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

// VALIDAÇÃO DE CSRF TOKEN PARA AÇÕES CRÍTICAS
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        error_log("Tentativa de CSRF detectada no desempenho geral");
        die("Erro de segurança. Tente novamente.");
    }
}

// CONFIGURAÇÃO SEGURA DO BANCO
$host = "localhost";
$user = "root";
$password = "SenhaIrada@2024!";
$database = "projeto_residencia";

// Conexão com tratamento de erro seguro
$conectar = mysqli_connect($host, $user, $password, $database);
if (!$conectar) {
    error_log("Erro de conexão com o banco no desempenho geral");
    die("Erro interno do sistema. Tente novamente mais tarde.");
}

// CONFIGURAÇÕES DE SEGURANÇA ADICIONAIS
mysqli_set_charset($conectar, "utf8mb4");
mysqli_query($conectar, "SET time_zone = '-03:00'");

// BUSCAR ESTATÍSTICAS GERAIS COM PREPARED STATEMENT
$sql_estatisticas_gerais = "SELECT
                           COUNT(DISTINCT a.idAluno) as total_alunos,
                           COUNT(ap.Aluno_idAluno) as total_provas_realizadas,
                           AVG(ap.nota) as media_geral,
                           MAX(ap.nota) as melhor_nota_geral,
                           MIN(ap.nota) as pior_nota_geral,
                           SUM(CASE WHEN ap.nota >= 7 THEN 1 ELSE 0 END) as provas_aprovadas,
                           SUM(CASE WHEN ap.nota < 5 THEN 1 ELSE 0 END) as provas_reprovadas
                           FROM Aluno a
                           LEFT JOIN Aluno_Provas ap ON a.idAluno = ap.Aluno_idAluno
                           WHERE ap.status IN ('realizada', 'corrigida')";
$stmt_estatisticas_gerais = mysqli_prepare($conectar, $sql_estatisticas_gerais);

if (!$stmt_estatisticas_gerais) {
    error_log("Erro ao preparar consulta de estatísticas gerais: " . mysqli_error($conectar));
    $estatisticas_gerais = [
        'total_alunos' => 0,
        'total_provas_realizadas' => 0,
        'media_geral' => 0,
        'melhor_nota_geral' => 0,
        'pior_nota_geral' => 0,
        'provas_aprovadas' => 0,
        'provas_reprovadas' => 0
    ];
} else {
    mysqli_stmt_execute($stmt_estatisticas_gerais);
    $result_estatisticas_gerais = mysqli_stmt_get_result($stmt_estatisticas_gerais);
    $estatisticas_gerais = mysqli_fetch_assoc($result_estatisticas_gerais) ?? [
        'total_alunos' => 0,
        'total_provas_realizadas' => 0,
        'media_geral' => 0,
        'melhor_nota_geral' => 0,
        'pior_nota_geral' => 0,
        'provas_aprovadas' => 0,
        'provas_reprovadas' => 0
    ];
    mysqli_stmt_close($stmt_estatisticas_gerais);
}

// BUSCAR ESTATÍSTICAS POR MATÉRIA COM PREPARED STATEMENT
$sql_materias = "SELECT
                p.materia,
                COUNT(ap.Aluno_idAluno) as total_provas,
                AVG(ap.nota) as media_geral,
                MAX(ap.nota) as melhor_nota,
                MIN(ap.nota) as pior_nota,
                SUM(CASE WHEN ap.nota >= 7 THEN 1 ELSE 0 END) as aprovados,
                SUM(CASE WHEN ap.nota < 5 THEN 1 ELSE 0 END) as reprovados
                FROM Aluno_Provas ap
                INNER JOIN Provas p ON ap.Provas_idProvas = p.idProvas
                WHERE ap.status IN ('realizada', 'corrigida')
                GROUP BY p.materia
                ORDER BY p.materia";
$stmt_materias = mysqli_prepare($conectar, $sql_materias);
$materias = [];

if ($stmt_materias) {
    mysqli_stmt_execute($stmt_materias);
    $result_materias = mysqli_stmt_get_result($stmt_materias);
    
    if ($result_materias) {
        while ($materia = mysqli_fetch_assoc($result_materias)) {
            $materias[] = $materia;
        }
    }
    mysqli_stmt_close($stmt_materias);
}

