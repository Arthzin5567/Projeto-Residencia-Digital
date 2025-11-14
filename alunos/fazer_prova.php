<?php
session_start();

// Verificação consistente com as outras páginas
if (!isset($_SESSION['aluno_identificado'])) {
    echo "<script> 
            alert('Acesso negado! Identifique-se primeiro.');
            location.href = '../index.php';
          </script>";
    exit();
}

// ✅ VALIDAÇÃO SEGURA do ID da prova
if (!isset($_GET['id']) || empty($_GET['id']) || !is_numeric($_GET['id'])) {
    echo "<script> 
            alert('Prova não especificada.');
            location.href = 'dashboard_aluno.php';
          </script>";
    exit();
}

$prova_id = (int)$_GET['id'];
$aluno_id = (int)$_SESSION['id_aluno'];

$host = "localhost";
$user = "root";
$password = "SenhaIrada@2024!";
$database = "projeto_residencia";
$conectar = mysqli_connect($host, $user, $password, $database);

//  Buscar dados da prova 
$sql_prova = "SELECT * FROM Provas WHERE idProvas = ?";
$stmt_prova = mysqli_prepare($conectar, $sql_prova);
mysqli_stmt_bind_param($stmt_prova, "i", $prova_id);
mysqli_stmt_execute($stmt_prova);
$resultado = mysqli_stmt_get_result($stmt_prova);

if (!$resultado || mysqli_num_rows($resultado) == 0) {
    echo "<script> 
            alert('Prova não encontrada.');
            location.href = 'dashboard_aluno.php';
          </script>";
    mysqli_stmt_close($stmt_prova);
    exit();
}

$prova = mysqli_fetch_assoc($resultado);
mysqli_stmt_close($stmt_prova);

//  Buscar imagens da prova 
$sql_imagens = "SELECT numero_questao, caminho_imagem, nome_arquivo
                FROM ImagensProvas
                WHERE idProva = ?
                ORDER BY numero_questao, idImagem";
$stmt_imagens = mysqli_prepare($conectar, $sql_imagens);
mysqli_stmt_bind_param($stmt_imagens, "i", $prova_id);
mysqli_stmt_execute($stmt_imagens);
$resultado_imagens = mysqli_stmt_get_result($stmt_imagens);
$imagens_por_questao = [];

if ($resultado_imagens) {
    while ($imagem = mysqli_fetch_assoc($resultado_imagens)) {
        $imagens_por_questao[$imagem['numero_questao']][] = $imagem;
    }
}
mysqli_stmt_close($stmt_imagens);

$base_url = 'http://' . $_SERVER['HTTP_HOST'] . '/projeto_residencia/Projeto-Residencia-Digital/';

// Verificar se aluno já realizou a prova 
$sql_verifica = "SELECT status FROM Aluno_Provas
                 WHERE Aluno_idAluno = ? AND Provas_idProvas = ?";
$stmt_verifica = mysqli_prepare($conectar, $sql_verifica);
mysqli_stmt_bind_param($stmt_verifica, "ii", $aluno_id, $prova_id);
mysqli_stmt_execute($stmt_verifica);
$result_verifica = mysqli_stmt_get_result($stmt_verifica);

if ($result_verifica && mysqli_num_rows($result_verifica) > 0) {
    $status_prova = mysqli_fetch_assoc($result_verifica)['status'];
    if ($status_prova === 'realizada' || $status_prova === 'corrigida') {
        echo "<script> 
                alert('Você já realizou esta prova.');
                location.href = 'dashboard_aluno.php';
              </script>";
        mysqli_stmt_close($stmt_verifica);
        exit();
    }
}
mysqli_stmt_close($stmt_verifica);

// Decodificar questões
$questoes = json_decode($prova['conteudo'], true);

// CORREÇÃO: Verificar se o conteúdo é válido
if (!is_array($questoes) || empty($questoes)) {
    echo "<script> 
            alert('Erro: Conteúdo da prova inválido.');
            location.href = 'dashboard_aluno.php';
          </script>";
    exit();
}
?>

<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Fazer Prova - Edukhan</title>
    <!-- KaTeX CSS -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/katex@0.16.9/dist/katex.min.css">
    <link rel="stylesheet" href="../css/style.css">
    <style>
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
    </style>
