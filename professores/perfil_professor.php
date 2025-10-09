<?php
session_start();

// Verificar se é professor
if (!isset($_SESSION["logado"]) || $_SESSION["logado"] !== true || $_SESSION["tipo_usuario"] !== "professor") {
    echo "<script> 
            alert('Acesso negado para professores!');
            location.href = '../index.php';
          </script>";
    exit();
}

$conectar = mysqli_connect("localhost", "root", "", "projeto_residencia");
$professor_id = $_SESSION['idProfessor'];

// Buscar dados do professor
$sql_professor = "SELECT * FROM Professor WHERE idProfessor = '$professor_id'";
$result_professor = mysqli_query($conectar, $sql_professor);
$professor = mysqli_fetch_assoc($result_professor);

// Processar atualização do perfil
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['atualizar_perfil'])) {
    // Coletar dados do formulário
    $nome = mysqli_real_escape_string($conectar, $_POST['nome']);
    $email = mysqli_real_escape_string($conectar, $_POST['email']);
    $telefone = mysqli_real_escape_string($conectar, $_POST['telefone']);
    $especialidade = mysqli_real_escape_string($conectar, $_POST['especialidade']);
    $formacao = mysqli_real_escape_string($conectar, $_POST['formacao']);
    
    // Construir SQL de atualização
    $sql_atualizar = "UPDATE Professor SET 
                     nome = '$nome',
                     email = '$email',
                     telefone = '$telefone',
                     especialidade = '$especialidade',
                     formacao = '$formacao'
                     WHERE idProfessor = '$professor_id'";
    
    if (mysqli_query($conectar, $sql_atualizar)) {
        $sucesso = "Perfil atualizado com sucesso!";
        // Atualizar dados na sessão
        $_SESSION['usuario'] = $nome;
        // Recarregar dados do professor
        $result_professor = mysqli_query($conectar, $sql_professor);
        $professor = mysqli_fetch_assoc($result_professor);
    } else {
        $erro = "Erro ao atualizar perfil: " . mysqli_error($conectar);
    }
}
?>

