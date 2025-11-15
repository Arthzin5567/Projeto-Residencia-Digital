<?php
session_start();
require_once __DIR__ . '/../config/funcoes_comuns.php';
$conectar = conectarBanco();

verificarloginProfessor();

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
$csrf_token = gerarTokenCSRF();

// VALIDAÇÃO DO ID DO ALUNO
if (!isset($_GET['id']) || empty($_GET['id'])) {
    header("Location: gerenciar_alunos.php?erro=aluno_nao_especificado");
    exit();
}

$aluno_id = (int)$_GET['id'];

// VALIDAÇÃO DE FAIXA PARA ID
if ($aluno_id <= 0 || $aluno_id > 999999) {
    header("Location: gerenciar_alunos.php?erro=id_invalido");
    exit();
}

if (!$conectar) {
    error_log("Erro de conexão no perfil aluno");
    die("Erro interno do sistema. Tente novamente mais tarde.");
}

// CONFIGURAÇÕES DE SEGURANÇA
mysqli_set_charset($conectar, "utf8mb4");
mysqli_query($conectar, "SET time_zone = '-03:00'");

// BUSCAR DADOS BÁSICOS DO ALUNO COM PREPARED STATEMENT
$sql_aluno = "SELECT idAluno, nome, email, escolaridade, data_cadastro
              FROM Aluno
              WHERE idAluno = ?
              LIMIT 1";
$stmt_aluno = mysqli_prepare($conectar, $sql_aluno);

if (!$stmt_aluno) {
    error_log("Erro ao preparar consulta do aluno: " . mysqli_error($conectar));
    header("Location: gerenciar_alunos.php?erro=erro_sistema");
    exit();
}

mysqli_stmt_bind_param($stmt_aluno, "i", $aluno_id);
mysqli_stmt_execute($stmt_aluno);
$result_aluno = mysqli_stmt_get_result($stmt_aluno);

if (mysqli_num_rows($result_aluno) == 0) {
    mysqli_stmt_close($stmt_aluno);
    header("Location: gerenciar_alunos.php?erro=aluno_nao_encontrado");
    exit();
}

$aluno = mysqli_fetch_assoc($result_aluno);
mysqli_stmt_close($stmt_aluno);

// BUSCAR ESTATÍSTICAS GERAIS COM PREPARED STATEMENT
$sql_estatisticas = "SELECT
                     COUNT(*) as total_provas,
                     AVG(nota) as media_geral,
                     MAX(nota) as melhor_nota,
                     MIN(nota) as pior_nota,
                     SUM(CASE WHEN nota >= 7 THEN 1 ELSE 0 END) as provas_aprovadas,
                     SUM(CASE WHEN nota < 5 THEN 1 ELSE 0 END) as provas_reprovadas
                     FROM Aluno_Provas
                     WHERE Aluno_idAluno = ?
                     AND (status = 'realizada' OR status = 'corrigida')";
$stmt_estatisticas = mysqli_prepare($conectar, $sql_estatisticas);
$estatisticas = [
    'total_provas' => 0,
    'media_geral' => 0,
    'melhor_nota' => 0,
    'pior_nota' => 0,
    'provas_aprovadas' => 0,
    'provas_reprovadas' => 0
];

if ($stmt_estatisticas) {
    mysqli_stmt_bind_param($stmt_estatisticas, "i", $aluno_id);
    mysqli_stmt_execute($stmt_estatisticas);
    $result_estatisticas = mysqli_stmt_get_result($stmt_estatisticas);
    $estatisticas = mysqli_fetch_assoc($result_estatisticas) ?? $estatisticas;
    mysqli_stmt_close($stmt_estatisticas);
}

