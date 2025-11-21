<?php
session_start();
// Verificar se é professor
if (!isset($_SESSION["logado"]) || $_SESSION["logado"] !== true || $_SESSION["tipo_usuario"] !== "professor") {
    header("Location: ../index.php");
    exit();
}

// SEGURANÇA: Validar ID do professor
if (!isset($_SESSION['idProfessor']) || !is_numeric($_SESSION['idProfessor'])) {
    header("Location: ../index.php");
    exit();
}

require_once __DIR__ . '/../config/funcoes_comuns.php';
$conectar = conectarBanco();

// SEGURANÇA: Verificar conexão
if (!$conectar) {
    error_log("Erro de conexão ao excluir prova");
    header("Location: gerenciar_provas.php?erro=conexao");
    exit();
}

// Verificar se foi passado um ID de prova para excluir
if (!isset($_GET['id']) || empty($_GET['id'])) {
    header("Location: gerenciar_provas.php?erro=id_nao_informado");
    exit();
}

// SEGURANÇA: Validar e sanitizar ID
$prova_id = (int)$_GET['id'];
if ($prova_id <= 0) {
    header("Location: gerenciar_provas.php?erro=id_invalido");
    exit();
}

// Verificar se a prova pertence ao professor - SEGURANÇA: Prepared Statement
$sql_verificar = "SELECT * FROM Provas WHERE idProvas = ? AND Professor_idProfessor = ?";
$stmt_verificar = mysqli_prepare($conectar, $sql_verificar);

if ($stmt_verificar) {
    mysqli_stmt_bind_param($stmt_verificar, "ii", $prova_id, $_SESSION['idProfessor']);
    mysqli_stmt_execute($stmt_verificar);
    $result_verificar = mysqli_stmt_get_result($stmt_verificar);

    if (mysqli_num_rows($result_verificar) === 0) {
        mysqli_stmt_close($stmt_verificar);
        header("Location: gerenciar_provas.php?erro=prova_nao_encontrada");
        exit();
    }

    $prova = mysqli_fetch_assoc($result_verificar);
    mysqli_stmt_close($stmt_verificar);
}

// Processar exclusão quando o formulário for submetido
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    if (isset($_POST['confirmar']) && $_POST['confirmar'] === 'sim') {
        
        // Iniciar transação para garantir que todas as exclusões sejam feitas
        mysqli_begin_transaction($conectar);
        
        try {
            // 1. Excluir tentativas/respostas dos alunos - SEGURANÇA: Prepared Statement
            $sql_excluir_aluno_provas = "DELETE FROM Aluno_Provas WHERE Provas_idProvas = ?";
            $stmt_aluno = mysqli_prepare($conectar, $sql_excluir_aluno_provas);
            if ($stmt_aluno) {
                mysqli_stmt_bind_param($stmt_aluno, "i", $prova_id);
                mysqli_stmt_execute($stmt_aluno);
                mysqli_stmt_close($stmt_aluno);
            }

            // 2. Excluir a prova - SEGURANÇA: Prepared Statement
            $sql_excluir_prova = "DELETE FROM Provas WHERE idProvas = ? AND Professor_idProfessor = ?";
            $stmt_prova = mysqli_prepare($conectar, $sql_excluir_prova);
            if ($stmt_prova) {
                mysqli_stmt_bind_param($stmt_prova, "ii", $prova_id, $_SESSION['idProfessor']);
                mysqli_stmt_execute($stmt_prova);
                mysqli_stmt_close($stmt_prova);
            }

            // Confirmar a transação
            mysqli_commit($conectar);

            header("Location: ../professores/gerenciar_provas.php?sucesso=prova_excluida");
            exit();

        } catch (Exception $e) {
            // Em caso de erro, reverter a transação
            mysqli_rollback($conectar);
            error_log("Erro ao excluir prova ID $prova_id: " . $e->getMessage());
            header("Location: ../professores/gerenciar_provas.php?erro=erro_exclusao");
            exit();
        }
        
    } else {
        // Se o usuário cancelou, redirecionar de volta
        header("Location: ../professores/gerenciar_provas.php");
        exit();
    }
}
?>

