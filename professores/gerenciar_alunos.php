<?php
session_start();

// HEADERS DE SEGURANÇA
header("X-Frame-Options: DENY");
header("X-Content-Type-Options: nosniff");
header("X-XSS-Protection: 1; mode=block");
header("Referrer-Policy: strict-origin-when-cross-origin");
header("Content-Security-Policy: default-src 'self'; img-src 'self' data:; style-src 'self' 'unsafe-inline';");

// VALIDAÇÃO RIGOROSA DE SESSÃO
if (!isset($_SESSION["logado"]) || $_SESSION["logado"] !== true || $_SESSION["tipo_usuario"] !== "professor") {
    header("Location: ../index.php?erro=acesso_negado");
    exit();
}

// VALIDAÇÃO DE CSRF TOKEN PARA AÇÕES
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        error_log("Tentativa de CSRF detectada no gerenciar alunos");
        die("Erro de segurança. Tente novamente.");
    }
}

$host = "localhost";
$user = "root";
$password = "SenhaIrada@2024!";
$database = "projeto_residencia";

// CONEXÃO SEGURA
$conectar = mysqli_connect($host, $user, $password, $database);
if (!$conectar) {
    error_log("Erro de conexão no gerenciar alunos");
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

// BUSCAR DADOS DO PROFESSOR COM PREPARED STATEMENT
$sql_professor = "SELECT idProfessor, nome, email, data_cadastro 
                  FROM Professor 
                  WHERE idProfessor = ? 
                  LIMIT 1";
$stmt_professor = mysqli_prepare($conectar, $sql_professor);

if (!$stmt_professor) {
    error_log("Erro ao preparar consulta do professor: " . mysqli_error($conectar));
    $professor = ['nome' => 'Professor', 'email' => ''];
} else {
    mysqli_stmt_bind_param($stmt_professor, "i", $professor_id);
    mysqli_stmt_execute($stmt_professor);
    $result_professor = mysqli_stmt_get_result($stmt_professor);
    $professor = mysqli_fetch_assoc($result_professor) ?? ['nome' => 'Professor', 'email' => ''];
    mysqli_stmt_close($stmt_professor);
}

// PROCESSAR PESQUISA COM VALIDAÇÃO
$pesquisa = "";
$where_condition = "1=1";
$pesquisa_segura = "";

if (isset($_GET['pesquisa']) && !empty(trim($_GET['pesquisa']))) {
    $pesquisa_input = trim($_GET['pesquisa']);
    $pesquisa_segura = htmlspecialchars($pesquisa_input, ENT_QUOTES, 'UTF-8');
    
    // VALIDAR TAMANHO DA PESQUISA
    if (strlen($pesquisa_input) > 100) {
        $pesquisa_input = substr($pesquisa_input, 0, 100);
    }
    
    // VERIFICAR SE É NÚMERO (ID) OU TEXTO (NOME)
    if (is_numeric($pesquisa_input)) {
        $id_pesquisa = (int)$pesquisa_input;
        // VALIDAR FAIXA DO ID
        if ($id_pesquisa > 0 && $id_pesquisa <= 999999) {
            $where_condition = "a.idAluno = ?";
            $pesquisa = $id_pesquisa;
            $tipo_pesquisa = 'id';
        } else {
            $where_condition = "1=0"; // Nenhum resultado para ID inválido
            $pesquisa = '';
        }
    } else {
        // PESQUISA POR TEXTO - USAR PREPARED STATEMENT
        $where_condition = "a.nome LIKE ?";
        $pesquisa = "%" . $pesquisa_input . "%";
        $tipo_pesquisa = 'texto';
    }
}

// BUSCAR ALUNOS COM ESTATÍSTICAS USANDO PREPARED STATEMENT
$sql_alunos = "SELECT 
                a.idAluno,
                a.nome,
                a.email,
                a.escolaridade,
                a.data_cadastro,
                COUNT(ap.idRegistro_prova) as total_provas,
                AVG(ap.nota) as media_geral,
                MAX(ap.nota) as melhor_nota,
                MIN(ap.nota) as pior_nota,
                SUM(CASE WHEN ap.nota >= 7 THEN 1 ELSE 0 END) as provas_aprovadas
               FROM Aluno a
               LEFT JOIN Aluno_Provas ap ON a.idAluno = ap.Aluno_idAluno 
               AND (ap.status = 'realizada' OR ap.status = 'corrigida')
               WHERE $where_condition
               GROUP BY a.idAluno, a.nome, a.email, a.escolaridade, a.data_cadastro
               ORDER BY a.nome ASC";

$stmt_alunos = mysqli_prepare($conectar, $sql_alunos);
$alunos = [];
$total_alunos = 0;

if ($stmt_alunos) {
    if ($pesquisa && isset($tipo_pesquisa)) {
        if ($tipo_pesquisa === 'id') {
            mysqli_stmt_bind_param($stmt_alunos, "i", $pesquisa);
        } else {
            mysqli_stmt_bind_param($stmt_alunos, "s", $pesquisa);
        }
    }
    
    mysqli_stmt_execute($stmt_alunos);
    $result_alunos = mysqli_stmt_get_result($stmt_alunos);
    
    if ($result_alunos) {
        while ($aluno = mysqli_fetch_assoc($result_alunos)) {
            $alunos[] = $aluno;
        }
        $total_alunos = count($alunos);
    }
    mysqli_stmt_close($stmt_alunos);
}

// BUSCAR TOTAL GERAL DE ALUNOS (para o footer)
$sql_total_geral = "SELECT COUNT(*) as total FROM Aluno WHERE ativo = 1";
$stmt_total_geral = mysqli_prepare($conectar, $sql_total_geral);
$total_geral = 0;

if ($stmt_total_geral) {
    mysqli_stmt_execute($stmt_total_geral);
    $result_total_geral = mysqli_stmt_get_result($stmt_total_geral);
    $row = mysqli_fetch_assoc($result_total_geral);
    $total_geral = (int)($row['total'] ?? 0);
    mysqli_stmt_close($stmt_total_geral);
}

// GERAR TOKEN CSRF
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}
?>