<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Meu Perfil - Edukhan</title>
</head>
<body>
    <header>
        <nav>
            <div>
                <h2>Edukhan - Perfil do Professor</h2>
            </div>
            <ul>
                <li><a href="dashboard_professor.php">Dashboard</a></li>
                <li><a href="gerenciar_alunos.php">Alunos</a></li>
                <li><a href="criar_prova.php">Avaliações</a></li>
                <li><a href="gerenciar_provas.php">Resultados</a></li>
                <li><a href="perfil_professor.php">Meu Perfil</a></li>
                <li><a href="../logout.php">Sair</a></li>
            </ul>
        </nav>
        <hr>
    </header>

    <main>
        <article>
            <section>
                <h1>👤 Meu Perfil - Professor</h1>
                <p>Gerencie suas informações profissionais e pessoais</p>
                
                <?php if (isset($sucesso)): ?>
                    <div>
                        ✅ <?php echo $sucesso; ?>
                    </div>
                <?php endif; ?>
                
                <?php if (isset($erro)): ?>
                    <div>
                        ❌ <?php echo $erro; ?>
                    </div>
                <?php endif; ?>
            </section>

            <!-- INFORMAÇÕES FIXAS -->
            <section>
                <h2>📋 Informações de Identificação</h2>
                <div>
                    <div>
                        <p><strong>CPF:</strong> <?php echo htmlspecialchars($professor['cpf']); ?></p>
                        <p><strong>Login:</strong> <?php echo htmlspecialchars($professor['login']); ?></p>
                    </div>
                    <div>
                        <p><strong>ID do Professor:</strong> <?php echo $professor['idProfessor']; ?></p>
                        <p><strong>Data de Cadastro:</strong> <?php echo date('d/m/Y', strtotime($professor['data_cadastro'])); ?></p>
                    </div>
                </div>
                <p>
                    ⚠️ Estas informações não podem ser alteradas
                </p>
            </section>

            <!-- FORMULÁRIO DE EDIÇÃO -->
            <section>
                <h2>✏️ Editar Informações</h2>
                <p>Atualize suas informações de contato e profissionais.</p>
                
                <form method="POST" action="perfil_professor.php">
                    <div>
                        
                        <!-- Coluna 1 - Dados Pessoais -->
                        <div>
                            <h3>Dados Pessoais</h3>
                            
                            <div>
                                <label>Nome Completo *</label>
                    <input type="text" name="nome" value="<?php echo htmlspecialchars($professor['nome']); ?>" required>
                            </div>
                            
                            <div>
                                <label>E-mail *</label>
                    <input type="email" name="email" value="<?php echo htmlspecialchars($professor['email']); ?>" required>
                            </div>
                            
                            <div>
                                <label>Telefone</label>
                    <input type="text" name="telefone" value="<?php echo htmlspecialchars($professor['telefone'] ?? ''); ?>" placeholder="(11) 99999-9999">
                            </div>
                        </div>
                        
                        <!-- Coluna 2 - Dados Profissionais -->
                        <div>
                            <h3>Dados Profissionais</h3>
                            
                            <div>
                                <label>Especialidade</label>
                                <select name="especialidade">
                                    <option value="">Selecione uma especialidade</option>
                                    <option value="Português" <?php echo ($professor['especialidade'] ?? '') === 'Português' ? 'selected' : ''; ?>>Português</option>
                                    <option value="Matemática" <?php echo ($professor['especialidade'] ?? '') === 'Matemática' ? 'selected' : ''; ?>>Matemática</option>
                                    <option value="Ambas" <?php echo ($professor['especialidade'] ?? '') === 'Ambas' ? 'selected' : ''; ?>>Português e Matemática</option>
                                </select>
                            </div>
                            
                            <div>
                                <label>Formação Acadêmica</label>
                                <select name="formacao">
                                    <option value="">Selecione a formação</option>
                                    <option value="Graduação" <?php echo ($professor['formacao'] ?? '') === 'Graduação' ? 'selected' : ''; ?>>Graduação</option>
                                    <option value="Especialização" <?php echo ($professor['formacao'] ?? '') === 'Especialização' ? 'selected' : ''; ?>>Especialização</option>
                                    <option value="Mestrado" <?php echo ($professor['formacao'] ?? '') === 'Mestrado' ? 'selected' : ''; ?>>Mestrado</option>
                                    <option value="Doutorado" <?php echo ($professor['formacao'] ?? '') === 'Doutorado' ? 'selected' : ''; ?>>Doutorado</option>
                                </select>
                            </div>
                            
                            <div>
                                <label>Senha</label>
                    <input type="password" name="senha" placeholder="Deixe em branco para manter a atual">
                    <small>Preencha apenas se desejar alterar a senha</small>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Botões de Ação -->
                    <div>
                        <button type="submit" name="atualizar_perfil">✅ Atualizar Perfil</button>
                        <a href="dashboard_professor.php">↩️ Voltar ao Dashboard</a>
                    </div>
                </form>
            </section>

            <!-- AJUDA -->
            <section>
                <h3>💡 Informações Importantes</h3>
                <ul>
                    <li>Mantenha seus dados de contato atualizados para comunicação com alunos e administração</li>
                    <li>A especialidade define as matérias que você pode lecionar no sistema</li>
                    <li>Em caso de problemas com acesso, entre em contato com a administração</li>
                </ul>
            </section>
        </article>
    </main>

    <footer>
        <p>&copy; 2023 Edukhan - Área do Professor</p>
        <p><small>Professor: <strong><?php echo htmlspecialchars($professor['nome']); ?></strong></small></p>
    </footer>

    <script>
        // Formatação automática do telefone
        document.querySelector('input[name="telefone"]')?.addEventListener('input', function(e) {
            formatarTelefone(this);
        });

        function formatarTelefone(input) {
            let value = input.value.replace(/\D/g, '');
            if (value.length > 11) value = value.substring(0, 11);
            
            if (value.length > 6) {
                value = value.replace(/(\d{2})(\d{5})(\d{4})/, '($1) $2-$3');
            } else if (value.length > 2) {
                value = value.replace(/(\d{2})(\d{0,5})/, '($1) $2');
            }
            input.value = value;
        }

        // Focar no primeiro campo se houver erro
        <?php if (isset($erro)): ?>
            document.querySelector('input[name="nome"]').focus();
        <?php endif; ?>
    </script>
</body>
</html>

<?php mysqli_close($conectar); ?>