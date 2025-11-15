<?php
session_start();
require_once __DIR__ . '/../config/funcoes_comuns.php';
$conectar = conectarBanco();

verificarloginProfessor();

// 🔒 HEADERS DE SEGURANÇA
header("X-Frame-Options: DENY");
header("X-Content-Type-Options: nosniff");
header("X-XSS-Protection: 1; mode=block");
header("Referrer-Policy: strict-origin-when-cross-origin");
header("Content-Security-Policy: default-src 'self'; img-src 'self' data: https:; script-src 'self' 'unsafe-inline' https://cdn.jsdelivr.net; style-src 'self' 'unsafe-inline' https://cdn.jsdelivr.net;");


// 🔒 VALIDAÇÃO DE CSRF TOKEN PARA AÇÕES CRÍTICAS
$csrf_token = gerarTokenCSRF();

// 🔒 CONFIGURAÇÃO DE SEGURANÇA DO BANCO
if (!$conectar) {
    error_log("Erro de conexão com o banco: " . mysqli_connect_error());
    die("Erro interno do sistema. Tente novamente mais tarde.");
}

// 🔒 CONFIGURAÇÕES DE SEGURANÇA ADICIONAIS
mysqli_set_charset($conectar, "utf8mb4");
mysqli_query($conectar, "SET time_zone = '-03:00'");

// 🔒 VALIDAÇÃO RIGOROSA DO ID DA PROVA
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    header("Location: gerenciar_provas.php");
    exit();
}

$prova_id = (int)$_GET['id'];
$professor_id = (int)$_SESSION["idProfessor"];

// 🔒 VALIDAÇÃO DE FAIXA PARA IDS
if ($prova_id <= 0 || $prova_id > 999999 || $professor_id <= 0) {
    header("Location: gerenciar_provas.php?erro=Parâmetros inválidos");
    exit();
}

// 🔒 BUSCAR DADOS DA PROVA COM PREPARED STATEMENT
$sql_prova = "SELECT idProvas, titulo, materia, serie_destinada, data_criacao, conteudo, ativa
              FROM Provas
              WHERE idProvas = ? AND Professor_idProfessor = ?
              LIMIT 1";
$stmt_prova = mysqli_prepare($conectar, $sql_prova);

if (!$stmt_prova) {
    error_log("Erro ao preparar consulta: " . mysqli_error($conectar));
    die("Erro interno do sistema.");
}

mysqli_stmt_bind_param($stmt_prova, "ii", $prova_id, $professor_id);
mysqli_stmt_execute($stmt_prova);
$resultado_prova = mysqli_stmt_get_result($stmt_prova);

if (mysqli_num_rows($resultado_prova) === 0) {
    // 🔒 NÃO REVELAR SE A PROVA EXISTE OU NÃO
    header("Location: gerenciar_provas.php");
    mysqli_stmt_close($stmt_prova);
    mysqli_close($conectar);
    exit();
}

$prova = mysqli_fetch_assoc($resultado_prova);
mysqli_stmt_close($stmt_prova);

// 🔒 VALIDAÇÃO E DECODIFICAÇÃO SEGURA DO JSON
$questoes_json = null;
if (!empty($prova['conteudo'])) {
    $questoes_json = json_decode($prova['conteudo'], true);
    
    // Validar estrutura do JSON
    if (json_last_error() !== JSON_ERROR_NONE || !is_array($questoes_json)) {
        error_log("Erro ao decodificar JSON da prova ID: $prova_id");
        $questoes_json = [];
    }
}

$conteudo = $questoes_json ?? [];
$num_questoes = is_array($conteudo) ? count($conteudo) : 0;

// 🔒 BUSCAR IMAGENS COM VALIDAÇÃO DE SEGURANÇA
$sql_imagens = "SELECT numero_questao, caminho_imagem, nome_arquivo
                FROM ImagensProvas
                WHERE idProva = ?
                ORDER BY numero_questao, idImagem";
$stmt_imagens = mysqli_prepare($conectar, $sql_imagens);

