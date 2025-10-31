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

// Verificar se o ID da prova foi passado
if (!isset($_GET['id']) || empty($_GET['id'])) {
    echo "<script> 
            alert('Prova não especificada.');
            location.href = 'dashboard_aluno.php';
          </script>";
    exit();
}

$prova_id = $_GET['id'];
$aluno_id = $_SESSION['id_aluno']; // variável de sessão correta
$conectar = mysqli_connect("localhost", "root", "", "projeto_residencia");

// Buscar dados da prova com tratamento de erro
$sql_prova = "SELECT * FROM Provas WHERE idProvas = '$prova_id'";
$resultado = mysqli_query($conectar, $sql_prova);

if (!$resultado || mysqli_num_rows($resultado) == 0) {
    echo "<script> 
            alert('Prova não encontrada.');
            location.href = 'dashboard_aluno.php';
          </script>";
    exit();
}

$prova = mysqli_fetch_assoc($resultado);

// Buscar imagens da prova
$sql_imagens = "SELECT numero_questao, caminho_imagem, nome_arquivo 
                FROM ImagensProvas 
                WHERE idProva = '$prova_id' 
                ORDER BY numero_questao, idImagem";
$resultado_imagens = mysqli_query($conectar, $sql_imagens);
$imagens_por_questao = [];

if ($resultado_imagens) {
    while ($imagem = mysqli_fetch_assoc($resultado_imagens)) {
        $imagens_por_questao[$imagem['numero_questao']][] = $imagem;
    }
}

$base_url = 'http://' . $_SERVER['HTTP_HOST'] . '/projeto_residencia/Projeto-Residencia-Digital/';

// Verificar se o aluno já realizou esta prova
$sql_verifica = "SELECT status FROM Aluno_Provas 
                 WHERE Aluno_idAluno = '$aluno_id' AND Provas_idProvas = '$prova_id'";
$result_verifica = mysqli_query($conectar, $sql_verifica);

if ($result_verifica && mysqli_num_rows($result_verifica) > 0) {
    $status_prova = mysqli_fetch_assoc($result_verifica)['status'];
    if ($status_prova === 'realizada' || $status_prova === 'corrigida') {
        echo "<script> 
                alert('Você já realizou esta prova.');
                location.href = 'dashboard_aluno.php';
              </script>";
        exit();
    }
}

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
    <link rel="stylesheet" href="../css/style.css">
</head>
<body>
    <header>
        <nav>
            <div class="logo">Edukhan - Realizando Prova</div>
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
            
            <form action="../includes/processa_prova.php" method="POST">
                <input type="hidden" name="prova_id" value="<?php echo $prova_id; ?>">
                
                <?php foreach ($questoes as $index => $questao): ?>
                    <div class="questao-fazer-prova">
                        <h3>Questão <?php echo $index + 1; ?></h3>

                        <!-- Exibir imagens da questão, se houver -->
                        <?php $numero_questao = $index + 1; ?>
                        <?php if (isset($imagens_por_questao[$numero_questao]) && !empty($imagens_por_questao[$numero_questao])): ?>
                            <div class="imagens-questao">
                                <div>
                                    <?php foreach ($imagens_por_questao[$numero_questao] as $imagem): ?>
                                        <div class="imagem-container">
                                            <?php
                                            // DEBUG: Verificar o caminho da imagem
                                            $caminho_imagem = $imagem['caminho_imagem'];
                                            $caminho_completo = "../" . $caminho_imagem;
                                            
                                            // Verificar se o arquivo existe
                                            if (!file_exists($caminho_completo)) {
                                                error_log("Arquivo de imagem não encontrado: " . $caminho_completo);
                                            }
                                            ?>
                                            <img src="../Projeto-Residencia-Digital/<?php echo htmlspecialchars($imagem['caminho_imagem']); ?>" 
                                                 alt="Imagem da questão <?php echo $numero_questao; ?>"
                                                 class="imagem-questao"
                                                 onclick="abrirModal('../Projeto-Residencia-Digital/<?php echo htmlspecialchars($imagem['caminho_imagem']); ?>')"
                                                 >
                                            <br>
                                            <small>
                                                <?php echo htmlspecialchars($imagem['nome_arquivo']); ?>
                                            </small>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                        <?php endif; ?>
                        
                        <p><?php echo htmlspecialchars($questao['enunciado']); ?></p>
                        
                        <div class="alternativas-fazer-prova">
                            <?php foreach ($questao['alternativas'] as $letra => $texto): ?>
                                <label>
                                    <input type="radio" name="resposta_<?php echo $index; ?>" value="<?php echo $letra; ?>" required>
                                    <strong><?php echo $letra; ?>)</strong> <?php echo htmlspecialchars($texto); ?>
                                </label>
                            <?php endforeach; ?>
                        </div>
                    </div>
                <?php endforeach; ?>
                
                <div>
                    <button type="submit" onclick="return confirm('Tem certeza que deseja finalizar a prova?')">
                        📝 Finalizar Prova
                    </button>
                </div>
            </form>
        </article>
    </main>

    <!-- Modal para visualização ampliada de imagens -->
    <div id="modalImagem" class="modal">
        <div class="modal-content">
            <img id="imagemModal" src="" alt="Imagem ampliada" class="modal-img">
            <button class="close-modal" onclick="fecharModal()">Fechar</button>
        </div>
    </div>

     <script>
        // Funções para o modal de imagens
        function abrirModal(src) {
            document.getElementById('imagemModal').src = src;
            document.getElementById('modalImagem').style.display = 'flex';
        }

        function fecharModal() {
            document.getElementById('modalImagem').style.display = 'none';
        }

        // Fechar modal ao clicar fora da imagem
        document.getElementById('modalImagem').addEventListener('click', function(e) {
            if (e.target === this) {
                fecharModal();
            }
        });

        // Fechar modal com tecla ESC
        document.addEventListener('keydown', function(e) {
            if (e.key === 'Escape') {
                fecharModal();
            }
        });

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