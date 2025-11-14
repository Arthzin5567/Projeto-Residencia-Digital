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

// Verificar se o ID do aluno foi passado
if (!isset($_GET['id']) || empty($_GET['id'])) {
    echo "<script> 
            alert('Aluno não especificado!');
            location.href = 'gerenciar_alunos.php';
          </script>";
    exit();
}

$aluno_id = $_GET['id'];
$host = "localhost";
$user = "root";
$password = "SenhaIrada@2024!";
$database = "projeto_residencia";
$conectar = mysqli_connect($host, $user, $password, $database);

// Buscar dados básicos do aluno
$sql_aluno = "SELECT * FROM Aluno WHERE idAluno = '$aluno_id'";
$result_aluno = mysqli_query($conectar, $sql_aluno);

if (mysqli_num_rows($result_aluno) == 0) {
    echo "<script> 
            alert('Aluno não encontrado!');
            location.href = 'gerenciar_alunos.php';
          </script>";
    exit();
}

$aluno = mysqli_fetch_assoc($result_aluno);

// Buscar estatísticas gerais
$sql_estatisticas = "SELECT 
                     COUNT(*) as total_provas,
                     AVG(nota) as media_geral,
                     MAX(nota) as melhor_nota,
                     MIN(nota) as pior_nota,
                     SUM(CASE WHEN nota >= 7 THEN 1 ELSE 0 END) as provas_aprovadas,
                     SUM(CASE WHEN nota < 5 THEN 1 ELSE 0 END) as provas_reprovadas
                     FROM Aluno_Provas 
                     WHERE Aluno_idAluno = '$aluno_id' 
                     AND (status = 'realizada' OR status = 'corrigida')";
$result_estatisticas = mysqli_query($conectar, $sql_estatisticas);
$estatisticas = mysqli_fetch_assoc($result_estatisticas);

// Buscar estatísticas por matéria
$sql_materias = "SELECT 
                 p.materia,
                 COUNT(*) as total_provas,
                 AVG(ap.nota) as media,
                 MAX(ap.nota) as melhor,
                 MIN(ap.nota) as pior
                 FROM Aluno_Provas ap
                 INNER JOIN Provas p ON ap.Provas_idProvas = p.idProvas
                 WHERE ap.Aluno_idAluno = '$aluno_id' 
                 AND (ap.status = 'realizada' OR ap.status = 'corrigida')
                 GROUP BY p.materia
                 ORDER BY p.materia";
$result_materias = mysqli_query($conectar, $sql_materias);

// Buscar todas as provas realizadas pelo aluno
$sql_provas = "SELECT 
               p.*,
               ap.nota,
               ap.data_realizacao,
               ap.status,
               ap.respostas
               FROM Aluno_Provas ap
               INNER JOIN Provas p ON ap.Provas_idProvas = p.idProvas
               WHERE ap.Aluno_idAluno = '$aluno_id' 
               AND (ap.status = 'realizada' OR ap.status = 'corrigida')
               ORDER BY ap.data_realizacao DESC";
$result_provas = mysqli_query($conectar, $sql_provas);
$total_provas = mysqli_num_rows($result_provas);

// Calcular evolução (comparando primeiras 3 com últimas 3 provas)
$sql_evolucao = "SELECT 
                 (SELECT AVG(nota) FROM (
                     SELECT nota FROM Aluno_Provas 
                     WHERE Aluno_idAluno = '$aluno_id' 
                     AND (status = 'realizada' OR status = 'corrigida')
                     ORDER BY data_realizacao ASC 
                     LIMIT 3
                 ) as primeiras) as media_inicial,
                 (SELECT AVG(nota) FROM (
                     SELECT nota FROM Aluno_Provas 
                     WHERE Aluno_idAluno = '$aluno_id' 
                     AND (status = 'realizada' OR status = 'corrigida')
                     ORDER BY data_realizacao DESC 
                     LIMIT 3
                 ) as ultimas) as media_recente";
$result_evolucao = mysqli_query($conectar, $sql_evolucao);
$evolucao = mysqli_fetch_assoc($result_evolucao);

// Formatar valores
$media_geral = $estatisticas['media_geral'] ? number_format($estatisticas['media_geral'], 1) : '0.0';
$melhor_nota = $estatisticas['melhor_nota'] ? number_format($estatisticas['melhor_nota'], 1) : '0.0';
$pior_nota = $estatisticas['pior_nota'] ? number_format($estatisticas['pior_nota'], 1) : '0.0';
$aprovacao_geral = $estatisticas['total_provas'] > 0 ? 
    number_format(($estatisticas['provas_aprovadas'] / $estatisticas['total_provas']) * 100, 1) : '0.0';

