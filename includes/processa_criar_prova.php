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
// require_once 'upload_imagens.php';

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
$conteudo_json = mysqli_real_escape_string($conectar, json_encode($questoes, JSON_UNESCAPED_UNICODE));



// Inserir no banco
$sql = "INSERT INTO Provas (titulo, materia, numero_questoes, conteudo, serie_destinada, data_criacao, Professor_idProfessor) 
        VALUES ('$titulo', '$materia', $numero_questoes, '$conteudo_json', '$serie_destinada', CURDATE(), $professor_id)";

echo "<pre>SQL: $sql</pre>";

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

if (mysqli_query($conectar, $sql)) {
    $prova_id = mysqli_insert_id($conectar);

    // PROCESSAR UPLOAD DE IMAGENS
    error_log("🚀 INICIANDO UPLOAD DE IMAGENS PARA PROVA ID: " . $prova_id);

    $total_imagens = 0;
    for ($i = 1; $i <= $numero_questoes; $i++) {
        $imagens_key = "imagens_$i";
        
        error_log("🔍 Verificando questão $i - chave: $imagens_key");
        
        // Verificar se a chave existe e tem arquivos
        if (!isset($_FILES[$imagens_key]) || empty($_FILES[$imagens_key]['name'][0])) {
            error_log("📭 Nenhum arquivo para questão $i");
            continue;
        }
        
        $arquivos = $_FILES[$imagens_key];
        $quantidade_arquivos = count($arquivos['name']);
        error_log("✅ Encontrados $quantidade_arquivos arquivo(s) para questão $i");
        
        // DEBUG: Log detalhado dos arquivos
        foreach ($arquivos['name'] as $index => $nome) {
            error_log("   📄 Arquivo $index: $nome (Tmp: " . $arquivos['tmp_name'][$index] . ")");
        }
        
        // Chamar função de upload
        $imagensSalvas = fazerUploadImagens($prova_id, $i, $arquivos, $conectar);
        
        if (!empty($imagensSalvas)) {
            $total_imagens += count($imagensSalvas);
            error_log("🎉 " . count($imagensSalvas) . " imagem(ns) salva(s) para questão $i");
            foreach ($imagensSalvas as $imagem) {
                error_log("   💾 Salvo: $imagem");
            }
        } else {
            error_log("❌ Falha no upload para questão $i");
            
            // DEBUG AVANÇADO: Testar manualmente
            testarUploadManualmente($prova_id, $i, $arquivos, $conectar);
        }
    }

    error_log("📊 RESUMO FINAL: $total_imagens imagem(ns) salva(s) no total");

    // Função de debug avançado
    function testarUploadManualmente($prova_id, $questao_numero, $arquivos, $conectar) {
        error_log("🧪 TESTE MANUAL DE UPLOAD:");
        
        // 1. Verificar diretório
        $uploadDir = "../uploads/provas/prova_" . $prova_id . "/";
        error_log("📁 Diretório: $uploadDir");
        error_log("   Existe: " . (is_dir($uploadDir) ? 'SIM' : 'NÃO'));
        error_log("   Pode escrever: " . (is_writable($uploadDir) ? 'SIM' : 'NÃO'));
        
        if (!is_dir($uploadDir)) {
            if (!mkdir($uploadDir, 0755, true)) {
                error_log("❌ Não foi possível criar diretório");
                return;
            }
            error_log("✅ Diretório criado");
        }
        
        // 2. Testar primeiro arquivo
        $tmp_name = $arquivos['tmp_name'][0];
        $nome_arquivo = $arquivos['name'][0];
        
        error_log("📄 Testando arquivo: $nome_arquivo");
        error_log("   Tmp existe: " . (file_exists($tmp_name) ? 'SIM' : 'NÃO'));
        error_log("   Tamanho: " . $arquivos['size'][0]);
        error_log("   Erro: " . $arquivos['error'][0]);
        
        if ($arquivos['error'][0] === UPLOAD_ERR_OK && file_exists($tmp_name)) {
            // Tentar upload manual
            $novo_nome = uniqid() . '_questao_' . $questao_numero . '.jpg';
            $caminho_destino = $uploadDir . $novo_nome;
            
            if (move_uploaded_file($tmp_name, $caminho_destino)) {
                error_log("✅ Upload manual bem-sucedido: $caminho_destino");
                
                // Tentar inserir no banco manualmente
                $caminho_relativo = "uploads/provas/prova_" . $prova_id . "/" . $novo_nome;
                $sql = "INSERT INTO ImagensProvas (idProva, numero_questao, caminho_imagem, nome_arquivo) VALUES (?, ?, ?, ?)";
                $stmt = mysqli_prepare($conectar, $sql);
                
                if ($stmt) {
                    mysqli_stmt_bind_param($stmt, "iiss", $prova_id, $questao_numero, $caminho_relativo, $nome_arquivo);
                    if (mysqli_stmt_execute($stmt)) {
                        error_log("✅ Inserção manual no banco bem-sucedida");
                    } else {
                        error_log("❌ Falha na inserção manual: " . mysqli_stmt_error($stmt));
                    }
                    mysqli_stmt_close($stmt);
                } else {
                    error_log("❌ Falha ao preparar statement: " . mysqli_error($conectar));
                }
            } else {
                error_log("❌ Falha no move_uploaded_file");
                error_log("   Permissões: " . decoct(fileperms($uploadDir)));
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
