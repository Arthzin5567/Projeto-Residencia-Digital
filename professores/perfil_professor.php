<?php
session_start();

// 🔒 HEADERS DE SEGURANÇA
header("X-Frame-Options: DENY");
header("X-Content-Type-Options: nosniff");
header("X-XSS-Protection: 1; mode=block");
header("Referrer-Policy: strict-origin-when-cross-origin");
header("Content-Security-Policy: default-src 'self'; img-src 'self' data:; style-src 'self' 'unsafe-inline';");

// 🔒 VALIDAÇÃO RIGOROSA DE SESSÃO
if (!isset($_SESSION["logado"]) || $_SESSION["logado"] !== true || $_SESSION["tipo_usuario"] !== "professor") {
    header("Location: ../index.php?erro=acesso_negado");
    exit();
}

// 🔒 VALIDAÇÃO DO ID DO PROFESSOR
if (!isset($_SESSION['idProfessor']) || !is_numeric($_SESSION['idProfessor'])) {
    session_destroy();
    header("Location: ../index.php?erro=sessao_invalida");
    exit();
}

$professor_id = (int)$_SESSION['idProfessor'];

// 🔒 VALIDAÇÃO DE FAIXA PARA ID
if ($professor_id <= 0 || $professor_id > 999999) {
    session_destroy();
    header("Location: ../index.php?erro=id_invalido");
    exit();
}

require_once '../config/database_config.php';

$host = $db_config['host'];
$user = $db_config['user'];
$password = $db_config['password'];
$database = $db_config['database'];

// 🔒 CONEXÃO SEGURA
$conectar = mysqli_connect($host, $user, $password, $database);
if (!$conectar) {
    error_log("Erro de conexão no perfil professor");
    die("Erro interno do sistema. Tente novamente mais tarde.");
}

// 🔒 CONFIGURAÇÕES DE SEGURANÇA
mysqli_set_charset($conectar, "utf8mb4");
mysqli_query($conectar, "SET time_zone = '-03:00'");

// 🔒 BUSCAR DADOS DO PROFESSOR COM PREPARED STATEMENT (VERSÃO COMPATÍVEL)
$sql_professor = "SELECT idProfessor, nome, email, cpf, login, data_cadastro
                  FROM Professor
                  WHERE idProfessor = ?
                  LIMIT 1";
$stmt_professor = mysqli_prepare($conectar, $sql_professor);

if (!$stmt_professor) {
    error_log("Erro ao preparar consulta do professor: " . mysqli_error($conectar));
    die("Erro interno do sistema.");
}

// 🔒 CORREÇÃO: Usar bind_result em vez de get_result para melhor compatibilidade
mysqli_stmt_bind_param($stmt_professor, "i", $professor_id);
mysqli_stmt_execute($stmt_professor);

// Bind apenas dos campos que EXISTEM na tabela
mysqli_stmt_bind_result($stmt_professor, $id, $nome, $email, $cpf, $login, $data_cadastro);
mysqli_stmt_store_result($stmt_professor);

// 🔒 CORREÇÃO: Verificar se encontrou o usuário ANTES de destruir a sessão
if (mysqli_stmt_num_rows($stmt_professor) === 0) {
    mysqli_stmt_close($stmt_professor);
    mysqli_close($conectar);
    session_destroy();
    header("Location: ../index.php?erro=usuario_nao_encontrado");
    exit();
}

// Buscar os resultados
mysqli_stmt_fetch($stmt_professor);

// 🔒 CORREÇÃO: Criar array apenas com campos existentes + campos opcionais com valores padrão
$professor = [
    'idProfessor' => $id,
    'nome' => $nome,
    'email' => $email,
    'cpf' => $cpf,
    'login' => $login,
    'data_cadastro' => $data_cadastro,
    // Campos que podem não existir na tabela - valores padrão
    'telefone' => '',
    'especialidade' => '',
    'formacao' => ''
];
mysqli_stmt_close($stmt_professor);