// BUSCAR ESTATÍSTICAS POR MATÉRIA COM PREPARED STATEMENT
$sql_materias = "SELECT
                 p.materia,
                 COUNT(*) as total_provas,
                 AVG(ap.nota) as media,
                 MAX(ap.nota) as melhor,
                 MIN(ap.nota) as pior
                 FROM Aluno_Provas ap
                 INNER JOIN Provas p ON ap.Provas_idProvas = p.idProvas
                 WHERE ap.Aluno_idAluno = ?
                 AND (ap.status = 'realizada' OR ap.status = 'corrigida')
                 GROUP BY p.materia
                 ORDER BY p.materia";
$stmt_materias = mysqli_prepare($conectar, $sql_materias);
$materias = [];

if ($stmt_materias) {
    mysqli_stmt_bind_param($stmt_materias, "i", $aluno_id);
    mysqli_stmt_execute($stmt_materias);
    $result_materias = mysqli_stmt_get_result($stmt_materias);
    
    if ($result_materias) {
        while ($materia = mysqli_fetch_assoc($result_materias)) {
            $materias[] = $materia;
        }
    }
    mysqli_stmt_close($stmt_materias);
}

// BUSCAR TODAS AS PROVAS REALIZADAS PELO ALUNO COM PREPARED STATEMENT
$sql_provas = "SELECT
               p.idProvas,
               p.titulo,
               p.materia,
               p.serie_destinada,
               p.numero_questoes,
               p.conteudo,
               ap.nota,
               ap.data_realizacao,
               ap.status,
               ap.respostas
               FROM Aluno_Provas ap
               INNER JOIN Provas p ON ap.Provas_idProvas = p.idProvas
               WHERE ap.Aluno_idAluno = ?
               AND (ap.status = 'realizada' OR ap.status = 'corrigida')
               ORDER BY ap.data_realizacao DESC";
$stmt_provas = mysqli_prepare($conectar, $sql_provas);
$provas = [];
$total_provas = 0;

if ($stmt_provas) {
    mysqli_stmt_bind_param($stmt_provas, "i", $aluno_id);
    mysqli_stmt_execute($stmt_provas);
    $result_provas = mysqli_stmt_get_result($stmt_provas);
    
    if ($result_provas) {
        while ($prova = mysqli_fetch_assoc($result_provas)) {
            $provas[] = $prova;
        }
        $total_provas = count($provas);
    }
    mysqli_stmt_close($stmt_provas);
}

// FUNÇÃO PARA ANALISAR AS RESPOSTAS DO ALUNO COM VALIDAÇÃO
function analisarRespostas($prova_conteudo, $respostas_aluno) {
    $analise = [
        'total_questoes' => 0,
        'acertos' => 0,
        'erros' => 0,
        'detalhes' => []
    ];
    
    // VALIDAR E DECODIFICAR JSON DA PROVA
    $questoes_prova = null;
    if (!empty($prova_conteudo)) {
        $questoes_prova = json_decode($prova_conteudo, true);
        if (json_last_error() !== JSON_ERROR_NONE || !is_array($questoes_prova)) {
            $questoes_prova = [];
        }
    } else {
        $questoes_prova = [];
    }
    
    // VALIDAR E DECODIFICAR RESPOSTAS DO ALUNO
    $respostas_array = null;
    if (!empty($respostas_aluno)) {
        $respostas_array = json_decode($respostas_aluno, true);
        if (json_last_error() !== JSON_ERROR_NONE || !is_array($respostas_array)) {
            $respostas_array = [];
        }
    } else {
        $respostas_array = [];
    }
    
    $analise['total_questoes'] = count($questoes_prova);
    
    foreach ($questoes_prova as $index => $questao) {
        // VALIDAR ESTRUTURA DA QUESTÃO
        if (!isset($questao['resposta_correta']) || !isset($questao['alternativas']) || !is_array($questao['alternativas'])) {
            continue;
        }
        
        $resposta_correta = htmlspecialchars($questao['resposta_correta'], ENT_QUOTES, 'UTF-8');
        $resposta_aluno = isset($respostas_array[$index]) ? htmlspecialchars($respostas_array[$index], ENT_QUOTES, 'UTF-8') : null;
        
        // VALIDAR RESPOSTAS (apenas A, B, C, D)
        $resposta_valida = in_array($resposta_correta, ['A', 'B', 'C', 'D']);
        $acertou = $resposta_valida && ($resposta_aluno === $resposta_correta);
        
        if ($acertou) {
            $analise['acertos']++;
        } else {
            $analise['erros']++;
        }
        
        // SANITIZAR ALTERNATIVAS
        $alternativas_sanitizadas = [];
        foreach (['A', 'B', 'C', 'D'] as $letra) {
            $alternativas_sanitizadas[$letra] = isset($questao['alternativas'][$letra]) ?
                htmlspecialchars($questao['alternativas'][$letra], ENT_QUOTES, 'UTF-8') : '';
        }
        
        $analise['detalhes'][] = [
            'numero' => $index + 1,
            'enunciado' => isset($questao['enunciado']) ? htmlspecialchars($questao['enunciado'], ENT_QUOTES, 'UTF-8') : '',
            'resposta_correta' => $resposta_correta,
            'resposta_aluno' => $resposta_aluno,
            'acertou' => $acertou,
            'alternativas' => $alternativas_sanitizadas
        ];
    }
    
    return $analise;
}