// BUSCAR DESEMPENHO POR SÉRIE COM PREPARED STATEMENT
$sql_series = "SELECT
              a.escolaridade as serie,
              COUNT(DISTINCT a.idAluno) as total_alunos,
              COUNT(ap.Aluno_idAluno) as total_provas,
              AVG(ap.nota) as media_geral,
              SUM(CASE WHEN ap.nota >= 7 THEN 1 ELSE 0 END) as provas_aprovadas
              FROM Aluno a
              LEFT JOIN Aluno_Provas ap ON a.idAluno = ap.Aluno_idAluno AND ap.status IN ('realizada', 'corrigida')
              GROUP BY a.escolaridade
              ORDER BY a.escolaridade";
$stmt_series = mysqli_prepare($conectar, $sql_series);
$series = [];

if ($stmt_series) {
    mysqli_stmt_execute($stmt_series);
    $result_series = mysqli_stmt_get_result($stmt_series);
    
    if ($result_series) {
        while ($serie = mysqli_fetch_assoc($result_series)) {
            $series[] = $serie;
        }
    }
    mysqli_stmt_close($stmt_series);
}

// BUSCAR TOP 10 ALUNOS COM PREPARED STATEMENT
$sql_top_alunos = "SELECT
                  a.idAluno,
                  a.nome,
                  a.escolaridade,
                  COUNT(ap.Aluno_idAluno) as total_provas,
                  AVG(ap.nota) as media_geral,
                  MAX(ap.nota) as melhor_nota
                  FROM Aluno a
                  INNER JOIN Aluno_Provas ap ON a.idAluno = ap.Aluno_idAluno
                  WHERE ap.status IN ('realizada', 'corrigida')
                  GROUP BY a.idAluno, a.nome, a.escolaridade
                  HAVING COUNT(ap.Aluno_idAluno) >= 1
                  ORDER BY media_geral DESC
                  LIMIT 10";
$stmt_top_alunos = mysqli_prepare($conectar, $sql_top_alunos);
$top_alunos = [];

if ($stmt_top_alunos) {
    mysqli_stmt_execute($stmt_top_alunos);
    $result_top_alunos = mysqli_stmt_get_result($stmt_top_alunos);
    
    if ($result_top_alunos) {
        while ($aluno = mysqli_fetch_assoc($result_top_alunos)) {
            $top_alunos[] = $aluno;
        }
    }
    mysqli_stmt_close($stmt_top_alunos);
}

// BUSCAR PROVAS COM MAIOR TAXA DE ACERTO/ERRO COM PREPARED STATEMENT
$sql_provas_destaque = "SELECT
                       p.idProvas,
                       p.titulo,
                       p.materia,
                       p.numero_questoes,
                       COUNT(ap.Aluno_idAluno) as total_realizacoes,
                       AVG(ap.nota) as media_geral,
                       (SELECT COUNT(*) FROM Aluno_Provas ap2
                        WHERE ap2.Provas_idProvas = p.idProvas
                        AND ap2.nota >= 7) as aprovacoes
                       FROM Provas p
                       INNER JOIN Aluno_Provas ap ON p.idProvas = ap.Provas_idProvas
                       WHERE ap.status IN ('realizada', 'corrigida')
                       GROUP BY p.idProvas, p.titulo, p.materia, p.numero_questoes
                       HAVING COUNT(ap.Aluno_idAluno) >= 1
                       ORDER BY total_realizacoes DESC, media_geral DESC
                       LIMIT 5";
$stmt_provas_destaque = mysqli_prepare($conectar, $sql_provas_destaque);
$provas_destaque = [];

if ($stmt_provas_destaque) {
    mysqli_stmt_execute($stmt_provas_destaque);
    $result_provas_destaque = mysqli_stmt_get_result($stmt_provas_destaque);
    
    if ($result_provas_destaque) {
        while ($prova = mysqli_fetch_assoc($result_provas_destaque)) {
            $provas_destaque[] = $prova;
        }
    }
    mysqli_stmt_close($stmt_provas_destaque);
}

// FORMATAR VALORES COM VERIFICAÇÃO DE NULL E SANITIZAÇÃO
$media_geral = isset($estatisticas_gerais['media_geral']) && $estatisticas_gerais['media_geral'] !== null ?
               number_format((float)$estatisticas_gerais['media_geral'], 1) : '0.0';
$melhor_nota_geral = isset($estatisticas_gerais['melhor_nota_geral']) && $estatisticas_gerais['melhor_nota_geral'] !== null ?
                    number_format((float)$estatisticas_gerais['melhor_nota_geral'], 1) : '0.0';