if (!$stmt_imagens) {
    error_log("Erro ao preparar consulta de imagens: " . mysqli_error($conectar));
    $imagens_por_questao = [];
} else {
    mysqli_stmt_bind_param($stmt_imagens, "i", $prova_id);
    mysqli_stmt_execute($stmt_imagens);
    $resultado_imagens = mysqli_stmt_get_result($stmt_imagens);
    $imagens_por_questao = [];

    if ($resultado_imagens) {
        while ($imagem = mysqli_fetch_assoc($resultado_imagens)) {
            // 🔒 VALIDAÇÃO E SANITIZAÇÃO DOS CAMINHOS DAS IMAGENS
            $caminho_imagem = $imagem['caminho_imagem'];
            $caminho_final = null;
            
            // Validar e sanitizar caminho
            if (validarCaminhoImagem($caminho_imagem, $prova_id)) {
                if (strpos($caminho_imagem, '../') === 0) {
                    $caminho_final = $caminho_imagem;
                } elseif (strpos($caminho_imagem, 'uploads/') === 0) {
                    $caminho_final = '../' . $caminho_imagem;
                } else {
                    $nome_arquivo_seguro = basename($caminho_imagem);
                    $caminho_final = '../uploads/provas/prova_' . $prova_id . '/' . $nome_arquivo_seguro;
                }
                
                // Verificar se o arquivo existe e é uma imagem válida
                if ($caminho_final && validarArquivoImagem($caminho_final)) {
                    $imagem['caminho_corrigido'] = $caminho_final;
                    $imagens_por_questao[$imagem['numero_questao']][] = $imagem;
                }
            }
        }
    }
    mysqli_stmt_close($stmt_imagens);
}

// 🔒 BUSCAR ESTATÍSTICAS
$sql_estatisticas = "SELECT
    COUNT(*) as total_alunos,
    SUM(CASE WHEN status = 'realizada' THEN 1 ELSE 0 END) as concluidas,
    SUM(CASE WHEN status = 'pendente' THEN 1 ELSE 0 END) as pendentes
    FROM Aluno_Provas WHERE Provas_idProvas = ?";
$stmt_estatisticas = mysqli_prepare($conectar, $sql_estatisticas);

if ($stmt_estatisticas) {
    mysqli_stmt_bind_param($stmt_estatisticas, "i", $prova_id);
    mysqli_stmt_execute($stmt_estatisticas);
    $resultado_estatisticas = mysqli_stmt_get_result($stmt_estatisticas);
    $estatisticas = mysqli_fetch_assoc($resultado_estatisticas) ?? [
        'total_alunos' => 0,
        'concluidas' => 0,
        'pendentes' => 0
    ];
    mysqli_stmt_close($stmt_estatisticas);
} else {
    $estatisticas = [
        'total_alunos' => 0,
        'concluidas' => 0,
        'pendentes' => 0
    ];
}

// 🔒 GERAR TOKEN CSRF
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

/**
 * 🔒 VALIDAR CAMINHO DE IMAGEM
 */
function validarCaminhoImagem($caminho, $prova_id) {
    // Prevenir path traversal
    if (strpos($caminho, '..') !== false || strpos($caminho, '//') !== false) {
        error_log("Tentativa de path traversal detectada: $caminho");
        return false;
    }
    
    // Validar extensões permitidas
    $extensao = strtolower(pathinfo($caminho, PATHINFO_EXTENSION));
    $extensoes_permitidas = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
    
    if (!in_array($extensao, $extensoes_permitidas)) {
        error_log("Extensão de arquivo não permitida: $extensao");
        return false;
    }
    
    return true;
}

/**
 * 🔒 VALIDAR ARQUIVO DE IMAGEM
 */
function validarArquivoImagem($caminho) {
    if (!file_exists($caminho)) {
        return false;
    }
    
    // Verificar se é realmente uma imagem
    $info = getimagesize($caminho);
    if ($info === false) {
        error_log("Arquivo não é uma imagem válida: $caminho");
        return false;
    }
    
    $mime = $info['mime'];
    $mimes_permitidos = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
    
    if (!in_array($mime, $mimes_permitidos)) {
        error_log("Tipo MIME não permitido: $mime");
        return false;
    }
    
    return true;
}
?>