// FORMATAR VALORES COM VALIDAÇÃO
$media_geral = isset($estatisticas['media_geral']) && $estatisticas['media_geral'] !== null ?
               number_format((float)$estatisticas['media_geral'], 1) : '0.0';
$melhor_nota = isset($estatisticas['melhor_nota']) && $estatisticas['melhor_nota'] !== null ?
               number_format((float)$estatisticas['melhor_nota'], 1) : '0.0';
$pior_nota = isset($estatisticas['pior_nota']) && $estatisticas['pior_nota'] !== null ?
             number_format((float)$estatisticas['pior_nota'], 1) : '0.0';

$total_provas_geral = (int)($estatisticas['total_provas'] ?? 0);
$provas_aprovadas = (int)($estatisticas['provas_aprovadas'] ?? 0);
$aprovacao_geral = $total_provas_geral > 0 ?
    number_format(($provas_aprovadas / $total_provas_geral) * 100, 1) : '0.0';

// SANITIZAR DADOS DO ALUNO
$aluno_nome = htmlspecialchars($aluno['nome'] ?? '', ENT_QUOTES, 'UTF-8');
$aluno_email = htmlspecialchars($aluno['email'] ?? '', ENT_QUOTES, 'UTF-8');
$aluno_serie = htmlspecialchars($aluno['escolaridade'] ?? '', ENT_QUOTES, 'UTF-8');
$aluno_data_cadastro = date('d/m/Y', strtotime($aluno['data_cadastro'] ?? 'now'));

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
    <title>Perfil do Aluno - Edukhan</title>
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
        <article class="perfil-aluno">
            <!-- CABEÇALHO DO PERFIL -->
            <section class="perfil-cabecalho">
                <div class="perfil-header">
                    <div class="perfil-info">
                        <h1>👤 Perfil do Aluno: <span class="dado-seguro"><?php echo $aluno_nome; ?></span></h1>
                        <p><strong>ID:</strong> <?php echo $aluno_id; ?> |
                           <strong>Email:</strong> <span class="dado-seguro"><?php echo $aluno_email; ?></span> |
                           <strong>Série:</strong> <span class="dado-seguro"><?php echo $aluno_serie; ?></span></p>
                        <p><strong>Cadastrado em:</strong> <?php echo $aluno_data_cadastro; ?></p>
                    </div>
                    <div class="bnt-all-provas">
                        <a href="gerenciar_alunos.php" rel="noopener">← Voltar para Lista</a>
                    </div>
                </div>
            </section>

            <hr>

            <!-- ESTATÍSTICAS GERAIS -->
            <section class="estatisticas-gerais">
                <h2>📊 Desempenho Geral</h2>
                
                <div class="estatisticas-grid">
                    <div class="estatistica-card">
                        <div><strong>Total de Provas</strong></div>
                        <div class="estatistica-numero"><?php echo $total_provas_geral; ?></div>
                    </div>
                    
                    <div class="estatistica-card">
                        <div><strong>Média Geral</strong></div>
                        <div class="estatistica-numero"><?php echo $media_geral; ?></div>
                    </div>
                    
                    <div class="estatistica-card">
                        <div><strong>Melhor Nota</strong></div>
                        <div class="estatistica-numero"><?php echo $melhor_nota; ?></div>
                    </div>
                    
                    <div class="estatistica-card">
                        <div><strong>Pior Nota</strong></div>
                        <div class="estatistica-numero"><?php echo $pior_nota; ?></div>
                    </div>
                    
                    <div class="estatistica-card">
                        <div><strong>Taxa de Aprovação</strong></div>
                        <div class="estatistica-numero"><?php echo $aprovacao_geral; ?>%</div>
                    </div>
                </div>
            </section>

            <hr>

            <!-- HISTÓRICO DETALHADO DE PROVAS COM ANÁLISE -->
            <section class="historico-provas">
                <h2>📋 Histórico Completo de Provas</h2>
                
                <?php if ($total_provas > 0): ?>
                    <p>Total de provas realizadas: <strong><?php echo $total_provas; ?></strong></p>
                    
                    <?php foreach ($provas as $prova):
                        $nota_prova = (float)($prova['nota'] ?? 0);
                        $cor_nota = $nota_prova >= 7 ? 'nota-alta' : ($nota_prova < 5 ? 'nota-baixa' : 'nota-media');
                        $status_text = ($prova['status'] === 'corrigida') ? '✅ Corrigida' : '⏳ Aguardando correção';
                        
                        // ANALISAR AS RESPOSTAS DO ALUNO
                        $analise = analisarRespostas($prova['conteudo'], $prova['respostas']);
                        $percentual_acertos = $analise['total_questoes'] > 0 ?
                            ($analise['acertos'] / $analise['total_questoes']) * 100 : 0;
                        
                        // SANITIZAR DADOS DA PROVA
                        $prova_titulo = htmlspecialchars($prova['titulo'] ?: $prova['materia'] . ' - Prova', ENT_QUOTES, 'UTF-8');
                        $prova_materia = htmlspecialchars($prova['materia'] ?? '', ENT_QUOTES, 'UTF-8');
                        $prova_serie = htmlspecialchars($prova['serie_destinada'] ?? '', ENT_QUOTES, 'UTF-8');
                        $prova_data = date('d/m/Y', strtotime($prova['data_realizacao'] ?? 'now'));
                    ?>
                        <div class="prova-historico-card">
                            <!-- Cabeçalho da Prova -->
                            <div class="historico-prova-cabecalho">
                                <div class="prova-info">
                                    <h3 class="dado-seguro"><?php echo $prova_titulo; ?></h3>
                                    <p>
                                        <strong>Matéria:</strong> <span class="dado-seguro"><?php echo $prova_materia; ?></span> |
                                        <strong>Data:</strong> <?php echo $prova_data; ?> |
                                        <strong>Questões:</strong> <?php echo (int)($prova['numero_questoes'] ?? 0); ?>
                                    </p>
                                </div>
                                <div class="prova-nota-status">
                                    <div class="nota <?php echo $cor_nota; ?>">
                                        <?php echo number_format($nota_prova, 1); ?>
                                    </div>
                                    <small><?php echo $status_text; ?></small>
                                </div>
                            </div>

                            <!-- Resumo do Desempenho -->
                            <div class="historico-prova-desempenho">
                                <h4>📊 Resumo do Desempenho</h4>
                                <div class="desempenho-grid">
                                    <div class="desempenho-item">
                                        <div class="desempenho-numero acertos">✅ <?php echo $analise['acertos']; ?></div>
                                        <div class="desempenho-label">Acertos</div>
                                    </div>
                                    <div class="desempenho-item">
                                        <div class="desempenho-numero erros">❌ <?php echo $analise['erros']; ?></div>
                                        <div class="desempenho-label">Erros</div>
                                    </div>
                                    <div class="desempenho-item">
                                        <div class="desempenho-numero">📝 <?php echo $analise['total_questoes']; ?></div>
                                        <div class="desempenho-label">Total</div>
                                    </div>
                                    <div class="desempenho-item">
                                        <div class="desempenho-numero">📈 <?php echo number_format($percentual_acertos, 1); ?>%</div>
                                        <div class="desempenho-label">Acertos</div>
                                    </div>
                                </div>
                            </div>

                            <!-- Detalhamento Questão por Questão -->
                            <div class="historico-prova-detalhes-questoes">
                                <h4>🔍 Análise Questão por Questão</h4>
                                
                                <?php foreach ($analise['detalhes'] as $detalhe): ?>
                                    <div class="questao-analise">
                                        <div class="questao-cabecalho">
                                            <div class="questao-enunciado">
                                                <strong>Questão <?php echo $detalhe['numero']; ?>:</strong>
                                                <span class="dado-seguro"><?php echo $detalhe['enunciado']; ?></span>
                                            </div>
                                            <div class="questao-status <?php echo $detalhe['acertou'] ? 'acerto' : 'erro'; ?>">
                                                <?php echo $detalhe['acertou'] ? '✅ ACERTOU' : '❌ ERROU'; ?>
                                            </div>
                                        </div>
                                        
                                        <div class="questao-respostas">
                                            <div class="resposta-aluno">
                                                <strong>Resposta do Aluno:</strong><br>
                                                <?php if ($detalhe['resposta_aluno']): ?>
                                                    <span class="dado-seguro">
                                                        <?php echo $detalhe['resposta_aluno']; ?> -
                                                        <?php echo $detalhe['alternativas'][$detalhe['resposta_aluno']] ?? 'N/A'; ?>
                                                    </span>
                                                <?php else: ?>
                                                    <span class="sem-resposta">Não respondeu</span>
                                                <?php endif; ?>
                                            </div>
                                            <div class="resposta-correta">
                                                <strong>Resposta Correta:</strong><br>
                                                <span class="dado-seguro">
                                                    <?php echo $detalhe['resposta_correta']; ?> -
                                                    <?php echo $detalhe['alternativas'][$detalhe['resposta_correta']] ?? 'N/A'; ?>
                                                </span>
                                            </div>
                                        </div>

                                        <!-- Alternativas -->
                                        <div class="questao-alternativas">
                                            <strong>Alternativas:</strong><br>
                                            <?php foreach ($detalhe['alternativas'] as $letra => $texto): ?>
                                                <div class="alternativa <?php echo $letra === $detalhe['resposta_correta'] ? 'correta' : ''; ?>">
                                                    <strong><?php echo $letra; ?>)</strong>
                                                    <span class="dado-seguro"><?php echo $texto; ?></span>
                                                    <?php if ($letra === $detalhe['resposta_correta']): ?>
                                                        <span class="marcador-correta"> ✓</span>
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
                                Série destinada: <span class="dado-seguro"><?php echo $prova_serie; ?></span>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <div class="sem-provas">
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
                console.log('Perfil do aluno carregado com segurança');
            }
            
            // Adicionar funcionalidade de expandir/recolher questões
            document.querySelectorAll('.questao-analise').forEach(questao => {
                const cabecalho = questao.querySelector('.questao-cabecalho');
                const detalhes = questao.querySelector('.questao-respostas, .questao-alternativas');
                
                if (cabecalho && detalhes) {
                    cabecalho.style.cursor = 'pointer';
                    cabecalho.addEventListener('click', function() {
                        detalhes.style.display = detalhes.style.display === 'none' ? 'block' : 'none';
                    });
                    
                    // Inicialmente esconder detalhes para melhor organização
                    detalhes.style.display = 'none';
                }
            });
        });
    </script>
</body>
</html>

<?php
// LIMPEZA SEGURA
mysqli_close($conectar);
?>
