<?php
session_start();
require_once __DIR__ . '/../config/funcoes_comuns.php';

// VERIFICAÇÃO DE SEGURANÇA
if (!isset($_SESSION["logado"]) || $_SESSION["logado"] !== true || $_SESSION["tipo_usuario"] !== "professor") {
    header("Location: ../index.php");
    exit();
}

if (!isset($_SESSION['idProfessor']) || !is_numeric($_SESSION['idProfessor'])) {
    header("Location: ../index.php");
    exit();
}

// VALIDAÇÃO DOS PARÂMETROS
if (!isset($_GET['id']) || !is_numeric($_GET['id']) || !isset($_GET['action'])) {
    $_SESSION['erro_prova'] = "Parâmetros inválidos.";
    header("Location: ../professores/gerenciar_provas.php");
    exit();
}

$prova_id = (int)$_GET['id'];
$action = $_GET['action'];
$professor_id = (int)$_SESSION['idProfessor'];

$conectar = conectarBanco();

// VERIFICAR SE A PROVA PERTENCE AO PROFESSOR
$sql_verificar = "SELECT idProvas FROM Provas WHERE idProvas = ? AND Professor_idProfessor = ?";
$stmt_verificar = mysqli_prepare($conectar, $sql_verificar);
mysqli_stmt_bind_param($stmt_verificar, "ii", $prova_id, $professor_id);
mysqli_stmt_execute($stmt_verificar);
$result_verificar = mysqli_stmt_get_result($stmt_verificar);

if (mysqli_num_rows($result_verificar) === 0) {
    $_SESSION['erro_prova'] = "Prova não encontrada ou você não tem permissão para alterar seu status.";
    mysqli_stmt_close($stmt_verificar);
    mysqli_close($conectar);
    header("Location: ../professores/gerenciar_provas.php");
    exit();
}
mysqli_stmt_close($stmt_verificar);

// ALTERAR STATUS DA PROVA
if ($action === 'ativar') {
    $sql_toggle = "UPDATE Provas SET ativa = 1 WHERE idProvas = ?";
    $success_message = "Prova ativada com sucesso! Agora os alunos podem visualizá-la.";
} elseif ($action === 'desativar') {
    $sql_toggle = "UPDATE Provas SET ativa = 0 WHERE idProvas = ?";
    $success_message = "Prova desativada com sucesso! Os alunos não poderão mais visualizá-la.";
} else {
    $_SESSION['erro_prova'] = "Ação inválida.";
    mysqli_close($conectar);
    header("Location: ../professores/gerenciar_provas.php");
    exit();
}

$stmt_toggle = mysqli_prepare($conectar, $sql_toggle);
mysqli_stmt_bind_param($stmt_toggle, "i", $prova_id);

if (mysqli_stmt_execute($stmt_toggle)) {
    $_SESSION['sucesso_prova'] = $success_message;
} else {
    $_SESSION['erro_prova'] = "Erro ao alterar status da prova: " . mysqli_error($conectar);
}

mysqli_stmt_close($stmt_toggle);
mysqli_close($conectar);

header("Location: ../professores/gerenciar_provas.php");
exit();
