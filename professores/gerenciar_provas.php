<?php
session_start();

// HEADERS DE SEGURANÇA
header("X-Frame-Options: DENY");
header("X-Content-Type-Options: nosniff");
header("X-XSS-Protection: 1; mode=block");
header("Referrer-Policy: strict-origin-when-cross-origin");
header("Content-Security-Policy: default-src 'self'; img-src 'self' data:; script-src 'self' 'unsafe-inline' https://cdn.jsdelivr.net; style-src 'self' 'unsafe-inline' https://cdn.jsdelivr.net;");

// VALIDAÇÃO RIGOROSA DE SESSÃO
if (!isset($_SESSION["logado"]) || $_SESSION["logado"] !== true || $_SESSION["tipo_usuario"] !== "professor") {
    header("Location: ../index.php?erro=acesso_negado");
    exit();
}

// VALIDAÇÃO DE CSRF TOKEN PARA AÇÕES
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        error_log("Tentativa de CSRF detectada no gerenciar provas");
        die("Erro de segurança. Tente novamente.");
    }
}

require_once '../config/database_config.php';

$host = $db_config['host'];
$user = $db_config['user'];
$password = $db_config['password'];
$database = $db_config['database'];

// CONEXÃO SEGURA
$conectar = mysqli_connect($host, $user, $password, $database);
if (!$conectar) {
    error_log("Erro de conexão no gerenciar provas");
    die("Erro interno do sistema. Tente novamente mais tarde.");
}

// CONFIGURAÇÕES DE SEGURANÇA
mysqli_set_charset($conectar, "utf8mb4");
mysqli_query($conectar, "SET time_zone = '-03:00'");

// VALIDAÇÃO DO ID DO PROFESSOR
if (!isset($_SESSION['idProfessor']) || !is_numeric($_SESSION['idProfessor'])) {
    session_destroy();
    header("Location: ../index.php?erro=sessao_invalida");
    exit();
}

$professor_id = (int)$_SESSION['idProfessor'];

// VALIDAÇÃO DE FAIXA PARA ID
if ($professor_id <= 0 || $professor_id > 999999) {
    session_destroy();
    header("Location: ../index.php?erro=id_invalido");
    exit();
}

// BUSCAR PROVAS DO PROFESSOR COM PREPARED STATEMENT
$sql_provas = "SELECT idProvas, titulo, materia, serie_destinada, data_criacao, conteudo, ativa
               FROM Provas
               WHERE Professor_idProfessor = ?
               ORDER BY data_criacao DESC";
$stmt_provas = mysqli_prepare($conectar, $sql_provas);

if (!$stmt_provas) {
    error_log("Erro ao preparar consulta de provas: " . mysqli_error($conectar));
    $provas = [];
} else {
    mysqli_stmt_bind_param($stmt_provas, "i", $professor_id);
    mysqli_stmt_execute($stmt_provas);
    $resultado_provas = mysqli_stmt_get_result($stmt_provas);
    $provas = [];
    
    if ($resultado_provas) {
        while ($prova = mysqli_fetch_assoc($resultado_provas)) {
            $provas[] = $prova;
        }
    }
    mysqli_stmt_close($stmt_provas);
}

// GERAR TOKEN CSRF
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

// VERIFICAR MENSAGENS DE SUCESSO/ERRO
$mensagem_sucesso = '';
$mensagem_erro = '';

if (isset($_SESSION['mensagem_sucesso'])) {
    $mensagem_sucesso = $_SESSION['mensagem_sucesso'];
    unset($_SESSION['mensagem_sucesso']);
}

if (isset($_GET['sucesso'])) {
    switch ($_GET['sucesso']) {
        case 'prova_editada':
            $mensagem_sucesso = "Prova atualizada com sucesso!";
            break;
        case 'prova_criada':
            $mensagem_sucesso = "Prova criada com sucesso!";
            break;
    }
}

if (isset($_GET['erro'])) {
    switch ($_GET['erro']) {
        case 'prova_nao_encontrada':
            $mensagem_erro = "Prova não encontrada ou você não tem permissão para acessá-la.";
            break;
        case 'erro_edicao':
            $mensagem_erro = "Erro ao editar a prova. Tente novamente.";
            break;
    }
}
?>