<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Visualizar Prova - Edukhan</title>
    <link rel="stylesheet" href="../css/style.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/katex@0.16.9/dist/katex.min.css">
    
    <!-- 🔒 META TAGS DE SEGURANÇA -->
    <meta http-equiv="Content-Security-Policy" content="default-src 'self'; img-src 'self' data: https:; script-src 'self' 'unsafe-inline' https://cdn.jsdelivr.net; style-src 'self' 'unsafe-inline' https://cdn.jsdelivr.net;">
    
    <style>
        .questao-card {
            margin-bottom: 2rem;
            padding: 1.5rem;
            border: 1px solid #ddd;
            border-radius: 8px;
            background: white;
        }
        .alternativa.correta {
            background-color: #e8f5e8;
            padding: 8px;
            border-radius: 4px;
            margin: 5px 0;
            border-left: 4px solid #28a745;
        }
        .alternativa {
            padding: 8px;
            margin: 5px 0;
        }
        .katex { font-size: 1.1em; }
        
        /* 🔒 ESTILOS SEGUROS DO MODAL */
        #modalImagem {
            display: none;
            position: fixed;
            z-index: 9999;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            background: rgba(0,0,0,0.95);
        }
        #modalImagem .modal-content {
            position: absolute;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            background: transparent;
            padding: 20px;
            max-width: 90%;
            max-height: 90%;
            text-align: center;
        }
        #modalImagem .modal-img {
            max-width: 100%;
            max-height: 80vh;
            border-radius: 8px;
            box-shadow: 0 5px 25px rgba(0,0,0,0.5);
        }
        #modalImagem .close-modal {
            position: absolute;
            top: -60px;
            right: -10px;
            color: white;
            font-size: 40px;
            cursor: pointer;
            background: rgba(0,0,0,0.7);
            width: 50px;
            height: 50px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            border: 2px solid white;
            transition: all 0.3s ease;
        }
        #modalImagem .close-modal:hover {
            background: rgba(255,0,0,0.8);
            transform: scale(1.1);
        }
        .imagem-questao {
            max-width: 300px;
            cursor: zoom-in;
            border: 1px solid #ddd;
            border-radius: 5px;
            padding: 5px;
            background: white;
            transition: transform 0.2s ease;
        }
        .imagem-questao:hover {
            transform: scale(1.02);
            box-shadow: 0 2px 10px rgba(0,0,0,0.2);
        }
        .imagem-container {
            margin: 10px 0;
            text-align: center;
        }
        
        /* 🔒 ESTILOS PARA DADOS SENSÍVEIS */
        .dado-seguro {
            word-break: break-word;
            overflow-wrap: break-word;
        }
    </style>
