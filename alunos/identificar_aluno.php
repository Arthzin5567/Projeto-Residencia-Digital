<?php
session_start();

// Se o aluno já está identificado, redirecionar para o dashboard
if (isset($_SESSION['aluno_identificado'])) {
    header("Location: dashboard_aluno.php");
    exit();
}

$host = "localhost";
$user = "root";
$password = "SenhaIrada@2024!";
$database = "projeto_residencia";
$conectar = mysqli_connect($host, $user, $password, $database);

// ✅ Verificar se a conexão foi bem sucedida
if (!$conectar) {
    die("Erro de conexão: " . mysqli_connect_error());
}

$max_tentativas = 5;
$bloqueio_tempo = 15 * 60; // 15 minutos

// Verificar se há muitas tentativas falhas
if (isset($_SESSION['tentativas_login'])) {
    if ($_SESSION['tentativas_login'] >= $max_tentativas) {
        if (time() - $_SESSION['ultima_tentativa'] < $bloqueio_tempo) {
            $erro = "Muitas tentativas falhas. Tente novamente em " . 
                    ceil(($bloqueio_tempo - (time() - $_SESSION['ultima_tentativa'])) / 60) . " minutos.";
            $bloqueado = true;
        } else {
            // Resetar contador após o tempo de bloqueio
            unset($_SESSION['tentativas_login']);
            unset($_SESSION['ultima_tentativa']);
        }
    }
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    if (isset($_POST['codigo_acesso'])) {
        //  ALUNO JÁ CADASTRADO 
        $codigo_acesso = trim($_POST['codigo_acesso']);
        
        //  VALIDAÇÃO do código de acesso
        if (empty($codigo_acesso) || strlen($codigo_acesso) > 20) {
            $erro = "Código de acesso inválido!";
        } else {
            $sql = "SELECT idAluno, nome, codigo_acesso FROM Aluno WHERE codigo_acesso = ?";
            $stmt = mysqli_prepare($conectar, $sql);
            
            if ($stmt) {
                mysqli_stmt_bind_param($stmt, "s", $codigo_acesso);
                mysqli_stmt_execute($stmt);
                $result = mysqli_stmt_get_result($stmt);
                
                if (mysqli_num_rows($result) === 1) {
                    $aluno = mysqli_fetch_assoc($result);
                    
                    $_SESSION['aluno_identificado'] = true;
                    $_SESSION['id_aluno'] = (int)$aluno['idAluno'];
                    $_SESSION['nome_aluno'] = htmlspecialchars($aluno['nome']);
                    $_SESSION['usuario'] = htmlspecialchars($aluno['nome']);
                    
                    mysqli_stmt_close($stmt);
                    
                    header("Location: dashboard_aluno.php");
                    exit();
                } else {
                    $erro = "Código de acesso não encontrado!";
                }
                mysqli_stmt_close($stmt);
            } else {
                $erro = "Erro no sistema. Tente novamente.";
            }
        }
    } elseif (isset($_POST['cadastrar'])) {
        // Novo cadastro de aluno
        header("Location: ../cadastro.php");
        exit();
    }
}

if (isset($erro)) {
    // Incrementar contador de tentativas falhas
    $_SESSION['tentativas_login'] = ($_SESSION['tentativas_login'] ?? 0) + 1;
    $_SESSION['ultima_tentativa'] = time();
    
    // Log da tentativa (em produção, salvaria em arquivo/banco)
    error_log("Tentativa de login falha - Código: " . substr($codigo_acesso, 0, 3) . "*** - IP: " . $_SERVER['REMOTE_ADDR']);
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
            <div class="logo">
                <img src="../img/LOGOTIPO 1.avif" alt="logo">
            </div>
            <ul class="nav-links">
                <li><a href="../index.php">Voltar ao Início</a></li>
            </ul>
        </nav>
    </header>

    <main>
        <article class="identificar-aluno">
            <h1>Identificação do Aluno</h1>
            
            <?php if (isset($erro)): ?>
                <div>
                    <?php echo $erro; ?>
                </div>
            <?php endif; ?>
            
            <!-- Formulário para alunos já cadastrados -->
            <div>
                <div>
                    <h2>Já sou cadastrado</h2>
                    <p>Digite seu código de acesso:</p>
                    <form method="POST" action="identificar_aluno.php">
                        <div class="form-group">
                            <label for="codigo_acesso">Código de Acesso:</label>
                            <input type="text" id="codigo_acesso" name="codigo_acesso" required 
                                placeholder="Digite seu código de acesso">
                        </div>
                        <button type="submit">Entrar</button>
                    </form>
                </div>
                
                <!-- Opção para novos alunos -->
                <div>
                    <h2>Primeiro acesso</h2>
                    <p>Se é sua primeira vez, faça seu cadastro:</p>
                    <form method="POST" action="identificar_aluno.php">
                        <button type="submit" name="cadastrar" value="true">
                            Fazer Cadastro
                        </button>
                    </form>
                </div>
            </div>

            <div class="indentificar-links">
                <p><a href="../index.php">← Voltar para a página inicial</a></p>
            </div>

        </article>
    </main>

    <footer>
        <div class="footer-content">
            <ul class="footer-links">
                <li><a href="#">Como Usar a Plataforma</a></li>
                <li><a href="#">Materiais de Apoio</a></li>
                <li><a href="#">Suporte Técnico</a></li>
                <li><a href="#">Dúvidas Frequentes</a></li>
            </ul>
            <p class="copyright">© 2023 Edukhan - Plataforma de Avaliação Educacional. Todos os direitos reservados.</p>
        </div>
    </footer>
</body>
</html>