<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gerenciar Provas - Edukhan</title>
    <link rel="stylesheet" href="../css/style.css">
    <!-- KaTeX CSS COM INTEGRIDADE -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/katex@0.16.9/dist/katex.min.css" integrity="sha384-8e0zqR1Y4xTMnJ9Hy5qk4+8+hgN6Em5Q+8hFHy0rY8X6Fy6g7FfYk6g7v2z+Q7pZ" crossorigin="anonymous">
    
    <!-- META TAGS DE SEGURANÇA -->
    <meta http-equiv="Content-Security-Policy" content="default-src 'self'; img-src 'self' data:; script-src 'self' 'unsafe-inline' https://cdn.jsdelivr.net; style-src 'self' 'unsafe-inline' https://cdn.jsdelivr.net;">
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
                <li><a href="criar_prova.php" rel="noopener">Criar Prova</a></li>
                <li><a href="perfil_professor.php" rel="noopener">Meu Perfil</a></li>
                <li><a href="../logout.php" rel="noopener">Sair</a></li>
            </ul>
        </nav>
    </header>

    <main>
        <article class="gerenciar-provas-container">
            <h1>Minhas Provas</h1>
            
            <!-- MENSAGENS DE SUCESSO/ERRO -->
            <?php if (!empty($mensagem_sucesso)): ?>
                <div class="alert alert-success">
                    ✅ <?php echo htmlspecialchars($mensagem_sucesso, ENT_QUOTES, 'UTF-8'); ?>
                </div>
            <?php endif; ?>
            
            <?php if (!empty($mensagem_erro)): ?>
                <div class="alert alert-error">
                    ❌ <?php echo htmlspecialchars($mensagem_erro, ENT_QUOTES, 'UTF-8'); ?>
                </div>
            <?php endif; ?>
            
            <div class="provas-container">
                <?php if (!empty($provas)): ?>
                    <?php foreach ($provas as $prova):
                        // DECODIFICAR JSON COM VALIDAÇÃO
                        $conteudo = null;
                        if (!empty($prova['conteudo'])) {
                            $conteudo = json_decode($prova['conteudo'], true);
                            if (json_last_error() !== JSON_ERROR_NONE) {
                                $conteudo = [];
                            }
                        }
                        
                        $num_questoes = is_array($conteudo) ? count($conteudo) : 0;
                        
                        // SANITIZAR DADOS PARA EXIBIÇÃO
                        $prova_id = (int)($prova['idProvas'] ?? 0);
                        $prova_titulo = htmlspecialchars($prova['titulo'] ?? '', ENT_QUOTES, 'UTF-8');
                        $prova_materia = htmlspecialchars($prova['materia'] ?? '', ENT_QUOTES, 'UTF-8');
                        $prova_serie = htmlspecialchars($prova['serie_destinada'] ?? '', ENT_QUOTES, 'UTF-8');
                        $prova_data = date('d/m/Y', strtotime($prova['data_criacao'] ?? 'now'));
                        $prova_ativa = (bool)($prova['ativa'] ?? false);
                        
                        // VALIDAR IDS PARA LINKS
                        $link_visualizar = $prova_id > 0 ? "visualizar_prova.php?id=" . $prova_id : "#";
                        $link_editar = $prova_id > 0 ? "editar_prova.php?id=" . $prova_id : "#";
                        $link_excluir = $prova_id > 0 ? "../includes/excluir_prova.php?id=" . $prova_id : "#";
                        $link_ativar_desativar = $prova_id > 0 ?
                            ($prova_ativa ? "../includes/desativar_prova.php?id=" . $prova_id : "../includes/ativar_prova.php?id=" . $prova_id) : "#";
                        $link_resultados = $prova_id > 0 ? "resultados_prova.php?id=" . $prova_id : "#";
                    ?>
                        <div class="prova-card">
                            <div class="prova-header">
                                <div class="prova-title dado-seguro"><?php echo $prova_titulo; ?></div>
                                <div class="prova-meta">
                                    ID: <?php echo $prova_id; ?> |
                                    Criada em: <?php echo $prova_data; ?>
                                </div>
                            </div>
                            
                            <div class="prova-details">
                                <p><strong>Matéria:</strong> <span class="dado-seguro"><?php echo $prova_materia; ?></span></p>
                                <p><strong>Série:</strong> <span class="dado-seguro"><?php echo $prova_serie; ?></span></p>
                                <p><strong>Questões:</strong> <?php echo $num_questoes; ?></p>
                                <p><strong>Status:</strong>
                                    <span class="status-<?php echo $prova_ativa ? 'ativa' : 'inativa'; ?>">
                                        <?php echo $prova_ativa ? 'Ativa' : 'Inativa'; ?>
                                    </span>
                                </p>
                            </div>
                            
                            <div class="prova-actions">
                                <?php if ($prova_id > 0): ?>
                                    <a href="<?php echo $link_visualizar; ?>" class="btn btn-visualizar" rel="noopener">Visualizar</a>
                                    <a href="<?php echo $link_editar; ?>" class="btn btn-editar" rel="noopener">Editar</a>
                                    <a href="<?php echo $link_excluir; ?>"
                                       class="btn btn-excluir"
                                       onclick="return confirmarExclusao(<?php echo $prova_id; ?>, '<?php echo addslashes($prova_titulo); ?>')"
                                       rel="noopener">Excluir</a>
                                    
                                    <!-- Botão para ativar/desativar -->
                                    <?php if ($prova_ativa): ?>
                                        <a href="<?php echo $link_ativar_desativar; ?>" class="btn btn-desativar" rel="noopener">Desativar</a>
                                    <?php else: ?>
                                        <a href="<?php echo $link_ativar_desativar; ?>" class="btn btn-ativar" rel="noopener">Ativar</a>
                                    <?php endif; ?>
                                    
                                    <!-- Botão para ver resultados -->
                                    <a href="<?php echo $link_resultados; ?>" class="btn btn-resultados" rel="noopener">Resultados</a>
                                <?php else: ?>
                                    <span class="btn btn-disabled">ID Inválido</span>
                                <?php endif; ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <div class="empty-state">
                        <h3>Nenhuma prova encontrada</h3>
                        <p>Você ainda não criou nenhuma prova. <a href="criar_prova.php" rel="noopener">Clique aqui para criar a primeira!</a></p>
                    </div>
                <?php endif; ?>
            </div>
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
        </div>
    </footer>

    <!-- KaTeX JS COM INTEGRIDADE -->
    <script defer src="https://cdn.jsdelivr.net/npm/katex@0.16.9/dist/katex.min.js" integrity="sha384-8e0zqR1Y4xTMnJ9Hy5qk4+8+hgN6Em5Q+8hFHy0rY8X6Fy6g7FfYk6g7v2z+Q7pZ" crossorigin="anonymous"></script>
    <script defer src="https://cdn.jsdelivr.net/npm/katex@0.16.9/dist/contrib/auto-render.min.js" integrity="sha384-+XBljXPPiv+OzfbB3cVmLHf4hdUFHlWNZN5spNQ7rmHTXpd7WvJum6fIACpNNfIR" crossorigin="anonymous"></script>
    <script src="../js/math-config.js"></script>

    <script>
        // FUNÇÃO SEGURA PARA CONFIRMAR EXCLUSÃO
        function confirmarExclusao(provaId, provaTitulo) {
            if (!provaId || provaId <= 0) {
                alert('ID da prova inválido.');
                return false;
            }
            
            const mensagem = `Tem certeza que deseja excluir a prova "${provaTitulo}"?\n\nEsta ação não pode ser desfeita.`;
            return confirm(mensagem);
        }

        // CARREGAR ESTATÍSTICAS DAS PROVAS
        function carregarEstatisticas() {
            // Aqui você pode adicionar AJAX para carregar estatísticas em tempo real
            if (window.console && window.console.log) {
                console.log("Carregando estatísticas das provas com segurança...");
            }
        }
        
        // INICIALIZAÇÃO SEGURA
        document.addEventListener('DOMContentLoaded', function() {
            carregarEstatisticas();
            
            // Prevenir ações maliciosas
            document.addEventListener('contextmenu', function(e) {
                if (e.target.tagName === 'IMG') {
                    e.preventDefault();
                }
            });
            
            // Validar links antes de navegar
            document.querySelectorAll('a[href="#"]').forEach(link => {
                link.addEventListener('click', function(e) {
                    e.preventDefault();
                    alert('Link inválido ou prova não encontrada.');
                });
            });
        });

        // PREVENIR SUBMIT ACIDENTAL DE FORMULÁRIOS
        document.addEventListener('DOMContentLoaded', function() {
            const forms = document.querySelectorAll('form');
            forms.forEach(form => {
                form.addEventListener('submit', function(e) {
                    const submitBtn = this.querySelector('button[type="submit"], input[type="submit"]');
                    if (submitBtn) {
                        submitBtn.disabled = true;
                        submitBtn.textContent = 'Processando...';
                    }
                });
            });
        });
    </script>
</body>
</html>

<?php
// LIMPEZA SEGURA
mysqli_close($conectar);
?>