<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gerenciar Alunos - Edukhan</title>
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
                <li><a href="criar_prova.php" rel="noopener">Criar Provas</a></li>
                <li><a href="perfil_professor.php" rel="noopener">Meu Perfil</a></li>
                <li><a href="../logout.php" rel="noopener">Sair</a></li>
            </ul>
        </nav>
    </header>

    <main>
        <article class="gerenciar-alunos">
            <section class="gerenciar-alunos-informacoes">
                <h1>👥 Gerenciar Alunos</h1>
                <p>Visualize e pesquise por alunos cadastrados no sistema</p>
                
                <!-- FORMULÁRIO DE PESQUISA SEGURO -->
                <form method="GET" action="gerenciar_alunos.php">
                    <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
                    <div>
                        <input type="text" 
                               name="pesquisa" 
                               value="<?php echo $pesquisa_segura; ?>" 
                               placeholder="Pesquisar por ID ou nome do aluno..."
                               maxlength="100"
                               size="50">
                        <button type="submit">Pesquisar</button>
                    </div>
                </form>

                <!-- INFORMAÇÕES DOS RESULTADOS COM SANITIZAÇÃO -->
                <div class="gerenciar-alunos-resultado-pesquisa">
                    <p><strong><?php echo $total_alunos; ?></strong> aluno(s) encontrado(s)
                    <?php if (!empty($pesquisa_segura)): ?>
                        para "<strong class="dado-seguro"><?php echo $pesquisa_segura; ?></strong>"
                        <a href="gerenciar_alunos.php" rel="noopener">[Limpar pesquisa]</a>
                    <?php endif; ?>
                    </p>
                </div>
            </section>

            <!-- LISTA DE ALUNOS COM VALIDAÇÃO -->
            <section class="gerenciar-alunos-lista">
                <h2>📋 Lista de Alunos</h2>
                
                <?php if ($total_alunos > 0): ?>
                    <div class="gerenciar-alunos-cards">
                        <?php foreach ($alunos as $aluno):
                            // CALCULAR ESTATÍSTICAS COM VALIDAÇÃO
                            $total_provas = (int)($aluno['total_provas'] ?? 0);
                            $provas_aprovadas = (int)($aluno['provas_aprovadas'] ?? 0);
                            $taxa_aprovacao = $total_provas > 0 ?
                                ($provas_aprovadas / $total_provas) * 100 : 0;
                            
                            $media_geral = (float)($aluno['media_geral'] ?? 0);
                            $melhor_nota = (float)($aluno['melhor_nota'] ?? 0);
                            $pior_nota = (float)($aluno['pior_nota'] ?? 0);
                            
                            // DEFINIR COR DA MÉDIA
                            $media_class = 'media-media';
                            if ($media_geral >= 7) {
                                $media_class = 'media-alta';
                            } elseif ($media_geral < 5) {
                                $media_class = 'media-baixa';
                            }
                            
                            // SANITIZAR DADOS PARA EXIBIÇÃO
                            $aluno_id = (int)($aluno['idAluno'] ?? 0);
                            $aluno_nome = htmlspecialchars($aluno['nome'] ?? '', ENT_QUOTES, 'UTF-8');
                            $aluno_email = htmlspecialchars($aluno['email'] ?? '', ENT_QUOTES, 'UTF-8');
                            $aluno_serie = htmlspecialchars($aluno['escolaridade'] ?? '', ENT_QUOTES, 'UTF-8');
                            $data_cadastro = date('d/m/Y', strtotime($aluno['data_cadastro'] ?? 'now'));
                            
                            // VALIDAR ID DO ALUNO PARA O LINK
                            $link_perfil = $aluno_id > 0 ? "perfil_aluno.php?id=" . $aluno_id : "#";
                        ?>
                            <div class="aluno-card">
                                <div class="aluno-header">
                                    <div class="aluno-info">
                                        <h3 class="dado-seguro"><?php echo $aluno_nome; ?></h3>
                                        <div class="aluno-detalhes">
                                            <strong>ID:</strong> <?php echo $aluno_id; ?> |
                                            <strong>Série:</strong> <span class="dado-seguro"><?php echo $aluno_serie; ?></span> |
                                            <strong>Email:</strong> <span class="dado-seguro"><?php echo $aluno_email; ?></span>
                                        </div>
                                    </div>
                                    <div class="aluno-actions">
                                        <?php if ($aluno_id > 0): ?>
                                            <a href="<?php echo $link_perfil; ?>" rel="noopener">
                                                👁️ Ver Perfil Completo
                                            </a>
                                        <?php else: ?>
                                            <span class="link-disabled">👁️ ID Inválido</span>
                                        <?php endif; ?>
                                    </div>
                                </div>

                                <!-- ESTATÍSTICAS DO ALUNO -->
                                <div class="aluno-stats">
                                    <div class="stat-item">
                                        <div><strong>Provas Realizadas</strong></div>
                                        <div><?php echo $total_provas; ?></div>
                                    </div>
                                    
                                    <div class="stat-item">
                                        <div><strong>Média Geral</strong></div>
                                        <div class="<?php echo $media_class; ?>">
                                            <?php echo number_format($media_geral, 1); ?>
                                        </div>
                                    </div>
                                    
                                    <div class="stat-item">
                                        <div><strong>Melhor Nota</strong></div>
                                        <div>
                                            <?php echo number_format($melhor_nota, 1); ?>
                                        </div>
                                    </div>
                                    
                                    <div class="stat-item">
                                        <div><strong>Taxa de Aprovação</strong></div>
                                        <div>
                                            <?php echo number_format($taxa_aprovacao, 1); ?>%
                                        </div>
                                    </div>
                                </div>

                                <!-- INFORMAÇÕES ADICIONAIS -->
                                <div class="aluno-footer">
                                    <div>
                                        <strong>Cadastrado em:</strong> <?php echo $data_cadastro; ?>
                                    </div>
                                    <div>
                                        <strong>Provas realizadas:</strong> <?php echo $total_provas; ?>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php else: ?>
                    <div class="bnt-all-provas">
                        <h3>📭 Nenhum aluno encontrado</h3>
                        <p>
                            <?php if (!empty($pesquisa_segura)): ?>
                                Não foram encontrados alunos para "<strong class="dado-seguro"><?php echo $pesquisa_segura; ?></strong>".
                            <?php else: ?>
                                Não há alunos cadastrados no sistema.
                            <?php endif; ?>
                        </p>
                        <?php if (!empty($pesquisa_segura)): ?>
                            <a href="gerenciar_alunos.php" rel="noopener">
                                Ver todos os alunos
                            </a>
                        <?php endif; ?>
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
            <p><small>Total de alunos no sistema: <strong><?php echo $total_geral; ?></strong></small></p>
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
            
            // Validação do formulário de pesquisa
            const formPesquisa = document.querySelector('form[method="GET"]');
            if (formPesquisa) {
                formPesquisa.addEventListener('submit', function(e) {
                    const inputPesquisa = this.querySelector('input[name="pesquisa"]');
                    if (inputPesquisa && inputPesquisa.value.trim().length > 100) {
                        alert('A pesquisa deve ter no máximo 100 caracteres.');
                        e.preventDefault();
                    }
                });
            }
            
            // Log seguro para debug
            if (window.console && window.console.log) {
                console.log('Gerenciar alunos carregado com segurança');
            }
        });
    </script>
</body>
</html>

<?php
// LIMPEZA SEGURA
mysqli_close($conectar);
?>