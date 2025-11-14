<?php
session_start();

$host = "localhost";
$user = "root";
$password = "SenhaIrada@2024!";
$database = "projeto_residencia";
$conectar = mysqli_connect($host, $user, $password, $database);

$login = $_POST["username"];
$senha = $_POST["password"];

// Consulta para Professor
$sql_professor = "SELECT idProfessor, login, senha, nome, email, cpf, data_cadastro
                  FROM Professor 
                  WHERE login = '$login'";

$resultado_professor = mysqli_query($conectar, $sql_professor);

$linhas_professor = mysqli_num_rows($resultado_professor);

if ($linhas_professor == 1) {
    $registro = mysqli_fetch_row($resultado_professor);
    
        $_SESSION["idProfessor"] = $registro[0];
        $_SESSION["nome"] = $registro[3];
        $_SESSION["usuario"] = $registro[3];
        $_SESSION["tipo_usuario"] = "professor";
        $_SESSION["logado"] = true;
        
        echo "<script> 
                location.href = 'professores/dashboard_professor.php'
              </script>";
    
} elseif ($linhas_aluno == 1) {
    $registro = mysqli_fetch_row($resultado_aluno);
    
        $_SESSION["idAluno"] = $registro[0];
        $_SESSION["nome"] = $registro[3];
        $_SESSION["usuario"] = $registro[3];
        $_SESSION["tipo_usuario"] = "aluno";
        $_SESSION["logado"] = true;
        
        echo "<script> 
                location.href = 'home.php'
              </script>";
   
} else {
    mostrarErro();
}

function mostrarErro() {
    echo "<script> 
            alert('Login ou Senha Incorretos! Digite Novamente!!');
            location.href = 'index.php';
          </script>";
}