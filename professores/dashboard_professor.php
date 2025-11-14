<?php
session_start();

//  HEADERS DE SEGURANÇA
header("X-Frame-Options: DENY");
header("X-Content-Type-Options: nosniff");
header("X-XSS-Protection: 1; mode=block");
header("Referrer-Policy: strict-origin-when-cross-origin");
header("Content-Security-Policy: default-src 'self'; img-src 'self' data:; style-src 'self' 'unsafe-inline';");

//  VALIDAÇÃO RIGOROSA DE SESSÃO
if (!isset($_SESSION["logado"]) || $_SESSION["logado"] !== true || $_SESSION["tipo_usuario"] !== "professor") {
    //  NÃO usar alert JavaScript para erro de autenticação
    header("Location: ../index.php?erro=acesso_negado");
    exit();
}

//  VALIDAÇÃO DE CSRF TOKEN PARA AÇÕES CRÍTICAS
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        error_log("Tentativa de CSRF detectada no dashboard professor");
        die("Erro de segurança. Tente novamente.");
    }
}

//  CONFIGURAÇÃO SEGURA DO BANCO
$host = "localhost";
$user = "root";
$password = "SenhaIrada@2024!";
$database = "projeto_residencia";

// Conexão com tratamento de erro seguro
$conectar = mysqli_connect($host, $user, $password, $database);
if (!$conectar) {
    error_log("Erro de conexão com o banco no dashboard professor");
    die("Erro interno do sistema. Tente novamente mais tarde.");
}

//  CONFIGURAÇÕES DE SEGURANÇA ADICIONAIS
mysqli_set_charset($conectar, "utf8mb4");
mysqli_query($conectar, "SET time_zone = '-03:00'");

//  VALIDAÇÃO E SANITIZAÇÃO DO ID DO PROFESSOR
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

// BUSCAR DADOS DO PROFESSOR COM PREPARED STATEMENT
$sql_professor = "SELECT idProfessor, nome, email, data_cadastro 
                  FROM Professor 
                  WHERE idProfessor = ? 
                  LIMIT 1";
$stmt_professor = mysqli_prepare($conectar, $sql_professor);

if (!$stmt_professor) {
    error_log("Erro ao preparar consulta do professor: " . mysqli_error($conectar));
    die("Erro interno do sistema.");
}

mysqli_stmt_bind_param($stmt_professor, "i", $professor_id);
mysqli_stmt_execute($stmt_professor);
$result_professor = mysqli_stmt_get_result($stmt_professor);

if (mysqli_num_rows($result_professor) === 0) {
    // PROFESSOR NÃO ENCONTRADO - POSSÍVEL TENTATIVA DE INVASÃO
    error_log("Tentativa de acesso com ID de professor inválido: " . $professor_id);
    session_destroy();
    header("Location: ../index.php?erro=usuario_nao_encontrado");
    mysqli_stmt_close($stmt_professor);
    mysqli_close($conectar);
    exit();
}

$professor = mysqli_fetch_assoc($result_professor);
mysqli_stmt_close($stmt_professor);

// ESTATÍSTICAS - TOTAL DE ALUNOS
$sql_total_alunos = "SELECT COUNT(*) as total FROM Aluno WHERE ativo = 1";
$stmt_total_alunos = mysqli_prepare($conectar, $sql_total_alunos);
$total_alunos = 0;

if ($stmt_total_alunos) {
    mysqli_stmt_execute($stmt_total_alunos);
    $result_total_alunos = mysqli_stmt_get_result($stmt_total_alunos);
    $row = mysqli_fetch_assoc($result_total_alunos);
    $total_alunos = (int)($row['total'] ?? 0);
    mysqli_stmt_close($stmt_total_alunos);
}

// ESTATÍSTICAS - TOTAL DE PROVAS CRIADAS PELO PROFESSOR
$sql_total_provas = "SELECT COUNT(*) as total FROM Provas WHERE Professor_idProfessor = ?";
$stmt_total_provas = mysqli_prepare($conectar, $sql_total_provas);
$total_provas = 0;

