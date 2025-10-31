<?php
session_start();

// CORREÇÃO: Verificação consistente com as outras páginas
if (!isset($_SESSION['aluno_identificado'])) {
    echo "<script> 
            alert('Acesso negado! Identifique-se primeiro.');
            location.href = '../index.php';
          </script>";
    exit();
}

// CORREÇÃO: Verificar se o ID da prova foi passado
if (!isset($_GET['id']) || empty($_GET['id'])) {
    echo "<script> 
            alert('Prova não especificada.');
            location.href = 'dashboard_aluno.php';
          </script>";
    exit();
}

$prova_id = $_GET['id'];
$aluno_id = $_SESSION['id_aluno']; // CORREÇÃO: variável de sessão correta
$conectar = mysqli_connect("localhost", "root", "", "projeto_residencia");

// CORREÇÃO: Buscar dados da prova com tratamento de erro
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

// CORREÇÃO: Verificar se o aluno já realizou esta prova
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
    <!-- KaTeX CSS -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/katex@0.16.9/dist/katex.min.css">
</head>
<body>
    <header>
        <nav>
            <div class="logo">Edukhan - Realizando Prova</div>
        </nav>
    </header>

    <main>
        <article>
            <div class="header-info">
                <h1><?php echo htmlspecialchars($prova['titulo'] ?: 'Prova Sem Título'); ?></h1>
                <p><strong>Matéria:</strong> <?php echo htmlspecialchars($prova['materia']); ?></p>
                <p><strong>Número de Questões:</strong> <?php echo count($questoes); ?></p>
                <p><strong>Série Destinada:</strong> <?php echo htmlspecialchars($prova['serie_destinada']); ?></p>
            </div>
            
            <form action="../includes/processa_prova.php" method="POST">
                <input type="hidden" name="prova_id" value="<?php echo $prova_id; ?>">
                
                <?php foreach ($questoes as $index => $questao): ?>
                    <div class="questao">
                        <h3>Questão <?php echo $index + 1; ?></h3>
                        <p><?php echo htmlspecialchars($questao['enunciado']); ?></p>
                        
                        <div class="alternativas">
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

    <!-- KaTeX JS -->
    <script defer src="https://cdn.jsdelivr.net/npm/katex@0.16.9/dist/katex.min.js"></script>
    <script defer src="https://cdn.jsdelivr.net/npm/katex@0.16.9/dist/contrib/auto-render.min.js"></script>
    <script src="../js/math-config.js"></script>

</body>
</html>

<?php mysqli_close($conectar); ?>