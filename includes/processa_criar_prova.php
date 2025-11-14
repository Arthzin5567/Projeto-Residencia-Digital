<?php
session_start();
if (!isset($_SESSION["logado"]) || $_SESSION["logado"] !== true || $_SESSION["tipo_usuario"] !== "professor") {
    header("Location: ../index.php");
    exit();
}

// ✅ SEGURANÇA MÍNIMA: Validar ID do professor
if (!isset($_SESSION['idProfessor']) || !is_numeric($_SESSION['idProfessor'])) {
    header("Location: ../index.php");
    exit();
}

$host = "localhost";
$user = "root";
$password = "SenhaIrada@2024!";
$database = "projeto_residencia";
$conectar = mysqli_connect($host, $user, $password, $database);

// Coletar dados básicos - Sanitização básica
$titulo = mysqli_real_escape_string($conectar, trim($_POST["titulo"] ?? ''));
$materia = mysqli_real_escape_string($conectar, trim($_POST["materia"] ?? ''));
$serie_destinada = mysqli_real_escape_string($conectar, trim($_POST["serie_destinada"] ?? ''));
$numero_questoes = isset($_POST["numero_questoes"]) ? (int)$_POST["numero_questoes"] : 0;
$professor_id = (int)$_SESSION["idProfessor"];

//  Validação mínima
if (empty($titulo) || $numero_questoes < 1) {
    echo "<script>alert('Dados inválidos!'); location.href='../professores/criar_prova.php';</script>";
    exit();
}

// função de upload de imagens
require_once 'upload_imagens.php';

// DEBUG: Verificar dados recebidos
echo "<pre>POST data:\n";
print_r($_POST);
echo "</pre>";

// Construir array com as questões
$questoes = [];
$questoes_encontradas = 0;

for ($i = 1; $i <= $numero_questoes; $i++) {
    $enunciado_key = "enunciado_$i";
    
    // Processa se o enunciado existir e não estiver vazio
    if (isset($_POST[$enunciado_key]) && !empty(trim($_POST[$enunciado_key]))) {
        // Sanitização sem quebrar o fluxo
        $enunciado = mysqli_real_escape_string($conectar, $_POST[$enunciado_key]);
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

    // PROCESSAR UPLOAD DE IMAGENS
    $total_imagens = 0;
    for ($i = 1; $i <= $numero_questoes; $i++) {
        $imagens_key = "imagens_$i";
        
        if (isset($_FILES[$imagens_key]) && !empty($_FILES[$imagens_key]['name'][0])) {
            error_log("📁 Processando imagens para questão $i...");
            $imagensSalvas = fazerUploadImagens($prova_id, $i, $_FILES[$imagens_key]);
            
            if (!empty($imagensSalvas)) {
                $total_imagens += count($imagensSalvas);
                error_log("✅ " . count($imagensSalvas) . " imagem(ns) salva(s) para a questão $i");
            }
        }
    }
    
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
        echo "<script>alert('Prova criada, mas erro ao vincular alunos.'); location.href='../professores/gerenciar_provas.php';</script>";
    }
} else {
    echo "<script>alert('Erro ao criar prova.'); history.back();</script>";
}

mysqli_close($conectar);