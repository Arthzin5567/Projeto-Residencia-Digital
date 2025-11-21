<?php
/**
 * FUNÇÕES COMUNS PARA REDUZIR DUPLICAÇÃO
 */

// 1. CONEXÃO COM BANCO
function conectarBanco() {
    require_once __DIR__ . '/../config/database_config.php';
    
    $conexao = mysqli_connect(
        $db_config['host'],
        $db_config['user'],
        $db_config['password'],
        $db_config['database']
    );
    
    if ($conexao) {
        mysqli_set_charset($conexao, "utf8mb4");
        mysqli_query($conexao, "SET time_zone = '-03:00'");
    }
    
    return $conexao;
}

// 2. VALIDAÇÃO DE IMAGEM
function validarEProcessarImagem($arquivoTmp, $tipoMime, $tamanho) {
    $allowedTypes = ['image/jpeg', 'image/png', 'image/gif', 'image/jpg', 'image/webp'];
    
    if (!in_array($tipoMime, $allowedTypes)) {
        return ['sucesso' => false, 'erro' => 'Tipo de arquivo não permitido'];
    }
    
    if ($tamanho > 2 * 1024 * 1024) {
        return ['sucesso' => false, 'erro' => 'Arquivo muito grande'];
    }
    
    return ['sucesso' => true];
}