<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Excluir Prova - AvaliaEduca</title>
    <link rel="stylesheet" href="../css/style.css">
</head>
<body>
    <header>
        <nav>
            <div class="logo">
                <img src="../img/LOGOTIPO 1.avif" alt="logo">
            </div>
            <ul class="nav-links">
                <li><a href="../professores/dashboard_professor.php">Dashboard</a></li>
                <li><a href="../professores/gerenciar_provas.php">Minhas Provas</a></li>
                <li><a href="../logout.php">Sair</a></li>
            </ul>
        </nav>
    </header>

    <main>
        <article class="excluir-prova">
            <h1>Excluir Prova</h1>
            
            <div class="alerta-de-exclusao">
                <h2>⚠️ ATENÇÃO: Esta ação é irreversível!</h2>
                
                <p><strong>Você está prestes a excluir a seguinte prova:</strong></p>
                
                <div>
                    <p><strong>Título:</strong> <?php echo htmlspecialchars($prova['titulo']); ?></p>
                    <p><strong>Matéria:</strong> <?php echo htmlspecialchars($prova['materia']); ?></p>
                    <p><strong>Série Destinada:</strong> <?php echo htmlspecialchars($prova['serie_destinada']); ?></p>
                    <p><strong>Número de Questões:</strong> <?php echo htmlspecialchars($prova['numero_questoes']); ?></p>
                    <p><strong>Data de Criação:</strong> <?php echo date('d/m/Y H:i', strtotime($prova['data_criacao'])); ?></p>
                </div>

                <p><strong>Esta exclusão irá:</strong></p>
                <ul>
                    <li>Remover permanentemente a prova</li>
                    <li>Excluir todas as questões associadas</li>
                    <li>Remover qualquer histórico de respostas dos alunos</li>
                </ul>

                <p>Tem certeza que deseja continuar?</p>
            </div>

            <form action="excluir_prova.php?id=<?php echo $prova_id; ?>" method="POST">
                <div>
                    <label>
                        <input type="radio" name="confirmar" value="sim" required>
                        Sim, desejo excluir esta prova permanentemente
                    </label>
                    <br>
                    <label>
                        <input type="radio" name="confirmar" value="nao" checked>
                        Não, quero cancelar e voltar à lista de provas
                    </label>
                </div>

                <div>
                    <button type="submit">
                        CONFIRMAR EXCLUSÃO
                    </button>
                    <button type="button" onclick="window.location.href='../professores/gerenciar_provas.php'">
                        Cancelar
                    </button>
                </div>
            </form>
        </article>
    </main>

    <script>
        // Adicionar confirmação adicional antes do envio do formulário
        document.querySelector('form').addEventListener('submit', function(e) {
            const confirmacao = document.querySelector('input[name="confirmar"]:checked');
            
            if (confirmacao && confirmacao.value === 'sim') {
                const confirmacaoFinal = confirm('⚠️ ATENÇÃO FINAL!\n\nEsta ação é IRREVERSÍVEL!\nTem certeza absoluta que deseja excluir esta prova?\n\nTodas as questões e dados relacionados serão perdidos permanentemente.');
                
                if (!confirmacaoFinal) {
                    e.preventDefault();
                }
            }
        });

        // Prevenir o envio do formulário se a opção "sim" não estiver selecionada
        document.querySelector('button[type="submit"]').addEventListener('click', function(e) {
            const confirmacao = document.querySelector('input[name="confirmar"]:checked');
            
            if (!confirmacao || confirmacao.value !== 'sim') {
                e.preventDefault();
                alert('Por favor, selecione a opção de confirmação para excluir a prova.');
            }
        });
    </script>
</body>
</html>
