<?php
session_start();
require_once __DIR__ . '../config/funcoes_comuns.php'; 
$conectar = conectarBanco();

$login = $_POST["username"] ?? '';
$senha = $_POST["password"] ?? '';

// Consulta para Professor usando Prepared Statements
$sql_professor = "SELECT idProfessor, login, senha, nome, email, cpf, data_cadastro
                  FROM Professor 
                  WHERE login = ?";

$stmt_professor = mysqli_prepare($conectar, $sql_professor);

if ($stmt_professor) {
    mysqli_stmt_bind_param($stmt_professor, "s", $login);
    mysqli_stmt_execute($stmt_professor);
    $resultado_professor = mysqli_stmt_get_result($stmt_professor);
    
    $linhas_professor = mysqli_num_rows($resultado_professor);

    if ($linhas_professor == 1) {
        $registro = mysqli_fetch_assoc($resultado_professor);
        
        // VERIFICAÇÃO DE SENHA 
        if ($senha === $registro['senha']) {
            $_SESSION["idProfessor"] = $registro['idProfessor'];
            $_SESSION["nome"] = $registro['nome'];
            $_SESSION["usuario"] = $registro['nome'];
            $_SESSION["tipo_usuario"] = "professor";
            $_SESSION["logado"] = true;
            
            mysqli_stmt_close($stmt_professor);
            mysqli_close($conectar);
            
            header("Location: professores/dashboard_professor.php");
            exit();
        } else {
            mostrarErro();
        }
    } else {
        mostrarErro();
    }
    
    mysqli_stmt_close($stmt_professor);
} else {
    // Erro na preparação da query
    error_log("Erro na preparação da query: " . mysqli_error($conectar));
    mostrarErro();
}

function mostrarErro() {
    echo "<script> 
            alert('Login ou Senha Incorretos! Digite Novamente!!');
            location.href = 'index.php';
          </script>";
    exit();
}
