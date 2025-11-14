<?php
function fazerUploadImagens($idProva, $questaoNumero, $arquivos) {
    //  Validar parâmetros de entrada
    if (!is_numeric($idProva) || $idProva <= 0) {
        error_log("ID de prova inválido: $idProva");
        return [];
    }
    
    if (!is_numeric($questaoNumero) || $questaoNumero <= 0) {
        error_log("Número de questão inválido: $questaoNumero");
        return [];
    }
    
    if (!is_array($arquivos) || !isset($arquivos['tmp_name'])) {
        error_log("Dados de arquivo inválidos");
        return [];
    }

    $host = "localhost";
    $user = "root";
    $password = "SenhaIrada@2024!";
    $database = "projeto_residencia";
    $conectar = mysqli_connect($host, $user, $password, $database);

    //  Verificar conexão
    if (!$conectar) {
        error_log("Erro de conexão no upload de imagens");
        return [];
    }
    
    // DEBUG: Verificar o que está chegando
    error_log("Upload chamado para prova $idProva, questão $questaoNumero");
    
    //  Diretório de upload com validação
    $uploadBaseDir = "../uploads/provas/";
    
    // Garantir que o diretório base existe
    if (!is_dir($uploadBaseDir)) {
        if (!mkdir($uploadBaseDir, 0755, true)) {
            error_log("Não foi possível criar diretório base: $uploadBaseDir");
            mysqli_close($conectar);
            return [];
        }
    }
    
    $uploadDir = $uploadBaseDir . "prova_" . (int)$idProva . "/";
    
    // Criar diretório se não existir
    if (!is_dir($uploadDir)) {
        if (!mkdir($uploadDir, 0755, true)) {
            error_log("Não foi possível criar diretório: $uploadDir");
            mysqli_close($conectar);
            return [];
        }
    }
    
    $imagensSalvas = [];
    
    foreach ($arquivos['tmp_name'] as $key => $tmp_name) {
        //  Validar se o upload foi bem sucedido
        if ($arquivos['error'][$key] !== UPLOAD_ERR_OK) {
            error_log("Erro no upload do arquivo: " . $arquivos['error'][$key]);
            continue;
        }
        
        //  Validar se o arquivo temporário existe
        if (empty($tmp_name) || !file_exists($tmp_name)) {
            error_log("Arquivo temporário inválido ou não existe");
            continue;
        }

        //  Validar tipo de arquivo
        $allowedTypes = ['image/jpeg', 'image/png', 'image/gif', 'image/jpg', 'image/webp'];
        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $mimeType = finfo_file($finfo, $tmp_name);
        finfo_close($finfo);
        
        if (!in_array($mimeType, $allowedTypes)) {
            error_log("Tipo de arquivo não permitido: $mimeType");
            continue;
        }
        
        //  Validar tamanho (máx 2MB)
        if ($arquivos['size'][$key] > 2 * 1024 * 1024) {
            error_log("Arquivo muito grande: " . $arquivos['size'][$key] . " bytes");
            continue;
        }
        
        //  Validar se é realmente uma imagem
        if (!getimagesize($tmp_name)) {
            error_log("Arquivo não é uma imagem válida");
            continue;
        }
        
        //  Gerar nome seguro para o arquivo
        $extensao = pathinfo($arquivos['name'][$key], PATHINFO_EXTENSION);
        $extensao = strtolower($extensao);
        
        // Permitir apenas extensões seguras
        $allowedExtensions = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
        if (!in_array($extensao, $allowedExtensions)) {
            error_log("Extensão não permitida: $extensao");
            continue;
        }
        
        $nomeArquivo = uniqid() . '_questao_' . (int)$questaoNumero . '.' . $extensao;
        $caminhoCompleto = $uploadDir . $nomeArquivo;
        
        //  Validar caminho final
        $realUploadDir = realpath($uploadDir);
        $realCaminhoCompleto = realpath(dirname($caminhoCompleto));
        
        if ($realCaminhoCompleto === false || strpos($realCaminhoCompleto, $realUploadDir) !== 0) {
            error_log("Tentativa de path traversal detectada");
            continue;
        }
        
        if (move_uploaded_file($tmp_name, $caminhoCompleto)) {
            //  Preparar caminho relativo para o banco
            $caminhoRelativo = "uploads/provas/prova_" . (int)$idProva . "/" . $nomeArquivo;
            
            // Salvar no banco de dados
            $sql = "INSERT INTO ImagensProvas (idProva, numero_questao, caminho_imagem, nome_arquivo) 
                    VALUES (?, ?, ?, ?)";
            $stmt = mysqli_prepare($conectar, $sql);
            
            if ($stmt) {
                $nomeArquivoOriginal = htmlspecialchars(basename($arquivos['name'][$key]), ENT_QUOTES, 'UTF-8');
                mysqli_stmt_bind_param($stmt, "iiss", $idProva, $questaoNumero, $caminhoRelativo, $nomeArquivoOriginal);
                
                if (mysqli_stmt_execute($stmt)) {
                    $imagensSalvas[] = $caminhoRelativo;
                    error_log("✅ Imagem salva: $caminhoRelativo");
                } else {
                    error_log("❌ Erro ao salvar imagem no banco: " . mysqli_stmt_error($stmt));
                    // Remover arquivo se falhou no banco
                    if (file_exists($caminhoCompleto)) {
                        unlink($caminhoCompleto);
                    }
                }
                
                mysqli_stmt_close($stmt);
            }
        } else {
            error_log("❌ Falha ao mover arquivo uploadado");
        }
    }
    
    mysqli_close($conectar);
    
    // DEBUG: Resultado final
    error_log("Upload finalizado. " . count($imagensSalvas) . " imagem(ns) salva(s)");
    
    return $imagensSalvas;
}