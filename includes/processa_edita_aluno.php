<?php
session_start();

// Headers de segurança
header('X-Content-Type-Options: nosniff');
header('X-Frame-Options: DENY');

// FUNÇÃO PARA LIMPAR TELEFONE
function limparTelefone($telefone) {
    if (empty($telefone)) return '';
    return preg_replace('/\D/', '', $telefone);
}

// Verificar se o aluno está identificado e se é POST
if (!isset($_SESSION['aluno_identificado']) || $_SESSION['aluno_identificado'] !== true) {
    header("Location: ../index.php");
    exit();
}

// Validar ID do aluno
if (!isset($_SESSION['id_aluno']) || !is_numeric($_SESSION['id_aluno'])) {
    header("Location: ../index.php");
    exit();
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: ../alunos/perfil.php");
    exit();
}

require_once __DIR__ . '/../config/funcoes_comuns.php';
$conectar = conectarBanco();

// Verificar conexão
if (!$conectar) {
    error_log("Erro de conexão ao editar perfil");
    $_SESSION['erro_perfil'] = "Erro de conexão. Tente novamente.";
    header("Location: ../alunos/perfil.php");
    exit();
}

$aluno_id = validarLoginAluno();

// Buscar dados com Prepared Statement (já está bom)
$sql_aluno = "SELECT * FROM Aluno WHERE idAluno = ?";
$stmt_aluno = mysqli_prepare($conectar, $sql_aluno);
mysqli_stmt_bind_param($stmt_aluno, "i", $aluno_id);
mysqli_stmt_execute($stmt_aluno);
$result_aluno = mysqli_stmt_get_result($stmt_aluno);
$aluno = mysqli_fetch_assoc($result_aluno);
mysqli_stmt_close($stmt_aluno);

if (!$aluno) {
    $_SESSION['erro_perfil'] = "Aluno não encontrado!";
    header("Location: ../alunos/perfil.php");
    exit();
}

// Processar atualização do perfil
$codigo_confirmacao = trim($_POST['codigo_confirmacao'] ?? '');

// Validação segura do código
if (empty($codigo_confirmacao)) {
    $_SESSION['erro_perfil'] = "Código de confirmação é obrigatório!";
    header("Location: ../alunos/perfil.php");
    exit();
}

if ($codigo_confirmacao !== $aluno['codigo_acesso']) {
    $_SESSION['erro_perfil'] = "Código de confirmação incorreto!";
    header("Location: ../alunos/perfil.php");
    exit();
}

// Sanitização dos dados
$nome = trim(htmlspecialchars($_POST['nome'] ?? '', ENT_QUOTES, 'UTF-8'));
$email = trim($_POST['email'] ?? '');
$endereco = trim(htmlspecialchars($_POST['endereco'] ?? '', ENT_QUOTES, 'UTF-8'));
$telefone = limparTelefone(trim($_POST['telefone'] ?? ''));
$escola = trim(htmlspecialchars($_POST['escola'] ?? '', ENT_QUOTES, 'UTF-8'));
$turma = trim(htmlspecialchars($_POST['turma'] ?? '', ENT_QUOTES, 'UTF-8'));

// VALIDAÇÕES BÁSICAS
if (empty($nome) || strlen($nome) > 45) {
    $_SESSION['erro_perfil'] = "Nome inválido!";
    header("Location: ../alunos/perfil.php");
    exit();
} elseif ($email && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
    $_SESSION['erro_perfil'] = "E-mail inválido!";
    header("Location: ../alunos/perfil.php");
    exit();
}

// CONSTRUIR SQL DINÂMICO COM PREPARED STATEMENT
$sql_atualizar = "UPDATE Aluno SET
                 nome = ?, email = ?, endereco = ?, telefone = ?, escola = ?, turma = ?";
$tipos = "ssssss";
$valores = [$nome, $email, $endereco, $telefone, $escola, $turma];

// ADICIONAR CAMPOS DO RESPONSÁVEL SE FOR MENOR DE IDADE
if ($aluno['idade'] < 18) {
    $nome_responsavel = trim($_POST['nome_responsavel']);
    $telefone_responsavel = limparTelefone(trim($_POST['telefone_responsavel']));
    
    if (empty($nome_responsavel) || empty($telefone_responsavel)) {
        $_SESSION['erro_perfil'] = "Dados do responsável são obrigatórios para menores de idade!";
        header("Location: ../alunos/perfil.php");
        exit();
    } else {
        $sql_atualizar .= ", nome_responsavel = ?, tell_responsavel = ?";
        $tipos .= "ss";
        $valores[] = $nome_responsavel;
        $valores[] = $telefone_responsavel;
    }
}

$sql_atualizar .= " WHERE idAluno = ?";
$tipos .= "i";
$valores[] = $aluno_id;

// EXECUTAR UPDATE SEGURO
$stmt_atualizar = mysqli_prepare($conectar, $sql_atualizar);

if ($stmt_atualizar) {
    // BIND PARAM DINÂMICO
    mysqli_stmt_bind_param($stmt_atualizar, $tipos, ...$valores);
    
    if (mysqli_stmt_execute($stmt_atualizar)) {
        // ATUALIZAR DADOS NA SESSÃO
        $_SESSION['nome_aluno'] = htmlspecialchars($nome);
        $_SESSION['usuario'] = htmlspecialchars($nome);
        $_SESSION['sucesso_perfil'] = "Perfil atualizado com sucesso!";
    } else {
        $_SESSION['erro_perfil'] = "Erro ao atualizar perfil: " . mysqli_error($conectar);
    }
    mysqli_stmt_close($stmt_atualizar);
} else {
    $_SESSION['erro_perfil'] = "Erro no sistema. Tente novamente.";
}

mysqli_close($conectar);
header("Location: ../alunos/perfil.php");
exit();
