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

$host = "localhost";
$user = "root";
$password = "SenhaIrada@2024!";
$database = "projeto_residencia";
$conectar = mysqli_connect($host, $user, $password, $database);

// Buscar estatísticas gerais de todos os alunos
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
$result_estatisticas_gerais = mysqli_query($conectar, $sql_estatisticas_gerais);

// Verificar se a query foi bem sucedida
if (!$result_estatisticas_gerais) {
    echo "Erro na query: " . mysqli_error($conectar);
    exit();
}

$estatisticas_gerais = mysqli_fetch_assoc($result_estatisticas_gerais);

// Buscar estatísticas por matéria
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
$result_materias = mysqli_query($conectar, $sql_materias);

// Buscar desempenho por série
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
$result_series = mysqli_query($conectar, $sql_series);

// Buscar top 10 alunos com melhor desempenho
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
$result_top_alunos = mysqli_query($conectar, $sql_top_alunos);

// Buscar provas com maior taxa de acerto/erro
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
$result_provas_destaque = mysqli_query($conectar, $sql_provas_destaque);

// Formatar valores com verificação de NULL
$media_geral = $estatisticas_gerais['media_geral'] ? number_format($estatisticas_gerais['media_geral'], 1) : '0.0';
$melhor_nota_geral = $estatisticas_gerais['melhor_nota_geral'] ? number_format($estatisticas_gerais['melhor_nota_geral'], 1) : '0.0';
$pior_nota_geral = $estatisticas_gerais['pior_nota_geral'] ? number_format($estatisticas_gerais['pior_nota_geral'], 1) : '0.0';

// Calcular taxa de aprovação geral com verificação
$taxa_aprovacao_geral = ($estatisticas_gerais['total_provas_realizadas'] > 0 && $estatisticas_gerais['provas_aprovadas']) ? 
    number_format(($estatisticas_gerais['provas_aprovadas'] / $estatisticas_gerais['total_provas_realizadas']) * 100, 1) : '0.0';
?>

