<?php
session_start();


require_once __DIR__ . '/../config/funcoes_comuns.php';
$conectar = conectarBanco();
$aluno_id = verificarLoginAluno();

//  Buscar dados do aluno
$sql_aluno = "SELECT * FROM Aluno WHERE idAluno = ?";
$stmt_aluno = mysqli_prepare($conectar, $sql_aluno);
mysqli_stmt_bind_param($stmt_aluno, "i", $aluno_id);
mysqli_stmt_execute($stmt_aluno);
$result_aluno = mysqli_stmt_get_result($stmt_aluno);
$aluno = mysqli_fetch_assoc($result_aluno);
mysqli_stmt_close($stmt_aluno);

//  Buscar todas as provas realizadas pelo aluno
$sql_provas = "SELECT p.*, ap.nota, ap.data_realizacao, ap.status, ap.respostas
               FROM Aluno_Provas ap
               INNER JOIN Provas p ON ap.Provas_idProvas = p.idProvas
               WHERE ap.Aluno_idAluno = ?
               AND (ap.status = 'realizada' OR ap.status = 'corrigida')
               ORDER BY p.materia, ap.data_realizacao DESC";
$stmt_provas = mysqli_prepare($conectar, $sql_provas);
mysqli_stmt_bind_param($stmt_provas, "i", $aluno_id);
mysqli_stmt_execute($stmt_provas);
$result_provas = mysqli_stmt_get_result($stmt_provas);
$total_provas_realizadas = mysqli_num_rows($result_provas);


// Calcular estatísticas gerais
$sql_estatisticas = "SELECT
                     COUNT(*) as total_provas,
                     AVG(nota) as media_geral,
                     MAX(nota) as melhor_nota,
                     MIN(nota) as pior_nota,
                     SUM(CASE WHEN nota >= 7 THEN 1 ELSE 0 END) as provas_aprovadas
                     FROM Aluno_Provas
                     WHERE Aluno_idAluno = ?
                     AND (status = 'realizada' OR status = 'corrigida')";
$stmt_estatisticas = mysqli_prepare($conectar, $sql_estatisticas);
mysqli_stmt_bind_param($stmt_estatisticas, "i", $aluno_id);
mysqli_stmt_execute($stmt_estatisticas);
$result_estatisticas = mysqli_stmt_get_result($stmt_estatisticas);
$estatisticas = mysqli_fetch_assoc($result_estatisticas);
mysqli_stmt_close($stmt_estatisticas);

// Calcular estatísticas por matéria - PORTUGUÊS
$sql_portugues = "SELECT
                  COUNT(*) as total,
                  AVG(ap.nota) as media,
                  MAX(ap.nota) as melhor,
                  MIN(ap.nota) as pior,
                  SUM(CASE WHEN ap.nota >= 7 THEN 1 ELSE 0 END) as aprovadas
                  FROM Aluno_Provas ap
                  INNER JOIN Provas p ON ap.Provas_idProvas = p.idProvas
                  WHERE ap.Aluno_idAluno = ?
                  AND (ap.status = 'realizada' OR ap.status = 'corrigida')
                  AND p.materia = 'Português'";
$stmt_portugues = mysqli_prepare($conectar, $sql_portugues);
mysqli_stmt_bind_param($stmt_portugues, "i", $aluno_id);
mysqli_stmt_execute($stmt_portugues);
$result_portugues = mysqli_stmt_get_result($stmt_portugues);
$portugues = mysqli_fetch_assoc($result_portugues);
mysqli_stmt_close($stmt_portugues);

// Calcular estatísticas por matéria - MATEMÁTICA
$sql_matematica = "SELECT
                   COUNT(*) as total,
                   AVG(ap.nota) as media,
                   MAX(ap.nota) as melhor,
                   MIN(ap.nota) as pior,
                   SUM(CASE WHEN ap.nota >= 7 THEN 1 ELSE 0 END) as aprovadas
                   FROM Aluno_Provas ap
                   INNER JOIN Provas p ON ap.Provas_idProvas = p.idProvas
                   WHERE ap.Aluno_idAluno = ?
                   AND (ap.status = 'realizada' OR ap.status = 'corrigida')
                   AND p.materia = 'Matematica'";