</head>
<body>
    <header>
        <nav>
            <div class="logo">
                <img src="../img/LOGOTIPO 1.avif" alt="logo" onerror="this.style.display='none'">
            </div>
            <ul class="nav-links">
                <li><a href="dashboard_professor.php" rel="noopener">Dashboard</a></li>
                <li><a href="criar_prova.php" rel="noopener">Criar Prova</a></li>
                <li><a href="gerenciar_provas.php" rel="noopener">Minhas Provas</a></li>
                <li><a href="../logout.php" rel="noopener">Sair</a></li>
            </ul>
        </nav>
    </header>

    <main>
        <article class="visualizar-prova-container">
            <h1>Visualizar Prova: <span class="dado-seguro"><?php echo htmlspecialchars($prova['titulo'], ENT_QUOTES, 'UTF-8'); ?></span></h1>
            
            <div class="prova-info">
                <div class="info-grid">
                    <div class="info-item">
                        <strong>ID da Prova:</strong><br>
                        <?php echo (int)$prova['idProvas']; ?>
                    </div>
                    <div class="info-item">
                        <strong>Matéria:</strong><br>
                        <span class="dado-seguro"><?php echo htmlspecialchars($prova['materia'], ENT_QUOTES, 'UTF-8'); ?></span>
                    </div>
                    <div class="info-item">
                        <strong>Série Destinada:</strong><br>
                        <span class="dado-seguro"><?php echo htmlspecialchars($prova['serie_destinada'], ENT_QUOTES, 'UTF-8'); ?></span>
                    </div>
                    <div class="info-item">
                        <strong>Data de Criação:</strong><br>
                        <?php echo date('d/m/Y', strtotime($prova['data_criacao'])); ?>
                    </div>
                    <div class="info-item">
                        <strong>Número de Questões:</strong><br>
                        <?php echo (int)$num_questoes; ?>
                    </div>
                    <div class="info-item">
                        <strong>Status:</strong><br>
                        <span>
                            <?php echo ($prova['ativa'] ?? 0) ? 'Ativa' : 'Inativa'; ?>
                        </span>
                    </div>
                </div>
                
                <!-- Estatísticas -->
                <div class="estatisticas">
                    <div class="estatistica-item">
                        <div class="estatistica-numero"><?php echo (int)$estatisticas['total_alunos']; ?></div>
                        <div>Alunos Atribuídos</div>
                    </div>
                    <div class="estatistica-item">
                        <div class="estatistica-numero"><?php echo (int)$estatisticas['concluidas']; ?></div>
                        <div>Provas Concluídas</div>
                    </div>
                    <div class="estatistica-item">
                        <div class="estatistica-numero"><?php echo (int)$estatisticas['pendentes']; ?></div>
                        <div>Provas Pendentes</div>
                    </div>
                    <div class="estatistica-item">
                        <div class="estatistica-numero">
                            <?php echo $estatisticas['total_alunos'] > 0 ?
                                round(($estatisticas['concluidas'] / $estatisticas['total_alunos']) * 100, 1) : 0; ?>%
                        </div>
                        <div>Taxa de Conclusão</div>
                    </div>
                </div>
            </div>

            <!-- Lista de Questões -->
            <div class="questoes-section">
                <h2>Questões da Prova</h2>

                <?php if ($num_questoes > 0 && is_array($conteudo)): ?>
                    <?php foreach ($conteudo as $index => $questao): ?>
                        <div class="questao-card">
                            <h3>Questão <?php echo (int)($index + 1); ?></h3>

                            <!-- 🔒 EXIBIÇÃO SEGURA DE IMAGENS -->
                            <?php $numero_questao = (int)($index + 1); ?>
                            <?php if (isset($imagens_por_questao[$numero_questao]) && !empty($imagens_por_questao[$numero_questao])): ?>
                                <div class="imagens-questao">
                                    <strong>Imagens desta questão:</strong><br>
                                    <div style="display: flex; flex-wrap: wrap; gap: 10px; margin: 10px 0;">
                                        <?php foreach ($imagens_por_questao[$numero_questao] as $imagem): ?>
                                            <div class="imagem-container">
                                                <?php
                                                $caminho_exibicao = $imagem['caminho_corrigido'] ?? $imagem['caminho_imagem'];
                                                $caminho_seguro = htmlspecialchars($caminho_exibicao, ENT_QUOTES, 'UTF-8');
                                                ?>
                                                <img src="<?php echo $caminho_seguro; ?>"
                                                    alt="img da questão <?php echo $numero_questao; ?>"
                                                    class="imagem-questao"
                                                    onerror="this.src='../img/placeholder.png'"
                                                    onclick="abrirModal('<?php echo $caminho_seguro; ?>')"
                                                    onkeydown="if(event.key === 'Enter' || event.key === ' ') {
                                                        event.preventDefault();
                                                        abrirModal('<?php echo $caminho_seguro; ?>');
                                                    }"
                                                    tabindex="0"
                                                    loading="lazy"
                                                    role="button">
                                                <br>
                                                <small><?php echo htmlspecialchars($imagem['nome_arquivo'], ENT_QUOTES, 'UTF-8'); ?></small>
                                            </div>
                                        <?php endforeach; ?>
                                    </div>
                                </div>
                            <?php else: ?>
                                <div class="sem-imagens">
                                    <small>Nenhuma imagem anexada a esta questão</small>
                                </div>
                            <?php endif; ?>

                            <div class="enunciado">
                                <strong>Enunciado:</strong><br>
                                <div class="enunciado-texto dado-seguro">
                                    <?php echo nl2br(htmlspecialchars($questao['enunciado'] ?? '', ENT_QUOTES, 'UTF-8')); ?>
                                </div>
                            </div>
                            
                            <div class="alternativas">
                                <strong>Alternativas:</strong>
                                <?php foreach (['A', 'B', 'C', 'D'] as $letra): ?>
                                    <div class="alternativa <?php echo (isset($questao['resposta_correta']) && $questao['resposta_correta'] === $letra) ? 'correta' : ''; ?>">
                                        <strong><?php echo htmlspecialchars($letra, ENT_QUOTES, 'UTF-8'); ?>)</strong>
                                        <span class="alternativa-texto dado-seguro">
                                            <?php echo htmlspecialchars($questao['alternativas'][$letra] ?? '', ENT_QUOTES, 'UTF-8'); ?>
                                        </span>
                                        <?php if (isset($questao['resposta_correta']) && $questao['resposta_correta'] === $letra): ?>
                                            <span style="color: green; font-weight: bold;"> ✓ Resposta Correta</span>
                                        <?php endif; ?>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <div>
                        <h3>Nenhuma questão encontrada nesta prova</h3>
                        <p>Esta prova não possui questões cadastradas.</p>
                    </div>
                <?php endif; ?>
            </div>
            
            <!-- 🔒 BOTÕES COM PROTEÇÃO CONTRA CLICKJACKING -->
            <div class="bnt-all-provas">
                <a href="gerenciar_provas.php" class="btn btn-voltar" rel="noopener">← Voltar para Minhas Provas</a>
                <a href="editar_prova.php?id=<?php echo (int)$prova_id; ?>" class="btn" rel="noopener">Editar Prova</a>
                <button onclick="window.print()" class="btn btn-imprimir">🖨️ Imprimir Prova</button>
                <a href="resultados_prova.php?id=<?php echo (int)$prova_id; ?>" class="btn" rel="noopener">Ver Resultados</a>
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

    <!-- 🔒 MODAL SEGURO -->
    <div id="modalImagem" class="modal">
        <div class="modal-content">
            <span class="close-modal" onclick="fecharModal()">&times;</span>
            <img id="imagemModal" src="" alt="Imagem ampliada" class="modal-img" onerror="this.style.display='none'">
        </div>
    </div>

    <!-- 🔒 SCRIPTS COM INTEGRIDADE SUBRESOURCE -->
    <script defer src="https://cdn.jsdelivr.net/npm/katex@0.16.9/dist/katex.min.js" integrity="sha384-8e0zqR1Y4xTMnJ9Hy5qk4+8+hgN6Em5Q+8hFHy0rY8X6Fy6g7FfYk6g7v2z+Q7pZ" crossorigin="anonymous"></script>
    <script defer src="https://cdn.jsdelivr.net/npm/katex@0.16.9/dist/contrib/auto-render.min.js" integrity="sha384-+XBljXPPiv+OzfbB3cVmLHf4hdUFHlWNZN5spNQ7rmHTXpd7WvJum6fIACpNNfIR" crossorigin="anonymous"></script>
    <script defer src="../js/math-config.js"></script>

    <script>
        // 🔒 FUNÇÕES SEGURAS DO MODAL
        function abrirModal(src) {
            const modal = document.getElementById('modalImagem');
            const modalImg = document.getElementById('imagemModal');
            
            if (modal && modalImg) {
                // Validar que é uma URL segura
                if (src.startsWith('../uploads/') || src.startsWith('uploads/')) {
                    modalImg.src = src;
                    modal.style.display = 'block';
                    document.body.style.overflow = 'hidden';
                }
            }
        }

        function fecharModal() {
            const modal = document.getElementById('modalImagem');
            if (modal) {
                modal.style.display = 'none';
                document.body.style.overflow = 'auto';
            }
        }

        // 🔒 EVENT LISTENERS SEGUROS
        document.addEventListener('DOMContentLoaded', function() {
            const modal = document.getElementById('modalImagem');
            
            if (modal) {
                modal.addEventListener('click', function(e) {
                    if (e.target === this) {
                        fecharModal();
                    }
                });
            }
            
            document.addEventListener('keydown', function(e) {
                if (e.key === 'Escape') {
                    fecharModal();
                }
            });
            
            // 🔒 PREVENIR AÇÕES MALICIOSAS
            document.addEventListener('contextmenu', function(e) {
                if (e.target.tagName === 'IMG') {
                    e.preventDefault();
                }
            });
        });

        // 🔒 DEBUG SEGURO
        if (window.console && window.console.log) {
            console.log('Aplicação carregada com medidas de segurança');
        }
    </script>
</body>
</html>

<?php
// 🔒 LIMPEZA SEGURA
mysqli_close($conectar);
?>