<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Desempenho Geral - Edukhan</title>
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
                <li><a href="dashboard_professor.php">Dashboard</a></li>
                <li><a href="gerenciar_alunos.php">Alunos</a></li>
                <li><a href="criar_prova.php">Criar Prova</a></li>
                <li><a href="gerenciar_provas.php">Minhas Provas</a></li>
                <li><a href="perfil_professor.php">Meu Perfil</a></li>
                <li><a href="../logout.php">Sair</a></li>
            </ul>
        </nav>
    </header>

    <main>
        <article class="desempenho-geral">
            <!-- CABEÇALHO -->
            <section class="section-card">
                <div>
                    <div>
                        <h1>📊 Desempenho Geral dos Alunos</h1>
                        <p>Visão geral do desempenho de todos os alunos em todas as disciplinas</p>
                    </div>
                    <div>
                        <a href="exportar_desempenho_csv.php" class="btn btn-success">
                            📥 Exportar para CSV
                        </a>
                    </div>
                </div>
            </section>

            <!-- ESTATÍSTICAS GERAIS -->
            <section class="section-card">
                <h2>🌍 Estatísticas Gerais</h2>
                
                <div class="estatisticas-grid">
                    <div class="estatistica-card">
                        <div>👥 Total de Alunos</div>
                        <div class="estatistica-numero"><?php echo $estatisticas_gerais['total_alunos']; ?></div>
                        <small>Cadastrados no sistema</small>
                    </div>
                    
                    <div class="estatistica-card">
                        <div>📝 Provas Realizadas</div>
                        <div class="estatistica-numero"><?php echo $estatisticas_gerais['total_provas_realizadas']; ?></div>
                        <small>Total de avaliações</small>
                    </div>
                    
                    <div class="estatistica-card">
                        <div>📈 Média Geral</div>
                        <div class="estatistica-numero"><?php echo $media_geral; ?></div>
                        <small>Nota média das provas</small>
                    </div>
                    
                    <div class="estatistica-card">
                        <div>⭐ Melhor Nota</div>
                        <div class="estatistica-numero"><?php echo $melhor_nota_geral; ?></div>
                        <small>Maior nota alcançada</small>
                    </div>
                    
                    <div class="estatistica-card">
                        <div>📉 Pior Nota</div>
                        <div class="estatistica-numero"><?php echo $pior_nota_geral; ?></div>
                        <small>Menor nota registrada</small>
                    </div>
                    
                    <div class="estatistica-card">
                        <div>✅ Taxa de Aprovação</div>
                        <div class="estatistica-numero"><?php echo $taxa_aprovacao_geral; ?>%</div>
                        <small>Provas com nota ≥ 7</small>
                    </div>
                </div>
            </section>

            <!-- DESEMPENHO POR MATÉRIA -->
            <section class="section-card">
                <h2>📚 Desempenho por Matéria</h2>
                
                <?php if (mysqli_num_rows($result_materias) > 0): ?>
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
                            <?php while ($materia = mysqli_fetch_assoc($result_materias)): 
                                $taxa_aprovacao = $materia['total_provas'] > 0 ? 
                                    number_format(($materia['aprovados'] / $materia['total_provas']) * 100, 1) : 0;
                                $cor_taxa = $taxa_aprovacao >= 70 ? 'badge-aprovado' : 
                                          ($taxa_aprovacao >= 50 ? 'badge-medio' : 'badge-reprovado');
                            ?>
                                <tr>
                                    <td><strong><?php echo htmlspecialchars($materia['materia']); ?></strong></td>
                                    <td><?php echo $materia['total_provas']; ?></td>
                                    <td><?php echo number_format($materia['media_geral'], 1); ?></td>
                                    <td><?php echo number_format($materia['melhor_nota'], 1); ?></td>
                                    <td><?php echo number_format($materia['pior_nota'], 1); ?></td>
                                    <td><?php echo $materia['aprovados']; ?> / <?php echo $materia['total_provas']; ?></td>
                                    <td><span class="<?php echo $cor_taxa; ?>"><?php echo $taxa_aprovacao; ?>%</span></td>
                                </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                <?php else: ?>
                    <p>Nenhuma prova realizada ainda.</p>
                <?php endif; ?>
            </section>

            <!-- DESEMPENHO POR SÉRIE -->
            <section class="section-card">
                <h2>🎓 Desempenho por Série</h2>
                
                <?php if (mysqli_num_rows($result_series) > 0): ?>
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
                            <?php while ($serie = mysqli_fetch_assoc($result_series)): 
                                $taxa_aprovacao_serie = $serie['total_provas'] > 0 ? 
                                    number_format(($serie['provas_aprovadas'] / $serie['total_provas']) * 100, 1) : 0;
                            ?>
                                <tr>
                                    <td><strong><?php echo htmlspecialchars($serie['serie']); ?></strong></td>
                                    <td><?php echo $serie['total_alunos']; ?></td>
                                    <td><?php echo $serie['total_provas']; ?></td>
                                    <td><?php echo number_format($serie['media_geral'], 1); ?></td>
                                    <td>
                                        <div style="display: flex; align-items: center; gap: 10px;">
                                            <span><?php echo $taxa_aprovacao_serie; ?>%</span>
                                            <div class="progress-bar" style="flex-grow: 1;">
                                                <div class="progress-fill" style="width: <?php echo $taxa_aprovacao_serie; ?>%;"></div>
                                            </div>
                                        </div>
                                    </td>
                                </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                <?php else: ?>
                    <p>Nenhum dado disponível por série.</p>
                <?php endif; ?>
            </section>

            <!-- TOP 10 ALUNOS -->
            <section class="section-card">
                <h2>🏆 Top 10 Alunos - Melhor Desempenho</h2>
                
                <?php if (mysqli_num_rows($result_top_alunos) > 0): ?>
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
                            while ($aluno = mysqli_fetch_assoc($result_top_alunos)): 
                                $medalha = $posicao == 1 ? '🥇' : ($posicao == 2 ? '🥈' : ($posicao == 3 ? '🥉' : $posicao . 'º'));
                            ?>
                                <tr>
                                    <td><strong><?php echo $medalha; ?></strong></td>
                                    <td><?php echo htmlspecialchars($aluno['nome']); ?></td>
                                    <td><?php echo htmlspecialchars($aluno['escolaridade']); ?></td>
                                    <td><?php echo $aluno['total_provas']; ?></td>
                                    <td><strong><?php echo number_format($aluno['media_geral'], 1); ?></strong></td>
                                    <td><?php echo number_format($aluno['melhor_nota'], 1); ?></td>
                                    <td>
                                        <a href="perfil_aluno.php?id=<?php echo $aluno['idAluno']; ?>" class="btn btn-sm">
                                            Ver Perfil
                                        </a>
                                    </td>
                                </tr>
                            <?php 
                            $posicao++;
                            endwhile; 
                            ?>
                        </tbody>
                    </table>
                <?php else: ?>
                    <p>Nenhum aluno com provas realizadas ainda.</p>
                <?php endif; ?>
            </section>

            <!-- PROVAS EM DESTAQUE -->
            <section class="section-card">
                <h2>📋 Provas em Destaque</h2>
                <p>Provas com maior número de realizações e melhor desempenho</p>
                
                <?php if (mysqli_num_rows($result_provas_destaque) > 0): ?>
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
                            <?php while ($prova = mysqli_fetch_assoc($result_provas_destaque)): 
                                $taxa_aprovacao_prova = $prova['total_realizacoes'] > 0 ? 
                                    number_format(($prova['aprovacoes'] / $prova['total_realizacoes']) * 100, 1) : 0;
                            ?>
                                <tr>
                                    <td><strong><?php echo htmlspecialchars($prova['titulo']); ?></strong></td>
                                    <td><?php echo htmlspecialchars($prova['materia']); ?></td>
                                    <td><?php echo $prova['numero_questoes']; ?></td>
                                    <td><?php echo $prova['total_realizacoes']; ?></td>
                                    <td><?php echo number_format($prova['media_geral'], 1); ?></td>
                                    <td>
                                        <span class="<?php echo $taxa_aprovacao_prova >= 70 ? 'badge-aprovado' : 'badge-medio'; ?>">
                                            <?php echo $taxa_aprovacao_prova; ?>%
                                        </span>
                                    </td>
                                </tr>
                            <?php endwhile; ?>
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
                <li><a href="#">Como Usar a Plataforma</a></li>
                <li><a href="#">Materiais de Apoio</a></li>
                <li><a href="#">Suporte Técnico</a></li>
                <li><a href="#">Dúvidas Frequentes</a></li>
            </ul>
            <p class="copyright">© 2023 Edukhan - Plataforma de Avaliação Educacional. Todos os direitos reservados.</p>
        </div>
    </footer>
</body>

<!-- KaTeX JS -->
    <script defer src="https://cdn.jsdelivr.net/npm/katex@0.16.9/dist/katex.min.js"></script>
    <script defer src="https://cdn.jsdelivr.net/npm/katex@0.16.9/dist/contrib/auto-render.min.js"></script>
    <script src="../js/math-config.js"></script>

</html>

<?php mysqli_close($conectar); ?>