if ($stmt_total_provas) {
    mysqli_stmt_bind_param($stmt_total_provas, "i", $professor_id);
    mysqli_stmt_execute($stmt_total_provas);
    $result_total_provas = mysqli_stmt_get_result($stmt_total_provas);
    $row = mysqli_fetch_assoc($result_total_provas);
    $total_provas = (int)($row['total'] ?? 0);
    mysqli_stmt_close($stmt_total_provas);
}

// ESTATÍSTICAS - PROVAS REALIZADAS
$sql_provas_realizadas = "SELECT COUNT(DISTINCT ap.Provas_idProvas) as total 
                          FROM Aluno_Provas ap 
                          INNER JOIN Provas p ON ap.Provas_idProvas = p.idProvas 
                          WHERE p.Professor_idProfessor = ? 
                          AND ap.status IN ('realizada', 'corrigida')";
$stmt_provas_realizadas = mysqli_prepare($conectar, $sql_provas_realizadas);
$provas_realizadas = 0;

if ($stmt_provas_realizadas) {
    mysqli_stmt_bind_param($stmt_provas_realizadas, "i", $professor_id);
    mysqli_stmt_execute($stmt_provas_realizadas);
    $result_provas_realizadas = mysqli_stmt_get_result($stmt_provas_realizadas);
    $row = mysqli_fetch_assoc($result_provas_realizadas);
    $provas_realizadas = (int)($row['total'] ?? 0);
    mysqli_stmt_close($stmt_provas_realizadas);
}

// ÚLTIMAS PROVAS CRIADAS
$sql_ultimas_provas = "SELECT idProvas, titulo, materia, numero_questoes, serie_destinada, data_criacao 
                       FROM Provas 
                       WHERE Professor_idProfessor = ? 
                       ORDER BY data_criacao DESC 
                       LIMIT 5";
$stmt_ultimas_provas = mysqli_prepare($conectar, $sql_ultimas_provas);
$ultimas_provas = [];

if ($stmt_ultimas_provas) {
    mysqli_stmt_bind_param($stmt_ultimas_provas, "i", $professor_id);
    mysqli_stmt_execute($stmt_ultimas_provas);
    $result_ultimas_provas = mysqli_stmt_get_result($stmt_ultimas_provas);
    
    if ($result_ultimas_provas) {
        while ($prova = mysqli_fetch_assoc($result_ultimas_provas)) {
            $ultimas_provas[] = $prova;
        }
    }
    mysqli_stmt_close($stmt_ultimas_provas);
}

// GERAR TOKEN CSRF
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

// LIMPEZA DE DADOS SENSÍVEIS DA SESSÃO PARA EXIBIÇÃO
$usuario_seguro = htmlspecialchars($_SESSION['usuario'] ?? '', ENT_QUOTES, 'UTF-8');
$email_seguro = htmlspecialchars($professor['email'] ?? '', ENT_QUOTES, 'UTF-8');
?>