// Calcular evolução
$media_inicial = $evolucao['media_inicial'] ? floatval($evolucao['media_inicial']) : 0;
$media_recente = $evolucao['media_recente'] ? floatval($evolucao['media_recente']) : 0;

if ($media_inicial > 0) {
    $porcentagem_evolucao = (($media_recente - $media_inicial) / $media_inicial) * 100;
} else {
    $porcentagem_evolucao = $media_recente > 0 ? 100 : 0;
}

// Função para analisar as respostas do aluno
function analisarRespostas($prova_conteudo, $respostas_aluno) {
    $questoes_prova = json_decode($prova_conteudo, true);
    $respostas_array = json_decode($respostas_aluno, true);
    
    $analise = [
        'total_questoes' => count($questoes_prova),
        'acertos' => 0,
        'erros' => 0,
        'detalhes' => []
    ];
    
    if (!is_array($questoes_prova) || !is_array($respostas_array)) {
        return $analise;
    }
    
    foreach ($questoes_prova as $index => $questao) {
        $resposta_correta = $questao['resposta_correta'];
        $resposta_aluno = $respostas_array[$index] ?? null;
        
        $acertou = ($resposta_aluno === $resposta_correta);
        
        if ($acertou) {
            $analise['acertos']++;
        } else {
            $analise['erros']++;
        }
        
        $analise['detalhes'][] = [
            'numero' => $index + 1,
            'enunciado' => $questao['enunciado'],
            'resposta_correta' => $resposta_correta,
            'resposta_aluno' => $resposta_aluno,
            'acertou' => $acertou,
            'alternativas' => $questao['alternativas']
        ];
    }
    
    return $analise;
}
?>

