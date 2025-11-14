<?php
session_start();
$host = "localhost";
$user = "root";
$password = "SenhaIrada@2024!";
$database = "projeto_residencia";
$conectar = mysqli_connect($host, $user, $password, $database);

// ✅ Verificar conexão
if (!$conectar) {
    die("Erro de conexão: " . mysqli_connect_error());
}

//  COLETAR E VALIDAR DADOS DO FORMULÁRIO
$nome = trim($_POST["nome"] ?? '');
$email = trim($_POST["email"] ?? '');
$cpf = trim($_POST["cpf"] ?? '');
$idade = intval($_POST["idade"] ?? 0);
$escolaridade = trim($_POST["escolaridade"] ?? '');
$codigo_acesso = trim($_POST["codigo_acesso"] ?? '');

//  VALIDAÇÕES BÁSICAS
if (empty($nome) || empty($cpf) || empty($escolaridade) || empty($codigo_acesso) || $idade < 8) {
    echo "<script> 
            alert('Preencha todos os campos obrigatórios! Idade mínima: 8 anos.');
            location.href = '../cadastro.php';
          </script>";
    exit();
}

//  CAMPOS OPCIONAIS
$endereco = !empty($_POST["endereco"]) ? trim($_POST["endereco"]) : '';
$telefone = !empty($_POST["telefone"]) ? trim($_POST["telefone"]) : '';
$escola = !empty($_POST["escola"]) ? trim($_POST["escola"]) : '';
$turma = !empty($_POST["turma"]) ? trim($_POST["turma"]) : '';

//  ADAPTAÇÃO PARA A ESTRUTURA ATUAL DA TABELA (campos INT)
// CPF - apenas números (para INT da tabela)
$cpf_limpo = preg_replace('/\D/', '', $cpf);
if (strlen($cpf_limpo) !== 11) {
    echo "<script> 
            alert('CPF deve conter 11 dígitos!');
            location.href = '../cadastro.php';
          </script>";
    exit();
}
$cpf_int = intval($cpf_limpo);

// Telefone - apenas números (para INT da tabela)
$telefone_limpo = $telefone ? preg_replace('/\D/', '', $telefone) : '0';
$telefone_int = intval($telefone_limpo);

//  CAMPOS DO RESPONSÁVEL (CONDICIONAIS)
$nome_responsavel = '';
$telefone_responsavel_int = NULL;

if ($idade < 18) {
    //  PARA MENORES DE IDADE - CAMPOS OBRIGATÓRIOS
    if (empty($_POST["nome_responsavel"]) || empty($_POST["telefone_responsavel"])) {
        echo "<script> 
                alert('Para menores de 18 anos, os dados do responsável são obrigatórios!');
                location.href = '../cadastro.php';
              </script>";
        exit();
    }
    
    $nome_responsavel = trim($_POST["nome_responsavel"]);
    $telefone_responsavel_limpo = preg_replace('/\D/', '', trim($_POST["telefone_responsavel"]));
    $telefone_responsavel_int = intval($telefone_responsavel_limpo);
} else {
    //  PARA MAIORES DE IDADE - CAMPOS OPCIONAIS
    $nome_responsavel = !empty($_POST["nome_responsavel"]) ? trim($_POST["nome_responsavel"]) : '';
    if (!empty($_POST["telefone_responsavel"])) {
        $telefone_responsavel_limpo = preg_replace('/\D/', '', trim($_POST["telefone_responsavel"]));
        $telefone_responsavel_int = intval($telefone_responsavel_limpo);
    }
}

//  VALIDAR EMAIL (se preenchido)
if (!empty($email) && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
    echo "<script> 
            alert('E-mail inválido!');
            location.href = '../cadastro.php';
          </script>";
    exit();
}

// Verificar se código de acesso já existe (SEGURA)
$sql_verificar_codigo = "SELECT codigo_acesso FROM aluno WHERE codigo_acesso = ?";
$stmt_codigo = mysqli_prepare($conectar, $sql_verificar_codigo);
mysqli_stmt_bind_param($stmt_codigo, "s", $codigo_acesso);
mysqli_stmt_execute($stmt_codigo);
$resultado_verificar = mysqli_stmt_get_result($stmt_codigo);

