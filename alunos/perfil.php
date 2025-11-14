<?php
session_start();

// Verificar se o aluno está identificado
if (!isset($_SESSION['aluno_identificado'])) {
    echo "<script> 
            alert('Acesso negado! Identifique-se primeiro.');
            location.href = '../index.php';
          </script>";
    exit();
}

$host = "localhost";
$user = "root";
$password = "SenhaIrada@2024!";
$database = "projeto_residencia";
$conectar = mysqli_connect($host, $user, $password, $database);
$aluno_id = $_SESSION['id_aluno'];

// Buscar dados do aluno
$sql_aluno = "SELECT * FROM Aluno WHERE idAluno = '$aluno_id'";
$result_aluno = mysqli_query($conectar, $sql_aluno);
$aluno = mysqli_fetch_assoc($result_aluno);

// Processar atualização do perfil
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['atualizar_perfil'])) {
    $codigo_confirmacao = mysqli_real_escape_string($conectar, $_POST['codigo_confirmacao']);
    
    // Verificar código de confirmação
    if ($codigo_confirmacao !== $aluno['codigo_acesso']) {
        $erro = "Código de confirmação incorreto!";
    } else {
        // Coletar dados do formulário
        $nome = mysqli_real_escape_string($conectar, $_POST['nome']);
        $email = mysqli_real_escape_string($conectar, $_POST['email']);
        $endereco = mysqli_real_escape_string($conectar, $_POST['endereco']);
        $telefone = mysqli_real_escape_string($conectar, $_POST['telefone']);
        $escola = mysqli_real_escape_string($conectar, $_POST['escola']);
        $turma = mysqli_real_escape_string($conectar, $_POST['turma']);
        
        // Para menores de idade, atualizar responsável também
        if ($aluno['idade'] < 18) {
            $nome_responsavel = mysqli_real_escape_string($conectar, $_POST['nome_responsavel']);
            $telefone_responsavel = mysqli_real_escape_string($conectar, $_POST['telefone_responsavel']);
        }
        
        // Construir SQL de atualização
        $sql_atualizar = "UPDATE Aluno SET 
                         nome = '$nome',
                         email = '$email',
                         endereco = '$endereco',
                         telefone = '$telefone',
                         escola = '$escola',
                         turma = '$turma'";
        
        // Adicionar campos do responsável se for menor de idade
        if ($aluno['idade'] < 18) {
            $sql_atualizar .= ", nome_responsavel = '$nome_responsavel', telefone_responsavel = '$telefone_responsavel'";
        }
        
        $sql_atualizar .= " WHERE idAluno = '$aluno_id'";
        
        if (mysqli_query($conectar, $sql_atualizar)) {
            $sucesso = "Perfil atualizado com sucesso!";
            // Atualizar dados na sessão
            $_SESSION['nome_aluno'] = $nome;
            $_SESSION['usuario'] = $nome;
            // Recarregar dados do aluno
            $result_aluno = mysqli_query($conectar, $sql_aluno);
            $aluno = mysqli_fetch_assoc($result_aluno);
        } else {
            $erro = "Erro ao atualizar perfil: " . mysqli_error($conectar);
        }
    }
}
?>

<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Meu Perfil - Edukhan</title>
    <link rel="stylesheet" href="../css/style.css">