<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Perfil do Aluno - Edukhan</title>
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
        <article class="perfil-aluno">
            <!-- CABEÇALHO DO PERFIL -->
            <section class="perfil-cabecalho">
                <div>
                    <div>
                        <h1>👤 Perfil do Aluno: <?php echo htmlspecialchars($aluno['nome']); ?></h1>
                        <p><strong>ID:</strong> <?php echo $aluno['idAluno']; ?> | 
                           <strong>Email:</strong> <?php echo htmlspecialchars($aluno['email']); ?> | 
                           <strong>Série:</strong> <?php echo htmlspecialchars($aluno['escolaridade']); ?></p>
                    </div>
                    <div class="bnt-all-provas">
                        <a href="gerenciar_alunos.php">← Voltar para Lista</a>
                    </div>
                </div>
            </section>

            <hr>

            <!-- ESTATÍSTICAS GERAIS -->
            <section class="estatisticas-gerais">
                <h2>📊 Desempenho Geral</h2>
                
                <div>
                    <div>
                        <div><strong>Total de Provas</strong></div>
                        <div><?php echo $estatisticas['total_provas']; ?></div>
                    </div>
                    
                    <div>
                        <div><strong>Média Geral</strong></div>
                        <div><?php echo $media_geral; ?></div>
                    </div>
                    
                    <div>
                        <div><strong>Melhor Nota</strong></div>
                        <div><?php echo $melhor_nota; ?></div>
                    </div>
                    
                    <div>
                        <div><strong>Pior Nota</strong></div>
                        <div><?php echo $pior_nota; ?></div>
                    </div>
                    
                    <div>
                        <div><strong>Taxa de Aprovação</strong></div>
                        <div><?php echo $aprovacao_geral; ?>%</div>
                    </div>
                </div>
            </section>

            <hr>

            <!-- HISTÓRICO DETALHADO DE PROVAS COM ANÁLISE -->
            <section class="historico-provas">
                <h2>📋 Histórico Completo de Provas</h2>
                
                <?php if ($total_provas > 0): ?>
                    <p>Total de provas realizadas: <strong><?php echo $total_provas; ?></strong></p>
                    
                    <?php while ($prova = mysqli_fetch_assoc($result_provas)): 
                        $cor_nota = $prova['nota'] >= 7 ? 'green' : ($prova['nota'] < 5 ? 'red' : 'orange');
                        $status_text = $prova['status'] === 'corrigida' ? '✅ Corrigida' : '⏳ Aguardando correção';
                        
                        // Analisar as respostas do aluno
                        $analise = analisarRespostas($prova['conteudo'], $prova['respostas']);
                        $percentual_acertos = $analise['total_questoes'] > 0 ? 
                            ($analise['acertos'] / $analise['total_questoes']) * 100 : 0;
                    ?>
                        <div>
                            <!-- Cabeçalho da Prova -->
                            <div class="historico-prova-cabecalho">
                                <div>
                                    <h3>Prova: <?php echo htmlspecialchars($prova['titulo'] ?: $prova['materia'] . ' - Prova'); ?></h3>
                                    <p>
                                        <strong>Matéria:</strong> <?php echo htmlspecialchars($prova['materia']); ?> | 
                                        <strong>Data:</strong> <?php echo date('d/m/Y', strtotime($prova['data_realizacao'])); ?> | 
                                        <strong>Questões:</strong> <?php echo htmlspecialchars($prova['numero_questoes']); ?>
                                    </p>
                                </div>
                                <div>
                                    <div>
                                        <?php echo number_format($prova['nota'], 1); ?>
                                    </div>
                                    <small><?php echo $status_text; ?></small>
                                </div>
                            </div>

                            <!-- Resumo do Desempenho -->
                            <div class="historico-prova-desempenho">
                                <h4>📊 Resumo do Desempenho</h4>
                                <div>
                                    <div>
                                        <div>✅ <?php echo $analise['acertos']; ?></div>
                                        <div>Acertos</div>
                                    </div>
                                    <div>
                                        <div>❌ <?php echo $analise['erros']; ?></div>
                                        <div>Erros</div>
                                    </div>
                                    <div>
                                        <div>📝 <?php echo $analise['total_questoes']; ?></div>
                                        <div>Total</div>
                                    </div>
                                    <div>
                                        <div>📈 <?php echo number_format($percentual_acertos, 1); ?>%</div>
                                        <div>Acertos</div>
                                    </div>
                                </div>
                            </div>

                            <!-- Detalhamento Questão por Questão -->
                            <div class="historico-prova-detalhes-questoes">
                                <h4 style="margin: 0 0 15px 0;">🔍 Análise Questão por Questão</h4>
                                
                                <?php foreach ($analise['detalhes'] as $detalhe): ?>
                                    <div>
                                        <div>
                                            <div>
                                                <strong>Questão <?php echo $detalhe['numero']; ?>:</strong> 
                                                <?php echo htmlspecialchars($detalhe['enunciado']); ?>
                                            </div>
                                            <div>
                                                <?php echo $detalhe['acertou'] ? '✅ ACERTOU' : '❌ ERROU'; ?>
                                            </div>
                                        </div>
                                        
                                        <div>
                                            <div>
                                                <strong>Resposta do Aluno:</strong><br>
                                                <?php if ($detalhe['resposta_aluno']): ?>
                                                    <span>
                                                        <?php echo $detalhe['resposta_aluno']; ?> - 
                                                        <?php echo htmlspecialchars($detalhe['alternativas'][$detalhe['resposta_aluno']] ?? 'N/A'); ?>
                                                    </span>
                                                <?php else: ?>
                                                    <span style="color: #666;">Não respondeu</span>
                                                <?php endif; ?>
                                            </div>
                                            <div>
                                                <strong>Resposta Correta:</strong><br>
                                                <span>
                                                    <?php echo $detalhe['resposta_correta']; ?> - 
                                                    <?php echo htmlspecialchars($detalhe['alternativas'][$detalhe['resposta_correta']] ?? 'N/A'); ?>
                                                </span>
                                            </div>
                                        </div>

                                        <!-- Alternativas -->
                                        <div>
                                            <strong>Alternativas:</strong><br>
                                            <?php foreach ($detalhe['alternativas'] as $letra => $texto): ?>
                                                <div>
                                                    <strong><?php echo $letra; ?>)</strong> 
                                                    <?php echo htmlspecialchars($texto); ?>
                                                    <?php if ($letra === $detalhe['resposta_correta']): ?>
                                                        <span> ✓</span>
                                                    <?php endif; ?>
                                                </div>
                                            <?php endforeach; ?>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>

                            <!-- Informações adicionais da prova -->
                            <div class="historico-prova-informacoes-adicionais">
                                <strong>Informações da Prova:</strong> 
                                Série destinada: <?php echo htmlspecialchars($prova['serie_destinada']); ?> | 
                                Conteúdo: <?php echo htmlspecialchars($prova['conteudo']); ?>
                            </div>
                        </div>
                    <?php endwhile; ?>
                <?php else: ?>
                    <div>
                        <h3>📭 Nenhuma prova realizada</h3>
                        <p>O aluno ainda não realizou nenhuma prova.</p>
                    </div>
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

    <!-- KaTeX JS -->
    <script defer src="https://cdn.jsdelivr.net/npm/katex@0.16.9/dist/katex.min.js"></script>
    <script defer src="https://cdn.jsdelivr.net/npm/katex@0.16.9/dist/contrib/auto-render.min.js"></script>
    <script src="../js/math-config.js"></script>

    
</body>
</html>

<?php mysqli_close($conectar); ?>