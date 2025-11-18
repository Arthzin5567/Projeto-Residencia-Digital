<?php
session_start();

//  HEADERS DE SEGURANÇA
header("X-Frame-Options: DENY");
header("X-Content-Type-Options: nosniff");
header("X-XSS-Protection: 1; mode=block");
header("Referrer-Policy: strict-origin-when-cross-origin");

//  VALIDAÇÃO RIGOROSA DE SESSÃO
if (!isset($_SESSION["logado"]) || $_SESSION["logado"] !== true || $_SESSION["tipo_usuario"] !== "professor") {
    header("Location: ../index.php?erro=acesso_negado");
    exit();
}

//  VALIDAÇÃO DE ID DO PROFESSOR
if (!isset($_SESSION['idProfessor']) || !is_numeric($_SESSION['idProfessor'])) {
    header("Location: ../index.php?erro=sessao_invalida");
    exit();
}

//  VERIFICAR MÉTODO
if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    header("Location: ../professores/gerenciar_provas.php");
    exit();
}

require_once __DIR__ . '/../config/funcoes_comuns.php';
$conectar = conectarBanco();
if (!$conectar) {
    error_log("Erro de conexão ao editar prova");
    header("Location: ../professores/gerenciar_provas.php?erro=conexao");
    exit();
}

//  CONFIGURAÇÕES DE SEGURANÇA
mysqli_set_charset($conectar, "utf8mb4");

/**
 *  FUNÇÕES PARA FAZER UPLOAD DE IMAGENS
 */

$imagensSalvas = fazerUploadImagens($prova_id, $i, $_FILES[$chave_novas_imagens], $conectar);

//  RECEBER E VALIDAR DADOS DO FORMULÁRIO
$prova_id = isset($_POST['prova_id']) ? (int)$_POST['prova_id'] : 0;
$titulo = isset($_POST['titulo']) ? trim($_POST['titulo']) : '';
$materia = isset($_POST['materia']) ? trim($_POST['materia']) : '';
$serie_destinada = isset($_POST['serie_destinada']) ? trim($_POST['serie_destinada']) : '';
$numero_questoes = isset($_POST['numero_questoes']) ? (int)$_POST['numero_questoes'] : 0;
$professor_id = (int)$_SESSION['idProfessor'];

//  VALIDAÇÃO DE FAIXA PARA DADOS
if ($prova_id <= 0 || $prova_id > 999999) {
    header("Location: ../professores/gerenciar_provas.php?erro=id_invalido");
    exit();
}

if (empty($titulo) || strlen($titulo) > 255) {
    header("Location: ../professores/gerenciar_provas.php?erro=titulo_invalido");
    exit();
}

if ($numero_questoes < 1 || $numero_questoes > 20) {
    header("Location: ../professores/gerenciar_provas.php?erro=questoes_invalido");
    exit();
}

//  VERIFICAR SE A PROVA PERTENCE AO PROFESSOR COM PREPARED STATEMENT
$sql_verificar = "SELECT idProvas FROM Provas WHERE idProvas = ? AND Professor_idProfessor = ? LIMIT 1";
$stmt_verificar = mysqli_prepare($conectar, $sql_verificar);

if (!$stmt_verificar) {
    error_log("Erro ao preparar verificação: " . mysqli_error($conectar));
    header("Location: ../professores/gerenciar_provas.php?erro=erro_sistema");
    exit();
}

mysqli_stmt_bind_param($stmt_verificar, "ii", $prova_id, $professor_id);
mysqli_stmt_execute($stmt_verificar);
$result_verificar = mysqli_stmt_get_result($stmt_verificar);

if (mysqli_num_rows($result_verificar) === 0) {
    mysqli_stmt_close($stmt_verificar);
    header("Location: ../professores/gerenciar_provas.php?erro=prova_nao_encontrada");
    exit();
}
mysqli_stmt_close($stmt_verificar);