// 3. VALIDAÇÃO DE CSRF
function validarCSRF() {
    if ($_SERVER["REQUEST_METHOD"] === "POST" &&
        (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token'])) {
        die("Token CSRF inválido");
    }
}

function gerarTokenCSRF() {
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

function verificarLoginProfessor() {
    // VERIFICA SE O PROFESSOR ESTÁ LOGADO (SEU SISTEMA ORIGINAL)
    if (!isset($_SESSION["logado"]) || $_SESSION["logado"] !== true || $_SESSION["tipo_usuario"] !== "professor") {
        header("Location: ../index.php?erro=acesso_negado");
        exit();
    }
    
    // VALIDAÇÃO DO ID DO PROFESSOR (DO SEU CÓDIGO ORIGINAL)
    if (!isset($_SESSION['idProfessor']) || !is_numeric($_SESSION['idProfessor'])) {
        session_destroy();
        header("Location: ../index.php?erro=sessao_invalida");
        exit();
    }
    
    $professor_id = (int)$_SESSION['idProfessor'];
    
    // VALIDAÇÃO DE FAIXA PARA ID (DO SEU CÓDIGO ORIGINAL)
    if ($professor_id <= 0 || $professor_id > 999999) {
        session_destroy();
        header("Location: ../index.php?erro=id_invalido");
        exit();
    }
    
    return $professor_id;
}

function verificarLoginAluno() {
    // USA SEU CRITÉRIO ORIGINAL
    if (!isset($_SESSION['aluno_identificado'])) {
        echo "<script>
                alert('Acesso negado! Identifique-se primeiro.');
                location.href = '../index.php';
              </script>";
        exit();
    }
    
    // VALIDA O ID DO ALUNO (igual ao seu código original)
    if (!isset($_SESSION['id_aluno']) || !is_numeric($_SESSION['id_aluno'])) {
        session_destroy();
        header("Location: ../index.php?erro=sessao_invalida");
        exit();
    }
    
    $aluno_id = (int)$_SESSION['id_aluno'];
    
    if ($aluno_id <= 0 || $aluno_id > 999999) {
        session_destroy();
        header("Location: ../index.php?erro=id_invalida");
        exit();
    }
    
    return $aluno_id;
}

function sanitizarInput($dados) {
    if (is_array($dados)) {
        return array_map('sanitizarInput', $dados);
    }
    return htmlspecialchars(trim($dados), ENT_QUOTES, 'UTF-8');
}

function escapeOutput($texto) {
    return htmlspecialchars($texto ?? '', ENT_QUOTES, 'UTF-8');
}

// No funcoes_comuns.php, adicione estas funções:

/**
 * FUNÇÃO UNIFICADA DE UPLOAD DE IMAGENS
 * Versão refatorada - Complexidade: 12 (dentro do limite)
 */
function fazerUploadImagens($idProva, $questaoNumero, $arquivos, $conectar = null) {
    // Se não receber conexão, cria uma própria
    $conexaoPropria = false;
    if ($conectar === null) {
        $conectar = conectarBanco();
        $conexaoPropria = true;
        
        if (!$conectar) {
            error_log("❌ Erro de conexão no upload de imagens");
            return [];
        }
    }
    
    // VALIDAÇÃO INICIAL
    if (!validarParametrosUpload($idProva, $questaoNumero, $arquivos)) {
        if ($conexaoPropria) mysqli_close($conectar);
        return [];
    }
    
    // PREPARAR DIRETÓRIO
    $uploadDir = prepararDiretorioUpload($idProva);
    if (!$uploadDir) {
        if ($conexaoPropria) mysqli_close($conectar);
        return [];
    }
    
    // PROCESSAR ARQUIVOS
    $resultado = processarArquivosUpload($idProva, $questaoNumero, $arquivos, $uploadDir, $conectar);
    
    if ($conexaoPropria) {
        mysqli_close($conectar);
    }
    
    return $resultado;
}

/**
 * FUNÇÕES AUXILIARES (as mesmas que já estão no processa_editar_prova.php)
 */
function validarParametrosUpload($idProva, $questaoNumero, $arquivos) {
    if (!is_numeric($idProva) || $idProva <= 0) {
        error_log("ID de prova inválido: $idProva");
        return false;
    }
    
    if (!is_numeric($questaoNumero) || $questaoNumero <= 0) {
        error_log("Número de questão inválido: $questaoNumero");
        return false;
    }
    
    if (!is_array($arquivos) || !isset($arquivos['tmp_name'])) {
        error_log("Dados de arquivo inválidos");
        return false;
    }
    
    return true;
}

function prepararDiretorioUpload($idProva) {
    $uploadBaseDir = "../uploads/provas/";
    $uploadDir = $uploadBaseDir . "prova_" . (int)$idProva . "/";
    
    // Criar diretório base se não existir
    if (!is_dir($uploadBaseDir) && !mkdir($uploadBaseDir, 0755, true)) {
        error_log("Não foi possível criar diretório: $uploadBaseDir");
        return null;
    }
    
    // Criar diretório específico da prova
    if (!is_dir($uploadDir) && !mkdir($uploadDir, 0755, true)) {
        error_log("Não foi possível criar diretório: $uploadDir");
        return null;
    }
    
    return $uploadDir;
}

function validarArquivo($arquivo, $tmp_name, $size) {
    // Validar erro de upload
    if ($arquivo !== UPLOAD_ERR_OK) {
        error_log("Erro no upload do arquivo: " . $arquivo);
        return false;
    }
    
    // Validar arquivo temporário
    if (empty($tmp_name) || !file_exists($tmp_name)) {
        error_log("Arquivo temporário inválido ou não existe");
        return false;
    }
    
    // Validar tipo MIME
    $allowedTypes = ['image/jpeg', 'image/png', 'image/gif', 'image/jpg', 'image/webp'];
    $finfo = finfo_open(FILEINFO_MIME_TYPE);
    $mimeType = finfo_file($finfo, $tmp_name);
    finfo_close($finfo);
    
    if (!in_array($mimeType, $allowedTypes)) {
        error_log("Tipo de arquivo não permitido: $mimeType");
        return false;
    }
    
    // Validar tamanho (máx 2MB)
    if ($size > 2 * 1024 * 1024) {
        error_log("Arquivo muito grande: " . $size . " bytes");
        return false;
    }
    
    // Validar se é realmente uma imagem
    if (!getimagesize($tmp_name)) {
        error_log("Arquivo não é uma imagem válida");
        return false;
    }
    
    return true;
}

function validarExtensaoESeguranca($nomeArquivo, $uploadDir, $caminhoCompleto) {
    $allowedExtensions = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
    $extensao = strtolower(pathinfo($nomeArquivo, PATHINFO_EXTENSION));
    
    if (!in_array($extensao, $allowedExtensions)) {
        error_log("❌ Extensão não permitida: $extensao");
        return false;
    }
    
    // Validação de segurança simplificada
    // Garantir que o caminho completo está dentro do diretório de upload
    $caminhoNormalizado = realpath(dirname($caminhoCompleto));
    $dirNormalizado = realpath($uploadDir);
    
    if ($caminhoNormalizado === false || $dirNormalizado === false) {
        error_log("❌ Não foi possível normalizar caminhos");
        return false;
    }
    
    // Verificar se o caminho está dentro do diretório permitido
    if (strpos($caminhoNormalizado, $dirNormalizado) !== 0) {
        error_log("❌ Tentativa de path traversal: $caminhoNormalizado não está em $dirNormalizado");
        return false;
    }
    
    return $extensao;
}

function salvarArquivoNoBanco($idProva, $questaoNumero, $caminhoRelativo, $nomeArquivoOriginal, $conectar) {
    $sql = "INSERT INTO ImagensProvas (idProva, numero_questao, caminho_imagem, nome_arquivo) 
            VALUES (?, ?, ?, ?)";
    $stmt = mysqli_prepare($conectar, $sql);
    
    if (!$stmt) {
        return false;
    }
    
    $nomeArquivoOriginal = htmlspecialchars(basename($nomeArquivoOriginal), ENT_QUOTES, 'UTF-8');
    mysqli_stmt_bind_param($stmt, "iiss", $idProva, $questaoNumero, $caminhoRelativo, $nomeArquivoOriginal);
    
    $sucesso = mysqli_stmt_execute($stmt);
    mysqli_stmt_close($stmt);
    
    return $sucesso;
}

function processarArquivosUpload($idProva, $questaoNumero, $arquivos, $uploadDir, $conectar) {
    $imagensSalvas = [];
    
    foreach ($arquivos['tmp_name'] as $key => $tmp_name) {
        // Validar arquivo individual
        if (!validarArquivo($arquivos['error'][$key], $tmp_name, $arquivos['size'][$key])) {
            continue;
        }
        
        // Gerar nome seguro e validar extensão
        $extensao = validarExtensaoESeguranca($arquivos['name'][$key], $uploadDir, $uploadDir . 'temp');
        if (!$extensao) {
            continue;
        }
        
        $nomeArquivo = uniqid() . '_questao_' . (int)$questaoNumero . '.' . $extensao;
        $caminhoCompleto = $uploadDir . $nomeArquivo;
        
        // Validar caminho final
        if (!validarExtensaoESeguranca($nomeArquivo, $uploadDir, $caminhoCompleto)) {
            continue;
        }
        
        // Fazer upload físico
        if (!move_uploaded_file($tmp_name, $caminhoCompleto)) {
            error_log("❌ Falha ao mover arquivo uploadado");
            continue;
        }
        
        // Preparar caminho relativo
        $caminhoRelativo = "uploads/provas/prova_" . (int)$idProva . "/" . $nomeArquivo;
        
        // Salvar no banco
        if (salvarArquivoNoBanco($idProva, $questaoNumero, $caminhoRelativo, $arquivos['name'][$key], $conectar)) {
            $imagensSalvas[] = $caminhoRelativo;
            error_log("✅ Imagem salva: $caminhoRelativo");
        } else {
            error_log("❌ Erro ao salvar imagem no banco");
            // Remover arquivo se falhou no banco
            if (file_exists($caminhoCompleto)) {
                unlink($caminhoCompleto);
            }
        }
    }
    
    return $imagensSalvas;
}