if (mysqli_num_rows($resultado_verificar) > 0) {
    mysqli_stmt_close($stmt_codigo);
    echo "<script> 
            alert('Código de acesso já existe. Gerando novo código...');
            location.href = '../cadastro.php';
          </script>";
    exit();
}
mysqli_stmt_close($stmt_codigo);

// Verificar se CPF já existe (SEGURA)
$sql_verificar_cpf = "SELECT cpf FROM aluno WHERE cpf = ?";
$stmt_cpf = mysqli_prepare($conectar, $sql_verificar_cpf);
mysqli_stmt_bind_param($stmt_cpf, "i", $cpf_int);
mysqli_stmt_execute($stmt_cpf);
$resultado_cpf = mysqli_stmt_get_result($stmt_cpf);

if (mysqli_num_rows($resultado_cpf) > 0) {
    mysqli_stmt_close($stmt_cpf);
    echo "<script> 
            alert('CPF já cadastrado no sistema!');
            location.href = '../cadastro.php';
          </script>";
    exit();
}
mysqli_stmt_close($stmt_cpf);

// Verificar se email já existe (SEGURA - se foi preenchido)
if (!empty($email)) {
    $sql_verificar_email = "SELECT email FROM aluno WHERE email = ?";
    $stmt_email = mysqli_prepare($conectar, $sql_verificar_email);
    mysqli_stmt_bind_param($stmt_email, "s", $email);
    mysqli_stmt_execute($stmt_email);
    $resultado_email = mysqli_stmt_get_result($stmt_email);
    
    if (mysqli_num_rows($resultado_email) > 0) {
        mysqli_stmt_close($stmt_email);
        echo "<script> 
                alert('E-mail já cadastrado no sistema!');
                location.href = '../cadastro.php';
              </script>";
        exit();
    }
    mysqli_stmt_close($stmt_email);
}

//  Inserir no banco de dados
$sql_cadastrar = "INSERT INTO Aluno
                  (codigo_acesso, nome, idade, email, cpf, escolaridade,
                   endereco, telefone, tell_responsavel, nome_responsavel,
                   turma, escola, data_cadastro)
                  VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, CURDATE())";

$stmt_cadastrar = mysqli_prepare($conectar, $sql_cadastrar);

if ($stmt_cadastrar) {
    // ✅ BIND PARAM com tipos CORRETOS para a estrutura atual da tabela
    mysqli_stmt_bind_param($stmt_cadastrar, "ssisssssssss", 
        $codigo_acesso,        // s (varchar)
        $nome,                 // s (varchar) 
        $idade,                // i (int)
        $email,                // s (varchar)
        $cpf,              // s
        $escolaridade,         // s (varchar)
        $endereco,             // s (varchar)
        $telefone,         // s
        $telefone_responsavel, // s
        $nome_responsavel,     // s (varchar)
        $turma,                // s (varchar)
        $escola                // s (varchar)
    );
    
    if (mysqli_stmt_execute($stmt_cadastrar)) {
        //  SUCESSO
        $nome_seguro = htmlspecialchars($nome);
        $codigo_seguro = htmlspecialchars($codigo_acesso);
        
        echo "<script> 
                alert('$nome_seguro cadastrado com sucesso!\\\\n\\\\nCÓDIGO DE ACESSO: $codigo_seguro\\\\n\\\\nGuarde este código com segurança!');
                location.href = '../alunos/cadastro_sucesso.php?codigo=' + encodeURIComponent('$codigo_seguro') + '&nome=' + encodeURIComponent('$nome_seguro');
              </script>";
    } else {
        //  ERRO NO INSERT
        $erro = mysqli_error($conectar);
        error_log("Erro no cadastro: $erro");
        
        echo "<script> 
                alert('Erro no cadastro. Tente novamente.');
                location.href = '../cadastro.php';
              </script>";
    }
    mysqli_stmt_close($stmt_cadastrar);
} else {
    echo "<script> 
            alert('Erro no sistema. Tente novamente.');
            location.href = '../cadastro.php';
          </script>";
}

mysqli_close($conectar);