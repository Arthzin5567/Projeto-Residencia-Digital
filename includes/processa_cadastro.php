<?php
session_start();
$host = "localhost";
$user = "root";
$password = "SenhaIrada@2024!";
$database = "projeto_residencia";
$conectar = mysqli_connect($host, $user, $password, $database);

// Coletar todos os dados do formulário
$nome = mysqli_real_escape_string($conectar, $_POST["nome"]);
$email = mysqli_real_escape_string($conectar, $_POST["email"]);
$cpf = mysqli_real_escape_string($conectar, $_POST["cpf"]);
$idade = intval($_POST["idade"]);
$escolaridade = mysqli_real_escape_string($conectar, $_POST["escolaridade"]);
$codigo_acesso = mysqli_real_escape_string($conectar, $_POST["codigo_acesso"]);

// Campos opcionais
$endereco = isset($_POST["endereco"]) ? mysqli_real_escape_string($conectar, $_POST["endereco"]) : NULL;
$telefone = isset($_POST["telefone"]) ? mysqli_real_escape_string($conectar, $_POST["telefone"]) : NULL;
$escola = isset($_POST["escola"]) ? mysqli_real_escape_string($conectar, $_POST["escola"]) : NULL;
$turma = isset($_POST["turma"]) ? mysqli_real_escape_string($conectar, $_POST["turma"]) : NULL;

// Campos do responsável (condicionais)
if ($idade < 18) {
    // Para menores de idade - campos obrigatórios
    if (empty($_POST["nome_responsavel"]) || empty($_POST["telefone_responsavel"])) {
        echo "<script> 
                alert('Para menores de 18 anos, os dados do responsável são obrigatórios!');
                location.href = '../cadastro.php';
              </script>";
        exit();
    }
    $nome_responsavel = mysqli_real_escape_string($conectar, $_POST["nome_responsavel"]);
    $telefone_responsavel = mysqli_real_escape_string($conectar, $_POST["telefone_responsavel"]);
} else {
    // Para maiores de idade - campos opcionais
    $nome_responsavel = !empty($_POST["nome_responsavel"]) ? mysqli_real_escape_string($conectar, $_POST["nome_responsavel"]) : NULL;
    $telefone_responsavel = !empty($_POST["telefone_responsavel"]) ? mysqli_real_escape_string($conectar, $_POST["telefone_responsavel"]) : NULL;
}

// Validar idade mínima
if ($idade < 8) {
    echo "<script> 
            alert('Idade mínima é 8 anos!');
            location.href = '../cadastro.php';
          </script>";
    exit();
}

// Verificar se código de acesso já existe
$sql_verificar_codigo = "SELECT codigo_acesso FROM aluno WHERE codigo_acesso = '$codigo_acesso'";
$resultado_verificar = mysqli_query($conectar, $sql_verificar_codigo);

if (mysqli_num_rows($resultado_verificar) > 0) {
    // Código já existe - gerar novo e recarregar página
    echo "<script> 
            alert('Código de acesso já existe. Gerando novo código...');
            location.href = '../cadastro.php';
          </script>";
    exit();
}

// Verificar se CPF já existe
$sql_verificar_cpf = "SELECT cpf FROM aluno WHERE cpf = '$cpf'";
$resultado_cpf = mysqli_query($conectar, $sql_verificar_cpf);

if (mysqli_num_rows($resultado_cpf) > 0) {
    echo "<script> 
            alert('CPF já cadastrado no sistema!');
            location.href = '../cadastro.php';
          </script>";
    exit();
}

// Verificar se email já existe (se foi preenchido)
if (!empty($email)) {
    $sql_verificar_email = "SELECT email FROM aluno WHERE email = '$email'";
    $resultado_email = mysqli_query($conectar, $sql_verificar_email);
    
    if (mysqli_num_rows($resultado_email) > 0) {
        echo "<script> 
                alert('E-mail já cadastrado no sistema!');
                location.href = '../cadastro.php';
              </script>";
        exit();
    }
}

// Inserir no banco de dados
$sql_cadastrar = "INSERT INTO Aluno 
                  (nome, email, cpf, idade, escolaridade, codigo_acesso, endereco, telefone, nome_responsavel, tell_responsavel, escola, turma) 
                  VALUES 
                  ('$nome', " . 
                  (!empty($email) ? "'$email'" : "NULL") . ", 
                  '$cpf', 
                  '$idade', 
                  '$escolaridade', 
                  '$codigo_acesso', 
                  " . (!empty($endereco) ? "'$endereco'" : "NULL") . ", 
                  " . (!empty($telefone) ? "'$telefone'" : "NULL") . ", 
                  " . (!empty($nome_responsavel) ? "'$nome_responsavel'" : "NULL") . ", 
                  " . (!empty($telefone_responsavel) ? "'$telefone_responsavel'" : "NULL") . ", 
                  " . (!empty($escola) ? "'$escola'" : "NULL") . ", 
                  " . (!empty($turma) ? "'$turma'" : "NULL") . ")";

$resultado_cadastrar = mysqli_query($conectar, $sql_cadastrar);

if ($resultado_cadastrar) {
    // Sucesso - mostrar código de acesso
    echo "<script> 
            alert('$nome cadastrado com sucesso!\\\\n\\\\nCÓDIGO DE ACESSO: $codigo_acesso\\\\n\\\\nGuarde este código com segurança!');
            location.href = '../alunos/cadastro_sucesso.php?codigo=' + encodeURIComponent('$codigo_acesso') + '&nome=' + encodeURIComponent('$nome');
          </script>";
} else {
    // Erro no servidor
    echo "<script> 
            alert('Erro no servidor: " . mysqli_error($conectar) . "');
            location.href = '../cadastro.php';
          </script>";
}