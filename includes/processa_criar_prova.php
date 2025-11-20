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

require_once __DIR__ . '/../config/funcoes_comuns.php';
$conectar = conectarBanco();

// ✅ VALIDAÇÃO E SANITIZAÇÃO SEGURA
function validarESanitizar($dados, $tipo = 'string') {
    if (empty($dados)) return '';
    
    $dados = trim($dados);
    
    switch ($tipo) {
        case 'int':
            return (int)$dados;
        case 'email':
            return filter_var($dados, FILTER_VALIDATE_EMAIL) ? $dados : '';
        case 'alfanumerico':
            return preg_replace('/[^a-zA-Z0-9áéíóúâêîôûãõçÁÉÍÓÚÂÊÎÔÛÃÕÇ\s\-_\.]/', '', $dados);
        case 'string':
        default:
            // Remove caracteres potencialmente perigosos mas mantém acentuação
            return htmlspecialchars($dados, ENT_QUOTES, 'UTF-8');
    }
}

// ✅ COLETA E VALIDAÇÃO SEGURA DE DADOS
$titulo = validarESanitizar($_POST["titulo"] ?? '', 'alfanumerico');
$materia = validarESanitizar($_POST["materia"] ?? '');
$serie_destinada = validarESanitizar($_POST["serie_destinada"] ?? '');
$numero_questoes = validarESanitizar($_POST["numero_questoes"] ?? 0, 'int');
$professor_id = (int)$_SESSION["idProfessor"];

//  Validação mínima
if (empty($titulo) || $numero_questoes < 1) {
    echo "<script>alert('Dados inválidos!'); location.href='../professores/criar_prova.php';</script>";
    exit();
}

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

/*

// DEBUG: Verificar dados recebidos
echo "<pre>POST data:\n";
print_r($_POST);
echo "</pre>";

// DEBUG: Verificar questões coletadas
echo "<pre>Questões coletadas ($questoes_encontradas de $numero_questoes):\n";
print_r($questoes);
echo "</pre>";

// DEBUG: Verificar JSON
echo "<pre>JSON a ser inserido:\n";
echo $conteudo_json;
echo "</pre>";

*/

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
$conteudo_json = json_encode($questoes, JSON_UNESCAPED_UNICODE);

$data_criacao = date('Y-m-d H:i:s');

// Inserir no banco
$sql = "INSERT INTO Provas (titulo, materia, numero_questoes, conteudo, serie_destinada, data_criacao, Professor_idProfessor, ativa) 
        VALUES (?, ?, ?, ?, ?, ?, ?, 0)";

$stmt = mysqli_prepare($conectar, $sql);
if (!$stmt) {
    error_log("❌ Erro ao preparar statement: " . mysqli_error($conectar));
    $_SESSION['erro_prova'] = "Erro interno do sistema. Tente novamente.";
    header("Location: ../professores/criar_prova.php");
    exit();
}

// titulo (s), materia (s), numero_questoes (i), conteudo (s), serie_destinada (s), data_criacao (s), professor_id (i)
mysqli_stmt_bind_param($stmt, "ssisssi", 
    $titulo, 
    $materia, 
    $numero_questoes, 
    $conteudo_json, 
    $serie_destinada, 
    $data_criacao, 
    $professor_id
);

// Verificar estado da conexão antes do upload
error_log("🔗 Estado da conexão MySQL:");
error_log("   Conexão válida: " . ($conectar ? 'SIM' : 'NÃO'));
if ($conectar) {
    error_log("   Ping: " . (mysqli_ping($conectar) ? 'OK' : 'FALHA'));
    error_log("   Erro: " . mysqli_error($conectar));
}

// Se a conexão estiver problemática, recriar
if (!$conectar || !mysqli_ping($conectar)) {
    error_log("🔄 Reconectando ao banco...");
    mysqli_close($conectar);
    $conectar = conectarBanco();
    
    if (!$conectar) {
        error_log("❌ Falha ao reconectar");
        // Continuar sem upload de imagens
        return;
    }
}

