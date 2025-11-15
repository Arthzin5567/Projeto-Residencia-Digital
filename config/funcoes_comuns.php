<?php
/**
 * FUNÇÕES COMUNS PARA REDUZIR DUPLICAÇÃO
 */

// 1. CONEXÃO COM BANCO (elimina 5-10 linhas de cada arquivo)
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