$stmt_matematica = mysqli_prepare($conectar, $sql_matematica);
mysqli_stmt_bind_param($stmt_matematica, "i", $aluno_id);
mysqli_stmt_execute($stmt_matematica);
$result_matematica = mysqli_stmt_get_result($stmt_matematica);
$matematica = mysqli_fetch_assoc($result_matematica);
mysqli_stmt_close($stmt_matematica);

// Calcular evolução geral
$sql_evolucao = "SELECT
                 (SELECT AVG(nota) FROM (
                     SELECT nota FROM Aluno_Provas
                     WHERE Aluno_idAluno = ?
                     AND (status = 'realizada' OR status = 'corrigida')
                     ORDER BY data_realizacao ASC
                     LIMIT 3
                 ) as primeiras) as media_inicial,
                 (SELECT AVG(nota) FROM (
                     SELECT nota FROM Aluno_Provas
                     WHERE Aluno_idAluno = ?
                     AND (status = 'realizada' OR status = 'corrigida')
                     ORDER BY data_realizacao DESC
                     LIMIT 3
                 ) as ultimas) as media_recente";
$stmt_evolucao = mysqli_prepare($conectar, $sql_evolucao);
mysqli_stmt_bind_param($stmt_evolucao, "ii", $aluno_id, $aluno_id);
mysqli_stmt_execute($stmt_evolucao);
$result_evolucao = mysqli_stmt_get_result($stmt_evolucao);
$evolucao = mysqli_fetch_assoc($result_evolucao);
mysqli_stmt_close($stmt_evolucao);

// Calcular porcentagem de evolução geral
$media_inicial = $evolucao['media_inicial'] ? floatval($evolucao['media_inicial']) : 0;
$media_recente = $evolucao['media_recente'] ? floatval($evolucao['media_recente']) : 0;

if ($media_inicial > 0) {
    $porcentagem_evolucao = (($media_recente - $media_inicial) / $media_inicial) * 100;
} else {
    $porcentagem_evolucao = $media_recente > 0 ? 100 : 0;
}

// Formatar valores gerais
$media_geral = $estatisticas['media_geral'] ? number_format($estatisticas['media_geral'], 1) : '0.0';
$melhor_nota = $estatisticas['melhor_nota'] ? number_format($estatisticas['melhor_nota'], 1) : '0.0';
$pior_nota = $estatisticas['pior_nota'] ? number_format($estatisticas['pior_nota'], 1) : '0.0';
$aprovacao_geral = $estatisticas['total_provas'] > 0 ?
    number_format(($estatisticas['provas_aprovadas'] / $estatisticas['total_provas']) * 100, 1) : '0.0';

// Formatar valores de Português
$media_portugues = $portugues['media'] ? number_format($portugues['media'], 1) : '0.0';
$melhor_portugues = $portugues['melhor'] ? number_format($portugues['melhor'], 1) : '0.0';
$aprovacao_portugues = $portugues['total'] > 0 ?
    number_format(($portugues['aprovadas'] / $portugues['total']) * 100, 1) : '0.0';

// Formatar valores de Matemática
$media_matematica = $matematica['media'] ? number_format($matematica['media'], 1) : '0.0';
$melhor_matematica = $matematica['melhor'] ? number_format($matematica['melhor'], 1) : '0.0';
$aprovacao_matematica = $matematica['total'] > 0 ?
    number_format(($matematica['aprovadas'] / $matematica['total']) * 100, 1) : '0.0';

// Extrair operações ternárias aninhadas
// Determinar a classe de evolução
$classe_evolucao = 'evolucao-neutra';
if ($porcentagem_evolucao > 0) {
    $classe_evolucao = 'evolucao-positiva';
} elseif ($porcentagem_evolucao < 0) {
    $classe_evolucao = 'evolucao-negativa';
}
?>