</head>
<body>
    <header>
        <nav>
            <div class="logo">
                <img src="../img/LOGOTIPO 1.avif" alt="logo">
            </div>
        </nav>
    </header>

    <main>
        <article class="fazer-prova">
            <div class="header-info-fazer-prova">
                <h1><?php echo htmlspecialchars($prova['titulo'] ?: 'Prova Sem Título'); ?></h1>
                <p><strong>Matéria:</strong> <?php echo htmlspecialchars($prova['materia']); ?></p>
                <p><strong>Número de Questões:</strong> <?php echo count($questoes); ?></p>
                <p><strong>Série Destinada:</strong> <?php echo htmlspecialchars($prova['serie_destinada']); ?></p>
            </div>
            
            <form action="../includes/processa_prova.php" method="POST" id="form-prova">
                <input type="hidden" name="prova_id" value="<?php echo (int)$prova_id; ?>">
                
                <?php foreach ($questoes as $index => $questao): ?>
                    <div class="questao-fazer-prova">
                        <h3>Questão <?php echo $index + 1; ?></h3>

                        <!-- Exibir imagens da questão, se houver -->
                        <?php $numero_questao = $index + 1; ?>
                        <?php if (isset($imagens_por_questao[$numero_questao]) && !empty($imagens_por_questao[$numero_questao])): ?>
                        <div class="imagens-questao">
                            <?php foreach ($imagens_por_questao[$numero_questao] as $imagem): ?>
                                <div class="imagem-container">
                                    <?php
                                    //  Garantir que o caminho está correto
                                    $caminho_final = $imagem['caminho_imagem'];
                                    
                                    // Debug: Verificar o caminho
                                    error_log("Imagem da prova: " . $caminho_final);
                                    ?>
                                    <img src="<?php echo htmlspecialchars($caminho_final); ?>" 
                                        alt="Imagem da questão <?php echo $numero_questao; ?>"
                                        class="imagem-questao"
                                        onclick="abrirModal('<?php echo htmlspecialchars($caminho_final); ?>')"
                                        style="max-width: 300px; cursor: zoom-in;">
                                    <br>
                                    <small>
                                        <?php echo htmlspecialchars($imagem['nome_arquivo']); ?>
                                        <span style="color: #666; font-size: 0.8em;">(Clique para ampliar)</span>
                                    </small>
                                </div>
                            <?php endforeach; ?>
                        </div>
                        <?php endif; ?>
                        
                        <div class="enunciado-questao">
                            <?php echo nl2br(htmlspecialchars($questao['enunciado'])); ?>
                        </div>
                        
                        <div class="alternativas-fazer-prova">
                            <?php foreach ($questao['alternativas'] as $letra => $texto): ?>
                                <label class="alternativa-label">
                                    <input type="radio" 
                                        name="resposta_<?php echo $index; ?>" 
                                        value="<?php echo htmlspecialchars($letra); ?>" 
                                        required
                                        class="alternativa-input">
                                    <span class="alternativa-texto">
                                        <strong><?php echo htmlspecialchars($letra); ?>)</strong> 
                                        <?php echo htmlspecialchars($texto); ?>
                                    </span>
                                </label>
                            <?php endforeach; ?>
                        </div>
                    </div>
                <?php endforeach; ?>
                
                <div class="actions-prova">
                    <button type="button" onclick="verificarProva()" class="btn-finalizar">
                        📝 Finalizar Prova
                    </button>
                    <button type="button" onclick="window.history.back()" class="btn-voltar">
                        ↩️ Voltar
                    </button>
                </div>
            </form>
        </article>
    </main>

    <!-- Modal para imagens  -->
    <div id="modalImagem" class="modal">
        <div class="modal-content">
            <span class="close-modal" onclick="fecharModal()">&times;</span>
            <img id="imagemModal" src="" alt="Imagem ampliada" class="modal-img">
        </div>
    </div>

    <!-- KaTeX JS -->
    <script defer src="https://cdn.jsdelivr.net/npm/katex@0.16.9/dist/katex.min.js"></script>
    <script defer src="https://cdn.jsdelivr.net/npm/katex@0.16.9/dist/contrib/auto-render.min.js"></script>
    <script src="../js/math-config.js"></script>

    <script>
        // ✅ FUNÇÕES BÁSICAS DO MODAL - VERSÃO SIMPLIFICADA
        function abrirModal(src) {
            console.log('Abrindo modal com:', src);
            const modal = document.getElementById('modalImagem');
            const modalImg = document.getElementById('imagemModal');
            
            if (modal && modalImg) {
                modalImg.src = src;
                modal.style.display = 'block';
                document.body.style.overflow = 'hidden';
                console.log('✅ Modal aberto com sucesso');
            } else {
                console.error('❌ Elementos do modal não encontrados');
            }
        }

        function fecharModal() {
            const modal = document.getElementById('modalImagem');
            if (modal) {
                modal.style.display = 'none';
                document.body.style.overflow = 'auto';
                console.log('✅ Modal fechado');
            }
        }

        // ✅ CONFIGURAÇÃO DOS EVENT LISTENERS
        document.addEventListener('DOMContentLoaded', function() {
            // Fechar modal ao clicar fora
            const modal = document.getElementById('modalImagem');
            if (modal) {
                modal.addEventListener('click', function(e) {
                    if (e.target === this) {
                        fecharModal();
                    }
                });
            }
            
            // Fechar modal com ESC
            document.addEventListener('keydown', function(e) {
                if (e.key === 'Escape') {
                    fecharModal();
                }
            });
            
            // ✅ DEBUG: Verificar se as funções estão carregando
            console.log('✅ Modal functions loaded:', {
                abrirModal: typeof abrirModal,
                fecharModal: typeof fecharModal
            });
        });

        // ✅ FUNÇÃO DE TESTE (opcional - pode remover depois)
        function testarModal() {
            abrirModal('../img/LOGOTIPO 1.avif');
        }

        // Prevenir que o usuário saia da página acidentalmente
        window.addEventListener('beforeunload', function(e) {
            e.preventDefault();
            e.returnValue = 'Você tem certeza que deseja sair? Suas respostas podem ser perdidas.';
        });

        // Remover o aviso quando o formulário for enviado
        document.querySelector('form').addEventListener('submit', function() {
            window.removeEventListener('beforeunload', arguments.callee);
        });
    </script>

</body>
</html>

<?php mysqli_close($conectar); ?>