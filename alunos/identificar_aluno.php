<?php
session_start();

// Se o aluno já está identificado, redirecionar para o dashboard
if (isset($_SESSION['aluno_identificado'])) {
    header("Location: dashboard_aluno.php");
    exit();
}

$conectar = mysqli_connect("localhost", "root", "", "projeto_residencia");

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    if (isset($_POST['codigo_acesso'])) {
        // Aluno já cadastrado - identificar pelo código
        $codigo_acesso = mysqli_real_escape_string($conectar, $_POST['codigo_acesso']);
        
        $sql = "SELECT * FROM Aluno WHERE codigo_acesso = '$codigo_acesso'";
        $result = mysqli_query($conectar, $sql);
        
        if (mysqli_num_rows($result) === 1) {
            $aluno = mysqli_fetch_assoc($result);
            
            $_SESSION['aluno_identificado'] = true;
            $_SESSION['id_aluno'] = $aluno['idAluno'];
            $_SESSION['nome_aluno'] = $aluno['nome'];
            $_SESSION['usuario'] = $aluno['nome'];
            
            header("Location: dashboard_aluno.php");
            exit();
        } else {
            $erro = "Código de acesso não encontrado!";
        }
    } elseif (isset($_POST['cadastrar'])) {
        // Novo cadastro de aluno
        header("Location: ../cadastro.php");
        exit();
    }
}
?>

<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Identificação do Aluno - Edukhan</title>
        <link rel="stylesheet" href="../css/style.css">
</head>
<body>
    <header>
        <nav>
            <div class="logo">Edukhan - Identificação</div>
            <ul class="nav-links">
                <li><a href="../index.php">Voltar ao Início</a></li>
            </ul>
        </nav>
    </header>

    <main>
        <article class="identificar-links">
            <h1>Identificação do Aluno</h1>
            
            <?php if (isset($erro)): ?>
                <div>
                    <?php echo $erro; ?>
                </div>
            <?php endif; ?>
            
            <!-- Formulário para alunos já cadastrados -->
            <div class="identificar-aluno">
                <h2>Já sou cadastrado</h2>
                <p>Digite seu código de acesso:</p>
                <form method="POST" action="identificar_aluno.php">
                    <div>
                        <label for="codigo_acesso">Código de Acesso:</label>
                        <input type="text" id="codigo_acesso" name="codigo_acesso" required 
                               placeholder="Digite seu código de acesso">
                    </div>
                    <button type="submit">Entrar</button>
                </form>
            </div>
            
            <!-- Opção para novos alunos -->
            <div class="identificar-aluno">
                <h2>Primeiro acesso</h2>
                <p>Se é sua primeira vez, faça seu cadastro:</p>
                <form method="POST" action="identificar_aluno.php">
                    <button type="submit" name="cadastrar" value="true">
                        Fazer Cadastro
                    </button>
                </form>
            </div>
            
            <p><a href="../index.php">← Voltar para a página inicial</a></p>
        </article>
    </main>
</body>
</html>