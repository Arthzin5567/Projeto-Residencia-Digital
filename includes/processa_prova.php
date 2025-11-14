<?php
session_start();

//  Verificação consistente com as outras páginas
if (!isset($_SESSION['aluno_identificado'])) {
    echo "<script> 
            alert('Acesso negado! Identifique-se primeiro.');
            location.href = '../index.php';
          </script>";
    exit();
}

//  Verificar se o formulário foi submetido
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo "<script> 
            alert('Método de acesso inválido.');
            location.href = '../alunos/dashboard_aluno.php';
          </script>";
    exit();
}

//  Variáveis de sessão consistentes
$aluno_id = $_SESSION['id_aluno'];

//  Verificar se o prova_id foi enviado
if (!isset($_POST['prova_id']) || empty($_POST['prova_id'])) {
    echo "<script> 
            alert('Prova não identificada.');
            location.href = '../alunos/dashboard_aluno.php';
          </script>";
    exit();
}

$prova_id = $_POST['prova_id'];
$host = "localhost";
$user = "root";
$password = "SenhaIrada@2024!";
$database = "projeto_residencia";
$conectar = mysqli_connect($host, $user, $password, $database);

//  Buscar prova com tratamento de erro
$sql_prova = "SELECT * FROM Provas WHERE idProvas = '$prova_id'";
$resultado = mysqli_query($conectar, $sql_prova);

if (!$resultado || mysqli_num_rows($resultado) == 0) {
    echo "<script> 
            alert('Prova não encontrada.');
            location.href = '../alunos/dashboard_aluno.php';
          </script>";
    exit();
}

$prova = mysqli_fetch_assoc($resultado);
$questoes = json_decode($prova['conteudo'], true);

//  Verificar se questões são válidas
if (!is_array($questoes) || empty($questoes)) {
    echo "<script> 
            alert('Erro: Conteúdo da prova inválido.');
            location.href = '../alunos/dashboard_aluno.php';
          </script>";
    exit();
}

$acertos = 0;
$respostas_aluno = [];

// Corrigir cada questão
foreach ($questoes as $index => $questao) {
    $resposta_aluno = $_POST["resposta_$index"] ?? null;
    $respostas_aluno[] = $resposta_aluno;
    
    if ($resposta_aluno === $questao['resposta_correta']) {
        $acertos++;
    }
}

$nota = ($acertos / count($questoes)) * 10;
$nota_formatada = number_format($nota, 1);

//  Verificar se existe registro na tabela Aluno_Provas
$sql_verifica = "SELECT * FROM Aluno_Provas 
                 WHERE Aluno_idAluno = '$aluno_id' AND Provas_idProvas = '$prova_id'";
$result_verifica = mysqli_query($conectar, $sql_verifica);

//  Preparar respostas para SQL
$respostas_json = mysqli_real_escape_string($conectar, json_encode($respostas_aluno));

if (mysqli_num_rows($result_verifica) > 0) {
    // Atualizar registro existente
    $sql_update = "UPDATE Aluno_Provas 
                   SET nota = '$nota', 
                       data_realizacao = CURDATE(), 
                       status = 'realizada', 
                       respostas = '$respostas_json'
                   WHERE Aluno_idAluno = '$aluno_id' AND Provas_idProvas = '$prova_id'";
} else {
    //  Criar novo registro se não existir
    $sql_update = "INSERT INTO Aluno_Provas 
                   (Aluno_idAluno, Provas_idProvas, nota, data_realizacao, status, respostas, observacoes)
                   VALUES 
                   ('$aluno_id', '$prova_id', '$nota', CURDATE(), 'realizada', '$respostas_json', 'Prova realizada com sucesso')";
}

if (mysqli_query($conectar, $sql_update)) {
    echo "<script>
            alert('Prova finalizada com sucesso! Sua nota: $nota_formatada');
            location.href = '../alunos/historico.php';
          </script>";
} else {
    echo "<script>
            alert('Erro ao processar prova: " . addslashes(mysqli_error($conectar)) . "');
            location.href = '../alunos/dashboard_aluno.php';
          </script>";
}

mysqli_close($conectar);