<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard Professor - Edukhan</title>
    <link rel="stylesheet" href="../css/style.css">
    
    <!-- META TAGS DE SEGURANÇA -->
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
                <li><a href="gerenciar_provas.php" rel="noopener">Minhas Provas</a></li>
                <li><a href="../logout.php" rel="noopener">Sair</a></li>
            </ul>
        </nav>
    </header>

    <main>
        <article class="dashboard-container">
            <section class="welcome-professor">
                <h1>🎓 Dashboard do Professor</h1>
                <p>Bem-vindo, <strong class="dado-seguro"><?php echo $usuario_seguro; ?></strong>! 👋</p>
                <p><strong>Email:</strong> <span class="dado-seguro"><?php echo $email_seguro; ?></span></p>
            </section>

            <!-- ESTATÍSTICAS SEGURAS -->
            <section class="stats-container">
                <div class="stat-card">
                    <h3>👥 Total de Alunos</h3>
                    <div><?php echo $total_alunos; ?></div>
                    <p>Alunos cadastrados</p>
                </div>

                <div class="stat-card">
                    <h3>📝 Provas Criadas</h3>
                    <div><?php echo $total_provas; ?></div>
                    <p>Suas avaliações</p>
                </div>

                <div class="stat-card">
                    <h3>📊 Provas Realizadas</h3>
                    <div><?php echo $provas_realizadas; ?></div>
                    <p>Avaliações concluídas</p>
                </div>

                <div class="stat-card">
                    <h3>⭐ Status</h3>
                    <div>Ativo</div>
                    <p>Professor</p>
                </div>
            </section>

            <!-- AÇÕES RÁPIDAS SEGURAS -->
            <section class="actions-container">
                <h2>⚡ Ações Rápidas</h2>
                <div class="action-card">
                    <div class="acoes">
                        <a href="gerenciar_alunos.php" rel="noopener">
                            <h3>👥 Gerenciar Alunos</h3>
                            <p>Visualize, edite e pesquise alunos cadastrados</p>
                            <small>▶️ Acessar</small>
                        </a>
                    </div>
                    
                    <div class="acoes">
                        <a href="criar_prova.php" rel="noopener">
                            <h3>📝 Criar Avaliação</h3>
                            <p>Elabore novas provas para os alunos</p>
                            <small>▶️ Acessar</small>
                        </a>
                    </div>
                    
                    <div class="acoes">
                        <a href="desempenho_geral.php" rel="noopener">
                            <h3>📊 Verificar desempenho geral dos alunos</h3>
                            <p>Analise o desempenho dos alunos</p>
                            <small>▶️ Acessar</small>
                        </a>
                    </div>
                    
                    <div class="acoes">
                        <a href="perfil_professor.php" rel="noopener">
                            <h3>👤 Meu Perfil</h3>
                            <p>Atualize suas informações pessoais</p>
                            <small>▶️ Acessar</small>
                        </a>
                    </div>
                </div>
            </section>

            <!-- ÚLTIMAS PROVAS CRIADAS COM SANITIZAÇÃO -->
            <section class="latest-tests">
                <h2>📋 Suas Últimas Provas</h2>
                
                <?php if (!empty($ultimas_provas)): ?>
                    <div>
                        <?php foreach ($ultimas_provas as $prova): ?>
                            <div>
                                <div class="prova-card">
                                    <div>
                                        <?php
                                        $titulo_seguro = !empty($prova['titulo'])
                                            ? htmlspecialchars($prova['titulo'], ENT_QUOTES, 'UTF-8')
                                            : htmlspecialchars($prova['materia'] . ' - Prova', ENT_QUOTES, 'UTF-8');
                                        
                                        $materia_segura = htmlspecialchars($prova['materia'] ?? '', ENT_QUOTES, 'UTF-8');
                                        $questoes_seguras = (int)($prova['numero_questoes'] ?? 0);
                                        $serie_segura = htmlspecialchars($prova['serie_destinada'] ?? '', ENT_QUOTES, 'UTF-8');
                                        $data_segura = date('d/m/Y', strtotime($prova['data_criacao']));
                                        ?>
                                        <h4 class="dado-seguro"><?php echo $titulo_seguro; ?></h4>
                                        <p>
                                            <strong>Matéria:</strong> <span class="dado-seguro"><?php echo $materia_segura; ?></span> |
                                            <strong>Questões:</strong> <?php echo $questoes_seguras; ?> |
                                            <strong>Série:</strong> <span class="dado-seguro"><?php echo $serie_segura; ?></span>
                                        </p>
                                    </div>
                                    <small><?php echo $data_segura; ?></small>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                    <div class="bnt-all-provas">
                        <a href="gerenciar_provas.php" rel="noopener">Ver Todas as Provas</a>
                    </div>
                <?php else: ?>
                    <p class="no-tests">
                        📭 Você ainda não criou nenhuma prova.
                    </p>
                    <div class="bnt-all-provas">
                        <a href="criar_prova.php" rel="noopener">Criar Primeira Prova</a>
                    </div>
                <?php endif; ?>
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
        </div>
    </footer>

    <script>
        // CÓDIGO JAVASCRIPT SEGURO
        document.addEventListener('DOMContentLoaded', function() {
            // Prevenir ações maliciosas
            document.addEventListener('contextmenu', function(e) {
                if (e.target.tagName === 'IMG') {
                    e.preventDefault();
                }
            });
            
            // Log seguro para debug
            if (window.console && window.console.log) {
                console.log('Dashboard professor carregado com segurança');
            }
        });
    </script>
</body>
</html>

<?php
// LIMPEZA SEGURA
mysqli_close($conectar);
?>