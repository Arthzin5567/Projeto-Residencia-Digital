<?php
session_start();
// Verificar se é professor
if (!isset($_SESSION["logado"]) || $_SESSION["logado"] !== true || $_SESSION["tipo_usuario"] !== "professor") {
    header("Location: ../index.php");
    exit();
}

$conectar = mysqli_connect("localhost", "root", "", "projeto_residencia");

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Receber dados do formulário
    $prova_id = mysqli_real_escape_string($conectar, $_POST['prova_id']);
    $titulo = mysqli_real_escape_string($conectar, $_POST['titulo']);
    $materia = mysqli_real_escape_string($conectar, $_POST['materia']);
    $serie_destinada = mysqli_real_escape_string($conectar, $_POST['serie_destinada']);
    $numero_questoes = (int)$_POST['numero_questoes'];
    $professor_id = $_SESSION['idProfessor'];

    // Verificar se a prova pertence ao professor
    $sql_verificar = "SELECT idProvas FROM Provas WHERE idProvas = '$prova_id' AND Professor_idProfessor = '$professor_id'";
    $result_verificar = mysqli_query($conectar, $sql_verificar);

    if (mysqli_num_rows($result_verificar) === 0) {
        header("Location: ../professores/gerenciar_provas.php?erro=prova_nao_encontrada");
        exit();
    }

    // Montar array de questões
    $questoes = [];
    for ($i = 1; $i <= $numero_questoes; $i++) {
        $enunciado = mysqli_real_escape_string($conectar, $_POST["enunciado_$i"] ?? '');
        $alternativa_a = mysqli_real_escape_string($conectar, $_POST["alternativa_a_$i"] ?? '');
        $alternativa_b = mysqli_real_escape_string($conectar, $_POST["alternativa_b_$i"] ?? '');
        $alternativa_c = mysqli_real_escape_string($conectar, $_POST["alternativa_c_$i"] ?? '');
        $alternativa_d = mysqli_real_escape_string($conectar, $_POST["alternativa_d_$i"] ?? '');
        $resposta_correta = mysqli_real_escape_string($conectar, $_POST["resposta_correta_$i"] ?? 'A');

        $questoes[] = [
            'enunciado' => $enunciado,
            'alternativas' => [
                'A' => $alternativa_a,
                'B' => $alternativa_b,
                'C' => $alternativa_c,
                'D' => $alternativa_d
            ],
            'resposta_correta' => $resposta_correta
        ];
    }

    $conteudo_json = mysqli_real_escape_string($conectar, json_encode($questoes, JSON_UNESCAPED_UNICODE));

    // Atualizar dados da prova e questões
    $sql_prova = "UPDATE Provas SET titulo = '$titulo', materia = '$materia', serie_destinada = '$serie_destinada', numero_questoes = '$numero_questoes', conteudo = '$conteudo_json' WHERE idProvas = '$prova_id'";

    if (mysqli_query($conectar, $sql_prova)) {
        header("Location: ../professores/gerenciar_provas.php?sucesso=prova_editada");
        exit();
    } else {
        header("Location: ../professores/gerenciar_provas.php?erro=erro_edicao");
        exit();
    }
} else {
    header("Location: ../professores/gerenciar_provas.php");
    exit();
}
?>