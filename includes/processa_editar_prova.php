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

    // Processar remoção de imagens
    for ($i = 1; $i <= $numero_questoes; $i++) {
        $chave_remover = "imagens_remover_$i";
        if (isset($_POST[$chave_remover]) && is_array($_POST[$chave_remover])) {
            foreach ($_POST[$chave_remover] as $idImagem) {
                $idImagem = mysqli_real_escape_string($conectar, $idImagem);
                
                // Buscar informações da imagem para excluir o arquivo
                $sql_imagem = "SELECT caminho_imagem FROM ImagensProvas WHERE idImagem = '$idImagem' AND idProva = '$prova_id'";
                $result_imagem = mysqli_query($conectar, $sql_imagem);
                
                if ($result_imagem && $imagem = mysqli_fetch_assoc($result_imagem)) {
                    $caminho_arquivo = '../' . $imagem['caminho_imagem'];
                    
                    // Excluir arquivo físico
                    if (file_exists($caminho_arquivo)) {
                        unlink($caminho_arquivo);
                    }
                    
                    // Excluir registro do banco
                    $sql_excluir = "DELETE FROM ImagensProvas WHERE idImagem = '$idImagem'";
                    mysqli_query($conectar, $sql_excluir);
                }
            }
        }
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

        // Processar upload de novas imagens
        $total_novas_imagens = 0;
        for ($i = 1; $i <= $numero_questoes; $i++) {
            $chave_novas_imagens = "novas_imagens_$i";
            
            if (isset($_FILES[$chave_novas_imagens]) && !empty($_FILES[$chave_novas_imagens]['name'][0])) {
                $imagensSalvas = fazerUploadImagens($prova_id, $i, $_FILES[$chave_novas_imagens]);
                $total_novas_imagens += count($imagensSalvas);
            }
        }
        
        // Mensagem de sucesso
        $mensagem = "Prova atualizada com sucesso!";
        if ($total_novas_imagens > 0) {
            $mensagem .= " $total_novas_imagens nova(s) imagem(ns) adicionada(s).";
        }
        
        $_SESSION['mensagem_sucesso'] = $mensagem;
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