</head>
<body>
    <header>
        <nav>
            <div class="logo">
                <img src="../img/LOGOTIPO 1.avif" alt="logo">
            </div>
            <ul class="nav-links">
                <li><a href="dashboard_aluno.php">Dashboard</a></li>
                <li><a href="provas_disponiveis.php">Provas</a></li>
                <li><a href="historico.php">Desempenho</a></li>
                <li><a href="perfil.php">Meu Perfil</a></li>
                <li><a href="../logout.php">Sair</a></li>
            </ul>
        </nav>
    </header>

    <main>
        <article class="perfil">
            <section class="perfil-header">
                <h1>👤 Meu Perfil</h1>
                <p>Gerencie suas informações pessoais</p>
                
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
            <section class="perfil-info-fixa">
                <h2>📋 Informações de Identificação</h2>
                <div>
                    <div>
                        <p><strong>CPF:</strong> <?php echo htmlspecialchars($aluno['cpf']); ?></p>
                        <p><strong>Idade:</strong> <?php echo htmlspecialchars($aluno['idade']); ?> anos</p>
                        <p><strong>Escolaridade:</strong> <?php echo htmlspecialchars($aluno['escolaridade']); ?></p>
                    </div>
                    <div>
                        <p><strong>Código de Acesso:</strong> 
                            <span>
                                <?php echo htmlspecialchars($aluno['codigo_acesso']); ?>
                            </span>
                        </p>
                        <p><strong>Data de Cadastro:</strong> <?php echo date('d/m/Y', strtotime($aluno['data_cadastro'])); ?></p>
                    </div>
                </div>
                <p>
                    ⚠️ Estas informações não podem ser alteradas
                </p>
            </section>

            <!-- FORMULÁRIO DE EDIÇÃO -->
            <section class="perfil-info-fixa">
                <h2>✏️ Editar Informações Pessoais</h2>
                <p>Para alterar seus dados, preencha o formulário abaixo e confirme com seu código de acesso.</p>
                
                <form class="perfil-editar-formulario" method="POST" action="perfil.php">
                    <div>
                        
                        <!-- Coluna 1 -->
                        <div>
                            <h3>Dados Pessoais</h3>
                            
                            <div>
                                <label>Nome Completo *</label>
                                <input type="text" name="nome" value="<?php echo htmlspecialchars($aluno['nome']); ?>" required>
                            </div>
                            
                            <div>
                                <label>E-mail</label>
                                <input type="email" name="email" value="<?php echo htmlspecialchars($aluno['email']); ?>">
                            </div>
                            
                            <div>
                                <label>Telefone</label>
                                <input type="text" name="telefone" value="<?php echo htmlspecialchars($aluno['telefone']); ?>" 
                                       placeholder="(11) 99999-9999">
                            </div>
                        </div>
                        
                        <!-- Coluna 2 -->
                        <div>
                            <h3>Endereço e Escola</h3>
                            
                            <div>
                                <label>Endereço</label>
                                <input type="text" name="endereco" value="<?php echo htmlspecialchars($aluno['endereco']); ?>" 
                                       placeholder="Endereço completo">
                            </div>
                            
                            <div>
                                <label>Escola</label>
                                <input type="text" name="escola" value="<?php echo htmlspecialchars($aluno['escola']); ?>" 
                                       placeholder="Nome da escola">
                            </div>
                            
                            <div>
                                <label>Turma</label>
                                <input type="text" name="turma" value="<?php echo htmlspecialchars($aluno['turma']); ?>" 
                                       placeholder="Turma/Classe">
                            </div>
                        </div>
                    </div>
                    
                    <!-- Dados do Responsável (apenas para menores) -->
                    <?php if ($aluno['idade'] < 18): ?>
                    <div>
                        <h3>👨‍👦 Dados do Responsável</h3>
                        <div>
                            <div>
                                <label>Nome do Responsável *</label>
                                <input type="text" name="nome_responsavel" value="<?php echo htmlspecialchars($aluno['nome_responsavel']); ?>"  required>
                            </div>
                            <div>
                                <label>Telefone do Responsável *</label>
                                <input type="text" name="telefone_responsavel" value="<?php echo htmlspecialchars($aluno['telefone_responsavel']); ?>" 
                                       placeholder="(11) 99999-9999" required>
                            </div>
                        </div>
                    </div>
                    <?php endif; ?>
                    
                    <!-- Confirmação com código de acesso -->
                    <div>
                        <h3>🔐 Confirmação de Segurança</h3>
                        <p>Para confirmar as alterações, digite seu código de acesso:</p>
                        
                        <div>
                            <label>Código de Acesso *</label>
                            <input type="text" name="codigo_confirmacao"
                                   placeholder="Digite seu código" required>
                        </div>
                    </div>
                    
                    <div>
                        <button type="submit" name="atualizar_perfil" >
                            ✅ Atualizar Perfil
                        </button>
                        
                        <a href="dashboard_aluno.php" >
                            ↩️ Voltar ao Dashboard
                        </a>
                    </div>
                </form>
            </section>

            <!-- AJUDA -->
            <section>
                <h3>💡 Dicas Importantes</h3>
                <ul>
                    <li>Seu <strong>código de acesso</strong> é necessário para confirmar qualquer alteração</li>
                    <li>Mantenha seus dados de contato atualizados para receber comunicados</li>
                    <li>Em caso de perda do código, entre em contato com seu professor</li>
                </ul>
            </section>
        </article>
    </main>

    <footer>
        <div class="footer-content">
            <ul class="footer-links">
                <li><a href="#">Como Usar a Plataforma</a></li>
                <li><a href="#">Materiais de Apoio</a></li>
                <li><a href="#">Suporte Técnico</a></li>
                <li><a href="#">Dúvidas Frequentes</a></li>
            </ul>
            <p class="copyright">© 2023 Edukhan - Plataforma de Avaliação Educacional. Todos os direitos reservados.</p>
        </div>
    </footer>

    <script>
        // Formatação automática do telefone
        document.querySelector('input[name="telefone"]')?.addEventListener('input', function(e) {
            formatarTelefone(this);
        });
        
        document.querySelector('input[name="telefone_responsavel"]')?.addEventListener('input', function(e) {
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

        // Focar no código de confirmação quando o formulário for submetido com erro
        <?php if (isset($erro)): ?>
            document.querySelector('input[name="codigo_confirmacao"]').focus();
        <?php endif; ?>
    </script>
</body>
</html>