//  PROCESSAR REMOÇÃO DE IMAGENS
for ($i = 1; $i <= $numero_questoes; $i++) {
    $chave_remover = "imagens_remover_$i";
    if (isset($_POST[$chave_remover]) && is_array($_POST[$chave_remover])) {
        foreach ($_POST[$chave_remover] as $idImagem) {
            $idImagem = (int)$idImagem;
            
            if ($idImagem > 0) {
                //  BUSCAR INFORMAÇÕES DA IMAGEM COM PREPARED STATEMENT
                $sql_imagem = "SELECT caminho_imagem FROM ImagensProvas WHERE idImagem = ? AND idProva = ? LIMIT 1";
                $stmt_imagem = mysqli_prepare($conectar, $sql_imagem);
                
                if ($stmt_imagem) {
                    mysqli_stmt_bind_param($stmt_imagem, "ii", $idImagem, $prova_id);
                    mysqli_stmt_execute($stmt_imagem);
                    $result_imagem = mysqli_stmt_get_result($stmt_imagem);
                    
                    if ($result_imagem && $imagem = mysqli_fetch_assoc($result_imagem)) {
                        $caminho_arquivo = '../' . $imagem['caminho_imagem'];
                        
                        // Excluir arquivo físico
                        if (file_exists($caminho_arquivo)) {
                            unlink($caminho_arquivo);
                        }
                        
                        //  EXCLUIR REGISTRO DO BANCO COM PREPARED STATEMENT
                        $sql_excluir = "DELETE FROM ImagensProvas WHERE idImagem = ?";
                        $stmt_excluir = mysqli_prepare($conectar, $sql_excluir);
                        
                        if ($stmt_excluir) {
                            mysqli_stmt_bind_param($stmt_excluir, "i", $idImagem);
                            mysqli_stmt_execute($stmt_excluir);
                            mysqli_stmt_close($stmt_excluir);
                        }
                    }
                    mysqli_stmt_close($stmt_imagem);
                }
            }
        }
    }
}

//  CORREÇÃO: MONTAR ARRAY DE QUESTÕES CORRETAMENTE
$questoes = [];
for ($i = 1; $i <= $numero_questoes; $i++) {
    $enunciado = isset($_POST["enunciado_$i"]) ? trim($_POST["enunciado_$i"]) : '';
    $alternativa_a = isset($_POST["alternativa_a_$i"]) ? trim($_POST["alternativa_a_$i"]) : '';
    $alternativa_b = isset($_POST["alternativa_b_$i"]) ? trim($_POST["alternativa_b_$i"]) : '';
    $alternativa_c = isset($_POST["alternativa_c_$i"]) ? trim($_POST["alternativa_c_$i"]) : '';
    $alternativa_d = isset($_POST["alternativa_d_$i"]) ? trim($_POST["alternativa_d_$i"]) : '';
    $resposta_correta = isset($_POST["resposta_correta_$i"]) ? trim($_POST["resposta_correta_$i"]) : 'A';
    
    //  VALIDAR RESPOSTA CORRETA
    if (!in_array($resposta_correta, ['A', 'B', 'C', 'D'])) {
        $resposta_correta = 'A';
    }
    
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

//  CONVERTER PARA JSON COM VALIDAÇÃO
$conteudo_json = json_encode($questoes, JSON_UNESCAPED_UNICODE);
if ($conteudo_json === false) {
    error_log("Erro ao codificar JSON das questões");
    header("Location: ../professores/gerenciar_provas.php?erro=json_invalido");
    exit();
}

//  ATUALIZAR DADOS DA PROVA COM PREPARED STATEMENT
$sql_prova = "UPDATE Provas SET titulo = ?, materia = ?, serie_destinada = ?, numero_questoes = ?, conteudo = ? WHERE idProvas = ?";
$stmt_prova = mysqli_prepare($conectar, $sql_prova);

if (!$stmt_prova) {
    error_log("Erro ao preparar atualização: " . mysqli_error($conectar));
    header("Location: ../professores/gerenciar_provas.php?erro=erro_sistema");
    exit();
}

mysqli_stmt_bind_param($stmt_prova, "sssisi", $titulo, $materia, $serie_destinada, $numero_questoes, $conteudo_json, $prova_id);

if (mysqli_stmt_execute($stmt_prova)) {
    //  PROCESSAR UPLOAD DE NOVAS IMAGENS
    $total_novas_imagens = 0;
    for ($i = 1; $i <= $numero_questoes; $i++) {
        $chave_novas_imagens = "novas_imagens_$i";
        
        if (isset($_FILES[$chave_novas_imagens]) && !empty($_FILES[$chave_novas_imagens]['name'][0])) {
            $imagensSalvas = fazerUploadImagens($prova_id, $i, $_FILES[$chave_novas_imagens], $conectar);
            $total_novas_imagens += count($imagensSalvas);
        }
    }
    
    //  MENSAGEM DE SUCESSO
    $mensagem = "Prova atualizada com sucesso!";
    if ($total_novas_imagens > 0) {
        $mensagem .= " $total_novas_imagens nova(s) imagem(ns) adicionada(s).";
    }
    
    $_SESSION['mensagem_sucesso'] = $mensagem;
    mysqli_stmt_close($stmt_prova);
    mysqli_close($conectar);
    
    header("Location: ../professores/gerenciar_provas.php?sucesso=prova_editada");
    exit();
} else {
    error_log("Erro ao executar atualização: " . mysqli_stmt_error($stmt_prova));
    mysqli_stmt_close($stmt_prova);
    mysqli_close($conectar);
    
    header("Location: ../professores/gerenciar_provas.php?erro=erro_edicao");
    exit();
}