// 🔒 PROCESSAR ATUALIZAÇÃO DO PERFIL COM VALIDAÇÃO
$sucesso = '';
$erro = '';

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['atualizar_perfil'])) {
    // 🔒 VALIDAÇÃO CSRF
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        $erro = "Erro de segurança. Tente novamente.";
    } else {
        // 🔒 COLETAR E VALIDAR DADOS DO FORMULÁRIO
        $nome = isset($_POST['nome']) ? trim($_POST['nome']) : '';
        $email = isset($_POST['email']) ? trim($_POST['email']) : '';
        $senha = isset($_POST['senha']) ? trim($_POST['senha']) : '';
        
        // 🔒 VALIDAÇÃO DE CAMPOS OBRIGATÓRIOS
        if (empty($nome) || empty($email)) {
            $erro = "Nome e e-mail são obrigatórios.";
        } elseif (strlen($nome) > 100) {
            $erro = "O nome deve ter no máximo 100 caracteres.";
        } elseif (strlen($email) > 100) {
            $erro = "O e-mail deve ter no máximo 100 caracteres.";
        } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $erro = "E-mail inválido.";
        } elseif (!empty($senha) && strlen($senha) < 6) {
            $erro = "A senha deve ter pelo menos 6 caracteres.";
        } else {
            // 🔒 CONSTRUIR SQL DE ATUALIZAÇÃO APENAS COM CAMPOS EXISTENTES
            if (!empty($senha)) {
                // Atualizar com senha (apenas campos que existem)
                $sql_atualizar = "UPDATE Professor SET
                                 nome = ?, email = ?, senha = ?
                                 WHERE idProfessor = ?";
                $stmt_atualizar = mysqli_prepare($conectar, $sql_atualizar);
                
                if ($stmt_atualizar) {
                    $senha_hash = password_hash($senha, PASSWORD_DEFAULT);
                    mysqli_stmt_bind_param($stmt_atualizar, "sssi", $nome, $email, $senha_hash, $professor_id);
                }
            } else {
                // Atualizar sem senha (apenas campos que existem)
                $sql_atualizar = "UPDATE Professor SET
                                 nome = ?, email = ?
                                 WHERE idProfessor = ?";
                $stmt_atualizar = mysqli_prepare($conectar, $sql_atualizar);
                
                if ($stmt_atualizar) {
                    mysqli_stmt_bind_param($stmt_atualizar, "ssi", $nome, $email, $professor_id);
                }
            }
            
            if (isset($stmt_atualizar) && $stmt_atualizar) {
                if (mysqli_stmt_execute($stmt_atualizar)) {
                    $sucesso = "Perfil atualizado com sucesso!";
                    // 🔒 ATUALIZAR DADOS NA SESSÃO
                    $_SESSION['usuario'] = $nome;
                    // 🔒 ATUALIZAR DADOS LOCAIS
                    $professor['nome'] = $nome;
                    $professor['email'] = $email;
                } else {
                    $erro = "Erro ao atualizar perfil: " . mysqli_stmt_error($stmt_atualizar);
                }
                mysqli_stmt_close($stmt_atualizar);
            } else {
                $erro = "Erro ao preparar atualização.";
            }
        }
    }
}

// 🔒 GERAR TOKEN CSRF
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

// 🔒 SANITIZAR DADOS PARA EXIBIÇÃO
$professor_nome = htmlspecialchars($professor['nome'] ?? '', ENT_QUOTES, 'UTF-8');
$professor_email = htmlspecialchars($professor['email'] ?? '', ENT_QUOTES, 'UTF-8');
$professor_telefone = htmlspecialchars($professor['telefone'] ?? '', ENT_QUOTES, 'UTF-8');
$professor_cpf = htmlspecialchars($professor['cpf'] ?? '', ENT_QUOTES, 'UTF-8');
$professor_login = htmlspecialchars($professor['login'] ?? '', ENT_QUOTES, 'UTF-8');
$professor_data_cadastro = date('d/m/Y', strtotime($professor['data_cadastro'] ?? 'now'));
$professor_especialidade = htmlspecialchars($professor['especialidade'] ?? '', ENT_QUOTES, 'UTF-8');
$professor_formacao = htmlspecialchars($professor['formacao'] ?? '', ENT_QUOTES, 'UTF-8');
?>

