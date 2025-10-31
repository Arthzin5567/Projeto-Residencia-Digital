<?php
function fazerUploadImagens($idProva, $questaoNumero, $arquivos) {
    $conectar = mysqli_connect("localhost", "root", "", "projeto_residencia");

    // DEBUG: Verificar o que está chegando
    error_log("Upload chamado para prova $idProva, questão $questaoNumero");
    error_log("Arquivos recebidos: " . print_r($arquivos, true));
    
    // Diretório de upload
    $uploadDir = "../uploads/provas/prova_" . $idProva . "/";
    
    // Criar diretório se não existir
    if (!is_dir($uploadDir)) {
        mkdir($uploadDir, 0777, true);
    }
    
    $imagensSalvas = [];
    
    foreach ($arquivos['tmp_name'] as $key => $tmp_name) {
        if ($arquivos['error'][$key] === UPLOAD_ERR_OK) {
            // Validar tipo de arquivo
            $allowedTypes = ['image/jpeg', 'image/png', 'image/gif', 'image/jpg'];
            $finfo = finfo_open(FILEINFO_MIME_TYPE);
            $mimeType = finfo_file($finfo, $tmp_name);
            finfo_close($finfo);
            
            if (!in_array($mimeType, $allowedTypes)) {
                continue; // Pula arquivos que não são imagens
            }
            
            // Validar tamanho (máx 2MB)
            if ($arquivos['size'][$key] > 2 * 1024 * 1024) {
                continue;
            }
            
            // Gerar nome único para o arquivo
            $extensao = pathinfo($arquivos['name'][$key], PATHINFO_EXTENSION);
            $nomeArquivo = uniqid() . '_questao_' . $questaoNumero . '.' . $extensao;
            $caminhoCompleto = $uploadDir . $nomeArquivo;
            
            if (move_uploaded_file($tmp_name, $caminhoCompleto)) {
                // Salvar no banco de dados
                $sql = "INSERT INTO ImagensProvas (idProva, numero_questao, caminho_imagem, nome_arquivo) 
                        VALUES (?, ?, ?, ?)";
                $stmt = mysqli_prepare($conectar, $sql);
                mysqli_stmt_bind_param($stmt, "iiss", $idProva, $questaoNumero, $caminhoCompleto, $arquivos['name'][$key]);
                
                if (mysqli_stmt_execute($stmt)) {
                    $imagensSalvas[] = $caminhoCompleto;
                }
                
                mysqli_stmt_close($stmt);
            }
        }
    }
    
    mysqli_close($conectar);
    return $imagensSalvas;
}
?>