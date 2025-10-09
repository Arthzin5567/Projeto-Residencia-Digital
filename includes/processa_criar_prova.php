<?php
session_start();
if (!isset($_SESSION["logado"]) || $_SESSION["logado"] !== true || $_SESSION["tipo_usuario"] !== "professor") {
    header("Location: ../index.php");
    exit();
}

$conectar = mysqli_connect("localhost", "root", "", "projeto_residencia");

// Coletar dados básicos
$titulo = mysqli_real_escape_string($conectar, $_POST["titulo"]);
$materia = mysqli_real_escape_string($conectar, $_POST["materia"]);
$serie_destinada = mysqli_real_escape_string($conectar, $_POST["serie_destinada"]);
$numero_questoes = (int)$_POST["numero_questoes"];
$professor_id = $_SESSION["idProfessor"];

// DEBUG: Verificar dados recebidos
echo "<pre>POST data:\n";
print_r($_POST);
echo "</pre>";

// Construir array com as questões
$questoes = [];
$questoes_encontradas = 0;

for ($i = 1; $i <= $numero_questoes; $i++) {
    $enunciado_key = "enunciado_$i";
    
    if (isset($_POST[$enunciado_key]) && !empty(trim($_POST[$enunciado_key]))) {
        $questoes[] = [
            'enunciado' => $_POST[$enunciado_key],
            'alternativas' => [
                'A' => $_POST["alternativa_a_$i"],
                'B' => $_POST["alternativa_b_$i"],
                'C' => $_POST["alternativa_c_$i"],
                'D' => $_POST["alternativa_d_$i"]
            ],
            'resposta_correta' => $_POST["resposta_correta_$i"]
        ];
        $questoes_encontradas++;
    }
}

// DEBUG: Verificar questões coletadas
echo "<pre>Questões coletadas ($questoes_encontradas de $numero_questoes):\n";
print_r($questoes);
echo "</pre>";

// Se não encontrou questões, criar uma questão padrão para evitar erro
if (empty($questoes)) {
    $questoes[] = [
        'enunciado' => 'Questão padrão - edite esta prova',
        'alternativas' => [
            'A' => 'Alternativa A',
            'B' => 'Alternativa B', 
            'C' => 'Alternativa C',
            'D' => 'Alternativa D'
        ],
        'resposta_correta' => 'A'
    ];
    echo "<p>⚠️ Nenhuma questão encontrada. Usando questão padrão.</p>";
}

// Converter para JSON
$conteudo_json = mysqli_real_escape_string($conectar, json_encode($questoes, JSON_UNESCAPED_UNICODE));

// DEBUG: Verificar JSON
echo "<pre>JSON a ser inserido:\n";
echo $conteudo_json;
echo "</pre>";

// Inserir no banco
$sql = "INSERT INTO Provas (titulo, materia, numero_questoes, conteudo, serie_destinada, data_criacao, Professor_idProfessor) 
        VALUES ('$titulo', '$materia', $numero_questoes, '$conteudo_json', '$serie_destinada', CURDATE(), $professor_id)";

echo "<pre>SQL: $sql</pre>";

if (mysqli_query($conectar, $sql)) {
    $prova_id = mysqli_insert_id($conectar);
    
    // Criar registros para todos os alunos da série
    $sql_alunos = "SELECT idAluno FROM Aluno WHERE escolaridade = '$serie_destinada'";
    $resultado = mysqli_query($conectar, $sql_alunos);
    
    if ($resultado) {
        while ($aluno = mysqli_fetch_assoc($resultado)) {
            $sql_relacao = "INSERT INTO Aluno_Provas (Aluno_idAluno, Provas_idProvas, status) 
                            VALUES ({$aluno['idAluno']}, $prova_id, 'pendente')";
            mysqli_query($conectar, $sql_relacao);
        }
        echo "<script>alert('Prova criada com sucesso!'); location.href='../professores/gerenciar_provas.php';</script>";
    } else {
        echo "<script>alert('Prova criada, mas erro ao vincular alunos: " . mysqli_error($conectar) . "'); location.href='../professores/gerenciar_provas.php';</script>";
    }
} else {
    echo "<script>alert('Erro ao criar prova: " . mysqli_error($conectar) . "'); history.back();</script>";
}

mysqli_close($conectar);
?>