<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Meu Perfil - Edukhan</title>
    <link rel="stylesheet" href="../css/style.css">
    
    <!-- 🔒 META TAGS DE SEGURANÇA -->
    <meta http-equiv="Content-Security-Policy" content="default-src 'self'; img-src 'self' data:; style-src 'self' 'unsafe-inline';">
</head>
<body>
    <header>
        <nav>
            <div class="logo">
                <img src="../img/LOGOTIPO 1.avif" alt="logo" onerror="this.style.display='none'">
            </div>
            <ul class="nav-links">
                <li><a href="dashboard_professor.php" rel="noopener">Dashboard</a></li>
                <li><a href="gerenciar_alunos.php" rel="noopener">Alunos</a></li>
                <li><a href="gerenciar_provas.php" rel="noopener">Avaliações</a></li>
                <li><a href="../logout.php" rel="noopener">Sair</a></li>
            </ul>
        </nav>
    </header>

    <main>
        <article class="perfil-professor">
            <section class="perfil-professor-header">
                <h1>👤 Meu Perfil - Professor</h1>
                <p>Gerencie suas informações profissionais e pessoais</p>
                
                <?php if (!empty($sucesso)): ?>
                    <div class="alert alert-success">
                        ✅ <?php echo htmlspecialchars($sucesso, ENT_QUOTES, 'UTF-8'); ?>
                    </div>
                <?php endif; ?>
                
                <?php if (!empty($erro)): ?>
                    <div class="alert alert-error">
                        ❌ <?php echo htmlspecialchars($erro, ENT_QUOTES, 'UTF-8'); ?>
                    </div>
                <?php endif; ?>
            </section>

            <!-- 🔒 INFORMAÇÕES FIXAS -->
            <section class="perfil-professor-info-fixa">
                <h2>📋 Informações de Identificação</h2>
                <div class="info-grid">
                    <div class="info-col">
                        <p><strong>CPF:</strong> <span class="dado-seguro"><?php echo $professor_cpf; ?></span></p>
                        <p><strong>Login:</strong> <span class="dado-seguro"><?php echo $professor_login; ?></span></p>
                    </div>
                    <div class="info-col">
                        <p><strong>ID do Professor:</strong> <?php echo $professor_id; ?></p>
                        <p><strong>Data de Cadastro:</strong> <?php echo $professor_data_cadastro; ?></p>
                    </div>
                </div>
                <p class="info-aviso">
                    ⚠️ Estas informações não podem ser alteradas
                </p>
            </section>

            <!-- 🔒 FORMULÁRIO DE EDIÇÃO SEGURO -->
            <section class="perfil-professor-editar">
                <h2>✏️ Editar Informações</h2>
                <p>Atualize suas informações de contato e profissionais.</p>
                
                <form method="POST" action="perfil_professor.php" onsubmit="return validarFormulario()">
                    <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
                    
                    <div class="form-columns">
                        <!-- Coluna 1 - Dados Pessoais -->
                        <div class="form-col">
                            <h3>Dados Pessoais</h3>
                            
                            <div class="form-group">
                                <label for="nome">Nome Completo *</label>
                                <input type="text" id="nome" name="nome"
                                       value="<?php echo $professor_nome; ?>"
                                       required maxlength="100"
                                       oninput="validarNome(this)">
                                <small class="text-help">Máximo 100 caracteres</small>
                            </div>
                            
                            <div class="form-group">
                                <label for="email">E-mail *</label>
                                <input type="email" id="email" name="email"
                                       value="<?php echo $professor_email; ?>"
                                       required maxlength="100"
                                       oninput="validarEmail(this)">
                                <small class="text-help">Seu e-mail institucional</small>
                            </div>
                            
                            <div class="form-group">
                                <label for="telefone">Telefone</label>
                                <input type="text" id="telefone" name="telefone"
                                       value="<?php echo $professor_telefone; ?>"
                                       maxlength="20"
                                       placeholder="(11) 99999-9999"
                                       oninput="formatarTelefone(this)">
                                <small class="text-help">Formato: (11) 99999-9999</small>
                            </div>
                        </div>
                        
                        <!-- Coluna 2 - Dados Profissionais -->
                        <div class="form-col">
                            <h3>Dados Profissionais</h3>
                            
                            <div class="form-group">
                                <label for="especialidade">Especialidade</label>
                                <select id="especialidade" name="especialidade">
                                    <option value="">Selecione uma especialidade</option>
                                    <option value="Português" <?php echo $professor_especialidade === 'Português' ? 'selected' : ''; ?>>Português</option>
                                    <option value="Matemática" <?php echo $professor_especialidade === 'Matemática' ? 'selected' : ''; ?>>Matemática</option>
                                    <option value="Ambas" <?php echo $professor_especialidade === 'Ambas' ? 'selected' : ''; ?>>Português e Matemática</option>
                                </select>
                            </div>
                            
                            <div class="form-group">
                                <label for="formacao">Formação Acadêmica</label>
                                <select id="formacao" name="formacao">
                                    <option value="">Selecione a formação</option>
                                    <option value="Graduação" <?php echo $professor_formacao === 'Graduação' ? 'selected' : ''; ?>>Graduação</option>
                                    <option value="Especialização" <?php echo $professor_formacao === 'Especialização' ? 'selected' : ''; ?>>Especialização</option>
                                    <option value="Mestrado" <?php echo $professor_formacao === 'Mestrado' ? 'selected' : ''; ?>>Mestrado</option>
                                    <option value="Doutorado" <?php echo $professor_formacao === 'Doutorado' ? 'selected' : ''; ?>>Doutorado</option>
                                </select>
                            </div>
                            
                            <div class="form-group">
                                <label for="senha">Senha</label>
                                <input type="password" id="senha" name="senha"
                                       placeholder="Deixe em branco para manter a atual"
                                       minlength="6"
                                       oninput="validarSenha(this)">
                                <small class="text-help">Mínimo 6 caracteres. Preencha apenas se desejar alterar</small>
                            </div>
                        </div>
                    </div>
                    
                    <!-- 🔒 BOTÕES DE AÇÃO -->
                    <div class="bnt-all-provas">
                        <button type="submit" name="atualizar_perfil" class="btn btn-primary">✅ Atualizar Perfil</button>
                        <a href="dashboard_professor.php" class="btn btn-secondary" rel="noopener">↩️ Voltar ao Dashboard</a>
                    </div>
                </form>
            </section>

            <!-- 🔒 AJUDA -->
            <section class="perfil-professor-ajuda">
                <h3>💡 Informações Importantes</h3>
                <ul>
                    <li>Mantenha seus dados de contato atualizados para comunicação com alunos e administração</li>
                    <li>A especialidade define as matérias que você pode lecionar no sistema</li>
                    <li>Em caso de problemas com acesso, entre em contato com a administração</li>
                    <li>Suas informações pessoais são protegidas e não serão compartilhadas</li>
                </ul>
            </section>
        </article>
    </main>

    <footer>
        <div class="footer-content">
            <ul class="footer-links">
                <li><a href="#" rel="noopener">Como Usar a Plataforma</a></li>
                <li><a href="#" rel="noopener">Materiais de Apoio</a></li>
                <li><a href="#" rel="noopener">Suporte Técnico</a></li>
                <li><a href="#" rel="noopener">Dúvidas Frequentes</a></li>
            </ul>
            <p class="copyright">© 2023 Edukhan - Plataforma de Avaliação Educacional. Todos os direitos reservados.</p>
            <p><small>Professor: <strong class="dado-seguro"><?php echo $professor_nome; ?></strong></small></p>
        </div>
    </footer>

    <script>
        // 🔒 FUNÇÕES DE VALIDAÇÃO CLIENT-SIDE
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

        function validarNome(input) {
            const valor = input.value.trim();
            if (valor.length > 100) {
                input.value = valor.substring(0, 100);
                mostrarErro(input, 'Nome muito longo. Máximo 100 caracteres.');
            } else {
                limparErro(input);
            }
        }

        function validarEmail(input) {
            const valor = input.value.trim();
            const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
            
            if (valor && !emailRegex.test(valor)) {
                mostrarErro(input, 'Formato de e-mail inválido.');
            } else if (valor.length > 100) {
                input.value = valor.substring(0, 100);
                mostrarErro(input, 'E-mail muito longo.');
            } else {
                limparErro(input);
            }
        }

        function validarSenha(input) {
            const valor = input.value;
            if (valor && valor.length < 6) {
                mostrarErro(input, 'A senha deve ter pelo menos 6 caracteres.');
            } else {
                limparErro(input);
            }
        }

        function mostrarErro(input, mensagem) {
            limparErro(input);
            const erroDiv = document.createElement('div');
            erroDiv.className = 'erro-validacao';
            erroDiv.textContent = mensagem;
            erroDiv.style.color = '#dc3545';
            erroDiv.style.fontSize = '0.85em';
            erroDiv.style.marginTop = '5px';
            input.parentNode.appendChild(erroDiv);
            input.style.borderColor = '#dc3545';
        }

        function limparErro(input) {
            const erroDiv = input.parentNode.querySelector('.erro-validacao');
            if (erroDiv) {
                erroDiv.remove();
            }
            input.style.borderColor = '';
        }

        function validarFormulario() {
            const nome = document.getElementById('nome');
            const email = document.getElementById('email');
            const senha = document.getElementById('senha');
            let valido = true;

            // Validar nome
            if (!nome.value.trim()) {
                mostrarErro(nome, 'Nome é obrigatório.');
                valido = false;
            }

            // Validar email
            if (!email.value.trim()) {
                mostrarErro(email, 'E-mail é obrigatório.');
                valido = false;
            } else if (!/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email.value)) {
                mostrarErro(email, 'E-mail inválido.');
                valido = false;
            }

            // Validar senha se preenchida
            if (senha.value && senha.value.length < 6) {
                mostrarErro(senha, 'A senha deve ter pelo menos 6 caracteres.');
                valido = false;
            }

            if (!valido) {
                // Focar no primeiro campo com erro
                const primeiroErro = document.querySelector('.erro-validacao');
                if (primeiroErro) {
                    primeiroErro.previousElementSibling?.focus();
                }
                return false;
            }

            // 🔒 DESABILITAR BOTÃO PARA EVITAR MULTIPLOS CLICKS
            const submitBtn = document.querySelector('button[type="submit"]');
            if (submitBtn) {
                submitBtn.disabled = true;
                submitBtn.textContent = 'Atualizando...';
            }

            return true;
        }

        // 🔒 INICIALIZAÇÃO SEGURA
        document.addEventListener('DOMContentLoaded', function() {
            // Prevenir ações maliciosas
            document.addEventListener('contextmenu', function(e) {
                if (e.target.tagName === 'IMG') {
                    e.preventDefault();
                }
            });

            // Focar no primeiro campo se houver erro
            <?php if (!empty($erro)): ?>
                document.getElementById('nome')?.focus();
            <?php endif; ?>

            // Log seguro para debug
            if (window.console && window.console.log) {
                console.log('Perfil professor carregado com segurança');
            }
        });
    </script>
</body>
</html>

<?php
// 🔒 LIMPEZA SEGURA
mysqli_close($conectar);
?>