if (mysqli_stmt_execute($stmt)) {
    $prova_id = mysqli_insert_id($conectar);
    
    // ✅ LOG SEGURO - SEM DADOS DO USUÁRIO
    error_log("✅ Prova criada com ID: " . $prova_id);

    // PROCESSAR UPLOAD DE IMAGENS
    $total_imagens = 0;
    for ($i = 1; $i <= $numero_questoes; $i++) {
        $imagens_key = "imagens_$i";
        
        if (isset($_FILES[$imagens_key]) && !empty($_FILES[$imagens_key]['name'][0])) {
            // ✅ LOG SEGURO - APENAS METADADOS, NÃO CONTEÚDO
            error_log("📁 Processando upload para questão $i da prova $prova_id");
            
            $arquivos = $_FILES[$imagens_key];
            $imagensSalvas = fazerUploadImagens($prova_id, $i, $arquivos, $conectar);
            
            if (!empty($imagensSalvas)) {
                $total_imagens += count($imagensSalvas);
                // ✅ LOG SEGURO
                error_log("✅ " . count($imagensSalvas) . " imagem(ns) salva(s) para questão $i");
            }
        }
    }

    // ✅ VINCULAR ALUNOS COM PREPARED STATEMENT
    $sql_alunos = "SELECT idAluno FROM Aluno WHERE escolaridade = ?";
    $stmt_alunos = mysqli_prepare($conectar, $sql_alunos);
    
    if ($stmt_alunos) {
        mysqli_stmt_bind_param($stmt_alunos, "s", $serie_destinada);
        mysqli_stmt_execute($stmt_alunos);
        $resultado = mysqli_stmt_get_result($stmt_alunos);
        
        if ($resultado) {
            $alunos_vinculados = 0;
            while ($aluno = mysqli_fetch_assoc($resultado)) {
                $sql_relacao = "INSERT INTO Aluno_Provas (Aluno_idAluno, Provas_idProvas, status) VALUES (?, ?, 'pendente')";
                $stmt_relacao = mysqli_prepare($conectar, $sql_relacao);
                if ($stmt_relacao) {
                    mysqli_stmt_bind_param($stmt_relacao, "ii", $aluno['idAluno'], $prova_id);
                    if (mysqli_stmt_execute($stmt_relacao)) {
                        $alunos_vinculados++;
                    }
                    mysqli_stmt_close($stmt_relacao);
                }
            }
            
            $_SESSION['sucesso_prova'] = "Prova criada com sucesso! Vinculada a $alunos_vinculados aluno(s).";
            mysqli_stmt_close($stmt_alunos);
            mysqli_stmt_close($stmt);
            mysqli_close($conectar);
            
            header("Location: ../professores/gerenciar_provas.php");
            exit();
        }
        mysqli_stmt_close($stmt_alunos);
    }
    
    $_SESSION['sucesso_prova'] = "Prova criada com sucesso!";
    mysqli_stmt_close($stmt);
    mysqli_close($conectar);
    
    header("Location: ../professores/gerenciar_provas.php");
    exit();
    
} else {
    error_log("❌ Erro ao executar statement: " . mysqli_stmt_error($stmt));
    $_SESSION['erro_prova'] = "Erro ao criar prova no banco de dados.";
    mysqli_stmt_close($stmt);
    mysqli_close($conectar);
    
    header("Location: ../professores/criar_prova.php");
    exit();
}

// ✅ FUNÇÃO DE LOG SEGURO PARA UPLOAD (se necessário)
function logUploadSeguro($prova_id, $questao_numero, $arquivos) {
    // Apenas loga metadados, não o conteúdo ou nomes originais
    $quantidade = count($arquivos['name']);
    $tamanho_total = array_sum($arquivos['size']);
    
    error_log("📤 Upload: Prova $prova_id, Questão $questao_numero, Arquivos: $quantidade, Tamanho total: " . $tamanho_total . " bytes");
}

mysqli_close($conectar);