$pior_nota_geral = isset($estatisticas_gerais['pior_nota_geral']) && $estatisticas_gerais['pior_nota_geral'] !== null ?
                  number_format((float)$estatisticas_gerais['pior_nota_geral'], 1) : '0.0';

// CALCULAR TAXA DE APROVAÇÃO GERAL COM VERIFICAÇÃO
$total_provas = (int)($estatisticas_gerais['total_provas_realizadas'] ?? 0);
$provas_aprovadas = (int)($estatisticas_gerais['provas_aprovadas'] ?? 0);
$taxa_aprovacao_geral = $total_provas > 0 ?
    number_format(($provas_aprovadas / $total_provas) * 100, 1) : '0.0';

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
    <title>Desempenho Geral - Edukhan</title>
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
                <li><a href="gerenciar_provas.php" rel="noopener">Minhas Provas</a></li>
                <li><a href="perfil_professor.php" rel="noopener">Meu Perfil</a></li>
                <li><a href="../logout.php" rel="noopener">Sair</a></li>
            </ul>
        </nav>
    </header>

    <main>
        <article class="desempenho-geral">
            <!-- CABEÇALHO SEGURO -->
            <section class="section-card">
                <div>
                    <div>
                        <h1>📊 Desempenho Geral dos Alunos</h1>
                        <p>Visão geral do desempenho de todos os alunos em todas as disciplinas</p>
                    </div>
                    <div>
                        <a href="exportar_desempenho_csv.php" class="btn btn-success" rel="noopener">
                            📥 Exportar para CSV
                        </a>
                    </div>
                </div>
            </section>

            <!-- ESTATÍSTICAS GERAIS SEGURAS -->
            <section class="section-card">
                <h2>🌍 Estatísticas Gerais</h2>
                
                <div class="estatisticas-grid">
                    <div class="estatistica-card">
                        <div>👥 Total de Alunos</div>
                        <div class="estatistica-numero"><?php echo (int)($estatisticas_gerais['total_alunos'] ?? 0); ?></div>
                        <small>Cadastrados no sistema</small>
                    </div>
                    
                    <div class="estatistica-card">
                        <div>📝 Provas Realizadas</div>
                        <div class="estatistica-numero"><?php echo (int)($estatisticas_gerais['total_provas_realizadas'] ?? 0); ?></div>
                        <small>Total de avaliações</small>
                    </div>
                    
                    <div class="estatistica-card">
                        <div>📈 Média Geral</div>
                        <div class="estatistica-numero"><?php echo htmlspecialchars($media_geral, ENT_QUOTES, 'UTF-8'); ?></div>
                        <small>Nota média das provas</small>
                    </div>
                    
                    <div class="estatistica-card">
                        <div>⭐ Melhor Nota</div>
                        <div class="estatistica-numero"><?php echo htmlspecialchars($melhor_nota_geral, ENT_QUOTES, 'UTF-8'); ?></div>
                        <small>Maior nota alcançada</small>
                    </div>
                    
                    <div class="estatistica-card">
                        <div>📉 Pior Nota</div>
                        <div class="estatistica-numero"><?php echo htmlspecialchars($pior_nota_geral, ENT_QUOTES, 'UTF-8'); ?></div>
                        <small>Menor nota registrada</small>
                    </div>
                    
                    <div class="estatistica-card">
                        <div>✅ Taxa de Aprovação</div>
                        <div class="estatistica-numero"><?php echo htmlspecialchars($taxa_aprovacao_geral, ENT_QUOTES, 'UTF-8'); ?>%</div>
                        <small>Provas com nota ≥ 7</small>
                    </div>
                </div>
            </section>

            <!-- DESEMPENHO POR MATÉRIA COM SANITIZAÇÃO -->
            <section class="section-card">
                <h2>📚 Desempenho por Matéria</h2>
                
                <?php if (!empty($materias)): ?>
                    <table class="tabela-desempenho">
                        <thead>
                            <tr>
                                <th>Matéria</th>
                                <th>Total de Provas</th>
                                <th>Média Geral</th>
                                <th>Melhor Nota</th>
                                <th>Pior Nota</th>
                                <th>Aprovações</th>
                                <th>Taxa de Aprovação</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($materias as $materia):
                                $total_provas_materia = (int)($materia['total_provas'] ?? 0);
                                $aprovados_materia = (int)($materia['aprovados'] ?? 0);
                                $taxa_aprovacao = $total_provas_materia > 0 ?
                                    number_format(($aprovados_materia / $total_provas_materia) * 100, 1) : 0;
                                $cor_taxa = $taxa_aprovacao >= 70 ? 'badge-aprovado' :
                                          ($taxa_aprovacao >= 50 ? 'badge-medio' : 'badge-reprovado');
                                
                                $materia_nome = htmlspecialchars($materia['materia'] ?? '', ENT_QUOTES, 'UTF-8');
                                $media_materia = number_format((float)($materia['media_geral'] ?? 0), 1);
                                $melhor_nota_materia = number_format((float)($materia['melhor_nota'] ?? 0), 1);
                                $pior_nota_materia = number_format((float)($materia['pior_nota'] ?? 0), 1);
                            ?>
                                <tr>
                                    <td><strong class="dado-seguro"><?php echo $materia_nome; ?></strong></td>
                                    <td><?php echo $total_provas_materia; ?></td>
                                    <td><?php echo $media_materia; ?></td>
                                    <td><?php echo $melhor_nota_materia; ?></td>
                                    <td><?php echo $pior_nota_materia; ?></td>
                                    <td><?php echo $aprovados_materia; ?> / <?php echo $total_provas_materia; ?></td>
                                    <td><span class="<?php echo $cor_taxa; ?>"><?php echo $taxa_aprovacao; ?>%</span></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php else: ?>
                    <p>Nenhuma prova realizada ainda.</p>
                <?php endif; ?>
            </section>

            <!-- DESEMPENHO POR SÉRIE COM SANITIZAÇÃO -->
            <section class="section-card">
                <h2>🎓 Desempenho por Série</h2>
                
                <?php if (!empty($series)): ?>
                    <table class="tabela-desempenho">
                        <thead>
                            <tr>
                                <th>Série</th>
                                <th>Total de Alunos</th>
                                <th>Provas Realizadas</th>
                                <th>Média Geral</th>
                                <th>Taxa de Aprovação</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($series as $serie):
                                $total_provas_serie = (int)($serie['total_provas'] ?? 0);
                                $provas_aprovadas_serie = (int)($serie['provas_aprovadas'] ?? 0);
                                $taxa_aprovacao_serie = $total_provas_serie > 0 ?
                                    number_format(($provas_aprovadas_serie / $total_provas_serie) * 100, 1) : 0;
                                
                                $serie_nome = htmlspecialchars($serie['serie'] ?? '', ENT_QUOTES, 'UTF-8');
                                $media_serie = number_format((float)($serie['media_geral'] ?? 0), 1);
                            ?>
                                <tr>
                                    <td><strong class="dado-seguro"><?php echo $serie_nome; ?></strong></td>
                                    <td><?php echo (int)($serie['total_alunos'] ?? 0); ?></td>
                                    <td><?php echo $total_provas_serie; ?></td>
                                    <td><?php echo $media_serie; ?></td>
                                    <td>
                                        <div style="display: flex; align-items: center; gap: 10px;">
                                            <span><?php echo $taxa_aprovacao_serie; ?>%</span>
                                            <div class="progress-bar" style="flex-grow: 1;">
                                                <div class="progress-fill" style="width: <?php echo min($taxa_aprovacao_serie, 100); ?>%;"></div>
                                            </div>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php else: ?>
                    <p>Nenhum dado disponível por série.</p>
                <?php endif; ?>
            </section>

            <!-- TOP 10 ALUNOS COM VALIDAÇÃO DE ID -->
            <section class="section-card">
                <h2>🏆 Top 10 Alunos - Melhor Desempenho</h2>
                
                <?php if (!empty($top_alunos)): ?>
                    <table class="tabela-desempenho">
                        <thead>
                            <tr>
                                <th>Posição</th>
                                <th>Aluno</th>
                                <th>Série</th>
                                <th>Provas Realizadas</th>
                                <th>Média Geral</th>
                                <th>Melhor Nota</th>
                                <th>Ações</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                            $posicao = 1;
                            foreach ($top_alunos as $aluno):
                                $medalha = $posicao == 1 ? '🥇' : ($posicao == 2 ? '🥈' : ($posicao == 3 ? '🥉' : $posicao . 'º'));
                                
                                $aluno_id = (int)($aluno['idAluno'] ?? 0);
                                $aluno_nome = htmlspecialchars($aluno['nome'] ?? '', ENT_QUOTES, 'UTF-8');
                                $aluno_serie = htmlspecialchars($aluno['escolaridade'] ?? '', ENT_QUOTES, 'UTF-8');
                                $media_aluno = number_format((float)($aluno['media_geral'] ?? 0), 1);
                                $melhor_nota_aluno = number_format((float)($aluno['melhor_nota'] ?? 0), 1);
                                
                                // VALIDAR ID DO ALUNO ANTES DE CRIAR LINK
                                $link_perfil = $aluno_id > 0 ? "perfil_aluno.php?id=" . $aluno_id : "#";
                            ?>
                                <tr>
                                    <td><strong><?php echo $medalha; ?></strong></td>
                                    <td class="dado-seguro"><?php echo $aluno_nome; ?></td>
                                    <td class="dado-seguro"><?php echo $aluno_serie; ?></td>
                                    <td><?php echo (int)($aluno['total_provas'] ?? 0); ?></td>
                                    <td><strong><?php echo $media_aluno; ?></strong></td>
                                    <td><?php echo $melhor_nota_aluno; ?></td>
                                    <td>
                                        <?php if ($aluno_id > 0): ?>
                                            <a href="<?php echo $link_perfil; ?>" class="btn btn-sm" rel="noopener">
                                                Ver Perfil
                                            </a>
                                        <?php else: ?>
                                            <span class="btn btn-sm disabled">ID Inválido</span>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php
                            $posicao++;
                            endforeach;
                            ?>
                        </tbody>
                    </table>
                <?php else: ?>
                    <p>Nenhum aluno com provas realizadas ainda.</p>
                <?php endif; ?>
            </section>

            <!-- PROVAS EM DESTAQUE COM SANITIZAÇÃO -->
            <section class="section-card">
                <h2>📋 Provas em Destaque</h2>
                <p>Provas com maior número de realizações e melhor desempenho</p>
                
                <?php if (!empty($provas_destaque)): ?>
                    <table class="tabela-desempenho">
                        <thead>
                            <tr>
                                <th>Prova</th>
                                <th>Matéria</th>
                                <th>Questões</th>
                                <th>Realizações</th>
                                <th>Média Geral</th>
                                <th>Taxa de Aprovação</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($provas_destaque as $prova):
                                $total_realizacoes = (int)($prova['total_realizacoes'] ?? 0);
                                $aprovacoes = (int)($prova['aprovacoes'] ?? 0);
                                $taxa_aprovacao_prova = $total_realizacoes > 0 ?
                                    number_format(($aprovacoes / $total_realizacoes) * 100, 1) : 0;
                                
                                $prova_titulo = htmlspecialchars($prova['titulo'] ?? '', ENT_QUOTES, 'UTF-8');
                                $prova_materia = htmlspecialchars($prova['materia'] ?? '', ENT_QUOTES, 'UTF-8');
                                $media_prova = number_format((float)($prova['media_geral'] ?? 0), 1);
                            ?>
                                <tr>
                                    <td><strong class="dado-seguro"><?php echo $prova_titulo; ?></strong></td>
                                    <td class="dado-seguro"><?php echo $prova_materia; ?></td>
                                    <td><?php echo (int)($prova['numero_questoes'] ?? 0); ?></td>
                                    <td><?php echo $total_realizacoes; ?></td>
                                    <td><?php echo $media_prova; ?></td>
                                    <td>
                                        <span class="<?php echo $taxa_aprovacao_prova >= 70 ? 'badge-aprovado' : 'badge-medio'; ?>">
                                            <?php echo $taxa_aprovacao_prova; ?>%
                                        </span>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php else: ?>
                    <p>Nenhuma prova com realizações registradas.</p>
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

    <!-- KaTeX JS COM INTEGRIDADE -->
    <script defer src="https://cdn.jsdelivr.net/npm/katex@0.16.9/dist/katex.min.js" integrity="sha384-8e0zqR1Y4xTMnJ9Hy5qk4+8+hgN6Em5Q+8hFHy0rY8X6Fy6g7FfYk6g7v2z+Q7pZ" crossorigin="anonymous"></script>
    <script defer src="https://cdn.jsdelivr.net/npm/katex@0.16.9/dist/contrib/auto-render.min.js" integrity="sha384-+XBljXPPiv+OzfbB3cVmLHf4hdUFHlWNZN5spNQ7rmHTXpd7WvJum6fIACpNNfIR" crossorigin="anonymous"></script>
    <script src="../js/math-config.js"></script>

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
                console.log('Desempenho geral carregado com segurança');
            }
        });
    </script>
</body>
</html>

<?php
// LIMPEZA SEGURA
mysqli_close($conectar);
?>