<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Histórico - Edukhan</title>
    <link rel="stylesheet" href="../css/style.css">
    <!-- KaTeX CSS -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/katex@0.16.9/dist/katex.min.css">
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

    <main class="container">
        <article class="historico">
            <section class="header-historico">
                <h1>📊 Meu Desempenho</h1>
                <p>Aluno: <strong><?php echo $_SESSION['nome_aluno']; ?></strong></p>
            </section>

            <!-- CARDS GERAIS -->
            <section class="estatisticas-grid">
                <div class="stat-card media">
                    <h3>📈 Média Geral</h3>
                    <div class="stat-number"><?php echo $media_geral; ?></div>
                    <p>Performance em todas as matérias</p>
                </div>

                <div class="stat-card melhor">
                    <h3>🏆 Melhor Nota</h3>
                    <div class="stat-number"><?php echo $melhor_nota; ?></div>
                    <p>Seu melhor desempenho</p>
                </div>

                <div class="stat-card pior">
                    <h3>📉 Pior Nota</h3>
                    <div class="stat-number"><?php echo $pior_nota; ?></div>
                    <p>Pontos a melhorar</p>
                </div>

                <div class="stat-card aprovacao">
                    <h3>✅ Taxa de Aprovação</h3>
                    <div class="stat-number"><?php echo $aprovacao_geral; ?>%</div>
                    <p>Provas com nota ≥ 7.0</p>
                </div>

                <div class="stat-card evolucao">
                    <h3>🚀 Sua Evolução</h3>
                    <div class="stat-number <?php echo $classe_evolucao; ?>">
                        <?php echo number_format($porcentagem_evolucao, 1); ?>%
                    </div>
                    <p>
                        <?php if ($porcentagem_evolucao > 0): ?>
                            📈 Melhorando!
                        <?php elseif ($porcentagem_evolucao < 0): ?>
                            📉 Precisa de atenção
                        <?php else: ?>
                            ➡️ Mantendo desempenho
                        <?php endif; ?>
                    </p>
                </div>
            </section>

            <!-- APROVEITAMENTO POR MATÉRIA -->
            <section class="materias-historico">
                <div class="materia-card-historico">
                    <h3>📚 Português</h3>
                    <div class="materia-number"><?php echo $media_portugues; ?></div>
                    <p>Média Geral</p>
                    <div>
                        <div>
                            <strong>🏆 Melhor</strong><br>
                            <?php echo $melhor_portugues; ?>
                        </div>
                        <div>
                            <strong>✅ Aprovação</strong><br>
                            <?php echo $aprovacao_portugues; ?>%
                        </div>
                    </div>
                    <p>
                        <?php echo $portugues['total'] ? $portugues['total'] . ' prova(s)' : 'Nenhuma prova'; ?>
                    </p>
                </div>

                <div class="materia-card-historico">
                    <h3>🔢 Matemática</h3>
                    <div class="materia-number"><?php echo $media_matematica; ?></div>
                    <p>Média Geral</p>
                    <div>
                        <div>
                            <strong>🏆 Melhor</strong><br>
                            <?php echo $melhor_matematica; ?>
                        </div>
                        <div>
                            <strong>✅ Aprovação</strong><br>
                            <?php echo $aprovacao_matematica; ?>%
                        </div>
                    </div>
                    <p>
                        <?php echo $matematica['total'] ? $matematica['total'] . ' prova(s)' : 'Nenhuma prova'; ?>
                    </p>
                </div>
            </section>

            <!-- DETALHES DA EVOLUÇÃO -->
            <section class="historico-evolucao">
                <h2>📈 Análise da Sua Evolução</h2>
                <div>
                    <div>
                        <h4>Média Inicial</h4>
                        <div>
                            <?php echo number_format($media_inicial, 1); ?>
                        </div>
                        <small>Primeiras 3 provas</small>
                    </div>
                    <div>
                        <h4>Média Recente</h4>
                        <div>
                            <?php echo number_format($media_recente, 1); ?>
                        </div>
                        <small>Últimas 3 provas</small>
                    </div>
                </div>
            </section>

            <!-- HISTÓRICO DE PROVAS POR MATÉRIA -->
            <section class="historico-provas-por-materia">
                <h2>📋 Histórico de Provas Realizadas</h2>
                
                <?php if ($total_provas_realizadas > 0): ?>
                    <p>Total de provas realizadas: <strong><?php echo $total_provas_realizadas; ?></strong></p>
                    
                    <?php
                    // ✅ Fechar o statement das provas apenas DEPOIS de usar os resultados
                    mysqli_stmt_close($stmt_provas);

                    // Buscar as provas novamente para exibir (ou usar array em memória)
                    $stmt_provas_again = mysqli_prepare($conectar, $sql_provas);
                    mysqli_stmt_bind_param($stmt_provas_again, "i", $aluno_id);
                    mysqli_stmt_execute($stmt_provas_again);
                    $result_provas_display = mysqli_stmt_get_result($stmt_provas_again);

                    $current_materia = '';
                    while ($prova = mysqli_fetch_assoc($result_provas_display)):
                        
                        $nota_class = 'nota-baixa';
                        if ($prova['nota'] >= 7) {
                            $nota_class = 'nota-alta';
                        } elseif ($prova['nota'] >= 5) {
                            $nota_class = 'nota-media';
                        }
                        
                        $materia_class = $prova['materia'] === 'Português' ? 'portugues-item' : 'matematica-item';
                        $title_class = $prova['materia'] === 'Português' ? 'title-portugues' : 'title-matematica';
                        
                        // Mostrar título da matéria quando mudar
                        if ($current_materia !== $prova['materia']) {
                            $current_materia = $prova['materia'];
                            echo "<div class='materia-title $title_class'><h3 style='margin:0;'>$current_materia</h3></div>";
                        }
                    ?>
                        <div class="prova-item <?php echo $materia_class; ?>">
                            <div class="prova-header">
                                <div>
                                    <h3><?php echo htmlspecialchars($prova['titulo'] ?: 'Prova Sem Título'); ?></h3>
                                    <div>
                                        <span class="badge badge-serie">🎯 <?php echo htmlspecialchars($prova['serie_destinada']); ?></span>
                                        <span class="badge badge-status">📅 <?php echo date('d/m/Y', strtotime($prova['data_realizacao'])); ?></span>
                                    </div>
                                </div>
                                <div class="nota <?php echo $nota_class; ?>">
                                    <?php echo number_format($prova['nota'], 1); ?>
                                </div>
                            </div>
                            <p><strong>Status:</strong>
                                <?php echo $prova['status'] === 'corrigida' ? '✅ Corrigida' : '⏳ Aguardando correção'; ?>
                            </p>
                            <p><strong>Questões:</strong> <?php echo htmlspecialchars($prova['numero_questoes']); ?></p>
                        </div>
                    <?php endwhile;
                        mysqli_stmt_close($stmt_provas_again);
                    ?>
                <?php else: ?>
                    <div class="nenhuma-prova-historico">
                        <h3>📭 Nenhuma prova realizada ainda</h3>
                        <p>Você ainda não realizou nenhuma prova.</p>
                        <a href="provas_disponiveis.php">
                            Ver Provas Disponíveis
                        </a>
                    </div>
                <?php endif; ?>
            </section>
        </article>
    </main>

    <!-- KaTeX JS -->
    <script defer src="https://cdn.jsdelivr.net/npm/katex@0.16.9/dist/katex.min.js"></script>
    <script defer src="https://cdn.jsdelivr.net/npm/katex@0.16.9/dist/contrib/auto-render.min.js"></script>
    <script src="../js/math-config.js"></script>

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
</body>
</html>

<?php mysqli_close($conectar); ?>
