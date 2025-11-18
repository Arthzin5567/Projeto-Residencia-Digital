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

require_once __DIR__ . '/../config/funcoes_comuns.php';

$aluno_id = verificarLoginAluno();
$conectar = conectarBanco();

// Verificar conexão
if (!$conectar) {
    die("Erro de conexão: " . mysqli_connect_error());
}


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
                    <div class="form-columns">
                        
                        <!-- Coluna 1 -->
                        <div class="form-column">
                            <h3>Dados Pessoais</h3>
                            
                            <div class="form-group">
                                <label for="nome_completo">Nome Completo *</label>
                                <input type="text" id="nome_completo" name="nome" 
                                    value="<?php echo htmlspecialchars($aluno['nome']); ?>" 
                                    required aria-required="true">
                            </div>
                            
                            <div class="form-group">
                                <label for="email">E-mail</label>
                                <input type="email" id="email" name="email" 
                                    value="<?php echo htmlspecialchars($aluno['email']); ?>"
                                    aria-describedby="email_help">
                                <small id="email_help" class="text-help">Usado para comunicações importantes</small>
                            </div>
                            
                            <div class="form-group">
                                <label for="telefone">Telefone</label>
                                <input type="text" id="telefone" name="telefone" 
                                    value="<?php echo htmlspecialchars($aluno['telefone']); ?>"
                                    placeholder="(11) 99999-9999"
                                    aria-describedby="telefone_help">
                                <small id="telefone_help" class="text-help">Formato: (DDD) 99999-9999</small>
                            </div>
                        </div>
                        
                        <!-- Coluna 2 -->
                        <div class="form-column">
                            <h3>Endereço e Escola</h3>
                            
                            <div class="form-group">
                                <label for="endereco">Endereço</label>
                                <input type="text" id="endereco" name="endereco" 
                                    value="<?php echo htmlspecialchars($aluno['endereco']); ?>"
                                    placeholder="Endereço completo"
                                    aria-describedby="endereco_help">
                                <small id="endereco_help" class="text-help">Rua, número, bairro, cidade</small>
                            </div>
                            
                            <div class="form-group">
                                <label for="escola">Escola</label>
                                <input type="text" id="escola" name="escola" 
                                    value="<?php echo htmlspecialchars($aluno['escola']); ?>"
                                    placeholder="Nome da escola">
                            </div>
                            
                            <div class="form-group">
                                <label for="turma">Turma</label>
                                <input type="text" id="turma" name="turma" 
                                    value="<?php echo htmlspecialchars($aluno['turma']); ?>"
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
                                <label for="nome_responsavel">Nome do Responsável *</label>
                                <input type="text" id="nome_responsavel"
                                    name="nome_responsavel"
                                    value="<?php echo htmlspecialchars($aluno['nome_responsavel'] ?? ''); ?>"
                                    required aria-required="true"
                                    placeholder="Nome completo do responsável">
                            </div>
                            <div class="form-group">
                                <label for="telefone_responsavel">Telefone do Responsável *</label>

                                <?php
                                    // Pré-formatar o telefone ANTES do input
                                    $telefone_responsavel_formatado = '';
                                    if (!empty($aluno['tell_responsavel']) && $aluno['tell_responsavel'] != 0) {
                                        $telefone_responsavel_formatado = formatarTelefone($aluno['tell_responsavel']);
                                    }
                                ?>

                                <input type="text" id="telefone_responsavel"
                                name="telefone_responsavel"
                                value="<?php echo htmlspecialchars($telefone_responsavel_formatado); ?>"
                                placeholder="(11) 99999-9999"
                                required aria-required="true"
                                aria-describedby="telefone_responsavel_help">
                                <small id="telefone_responsavel_help" class="text-help">Formato: (DDD) 99999-9999</small>
                            </div>
                        </div>
                    </div>
                    <?php endif; ?>
                    
                    <!-- Confirmação com código de acesso -->
                    <div class="confirmacao-section">
                        <h3>🔐 Confirmação de Segurança</h3>
                        <p>Para confirmar as alterações, digite seu código de acesso:</p>
                        
                        <div class="form-group">
                            <label for="codigo_confirmacao">Código de Acesso *</label>
                            <input type="text" id="codigo_confirmacao" name="codigo_confirmacao"
                                placeholder="Digite seu código" 
                                required aria-required="true"
                                aria-describedby="codigo_help">
                            <small id="codigo_help" class="text-help">Seu código de acesso pessoal</small>
                        </div>
                    </div>
                    
                    <div class="form-actions">
                        <button type="submit" name="atualizar_perfil" class="btn-primary">
                            ✅ Atualizar Perfil
                        </button>
                        
                        <a href="dashboard_aluno.php" class="btn-secondary">
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
        document.querySelector('#telefone')?.addEventListener('input', function(e) {
            formatarTelefone(this);
        });

        document.querySelector('#telefone_responsavel')?.addEventListener('input', function(e) {
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
            document.querySelector('#codigo_confirmacao').focus();
        <?php endif; ?>
    </script>
</body>
</html>
