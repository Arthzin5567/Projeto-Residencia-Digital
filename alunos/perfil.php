<?php
session_start();

function formatarTelefone($telefone) {
    if (empty($telefone) || $telefone == 0) {
        return '';
    }
    
    $telefone = preg_replace('/\D/', '', (string)$telefone);
    
    if (strlen($telefone) === 11) {
        return '(' . substr($telefone, 0, 2) . ') ' . substr($telefone, 2, 5) . '-' . substr($telefone, 7);
    } elseif (strlen($telefone) === 10) {
        return '(' . substr($telefone, 0, 2) . ') ' . substr($telefone, 2, 4) . '-' . substr($telefone, 6);
    } else {
        return $telefone;
    }
}

function limparTelefone($telefone) {
    if (empty($telefone)) {
        return '';
    }
    // Remove tudo que não é número
    return preg_replace('/\D/', '', $telefone);
}

// Verificar se o aluno está identificado
if (!isset($_SESSION['aluno_identificado'])) {
    echo "<script>
            alert('Acesso negado! Identifique-se primeiro.');
            location.href = '../index.php';
          </script>";
    exit();
}


require_once '../config/database_config.php';

$host = $db_config['host'];
$user = $db_config['user'];
$password = $db_config['password'];
$database = $db_config['database'];
$conectar = mysqli_connect($host, $user, $password, $database);

// Verificar conexão
if (!$conectar) {
    die("Erro de conexão: " . mysqli_connect_error());
}

$aluno_id = (int)$_SESSION['id_aluno'];

// Buscar dados do aluno
$sql_aluno = "SELECT * FROM Aluno WHERE idAluno = ?";
$stmt_aluno = mysqli_prepare($conectar, $sql_aluno);
mysqli_stmt_bind_param($stmt_aluno, "i", $aluno_id);
mysqli_stmt_execute($stmt_aluno);
$result_aluno = mysqli_stmt_get_result($stmt_aluno);
$aluno = mysqli_fetch_assoc($result_aluno);
mysqli_stmt_close($stmt_aluno);

// Verificar mensagens de sessão
$sucesso = $_SESSION['sucesso_perfil'] ?? null;
$erro = $_SESSION['erro_perfil'] ?? null;

// Limpar mensagens da sessão
unset($_SESSION['sucesso_perfil']);
unset($_SESSION['erro_perfil']);

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
                    <div class="alert alert-success">
                        ✅ <?php echo htmlspecialchars($sucesso); ?>
                    </div>
                <?php endif; ?>

                <?php if (isset($erro)): ?>
                    <div class="alert alert-error">
                        ❌ <?php echo htmlspecialchars($erro); ?>
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
                
                <form class="perfil-editar-formulario" method="POST" action="../includes/processa_edita_aluno.php">
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
                    <?php if (($aluno['idade'] ?? 0) < 18): ?>
                    <div class="responsavel-section">
                        <h3>👨‍👦 Dados do Responsável</h3>
                        <div class="form-row">
                            <div class="form-group">
                                <label>Nome do Responsável *</label>
                                <input type="text"
                                    name="nome_responsavel"
                                    value="<?php echo htmlspecialchars($aluno['nome_responsavel'] ?? ''); ?>"
                                    required
                                    placeholder="Nome completo do responsável">
                            </div>
                            <div class="form-group">
                                <label>Telefone do Responsável *</label>

                                <?php
                                    // Pré-formatar o telefone ANTES do input
                                    $telefone_responsavel_formatado = '';
                                    if (!empty($aluno['tell_responsavel']) && $aluno['tell_responsavel'] != 0) {
                                        $telefone_responsavel_formatado = formatarTelefone($aluno['tell_responsavel']);
                                    }
                                ?>

                                <input type="text"
                                name="telefone_responsavel"
                                value="<?php echo htmlspecialchars($telefone_responsavel_formatado); ?>"
                                placeholder="(11) 99999-9999"
                                required>
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
