<?php
session_start();
// Verificar se é professor
if (!isset($_SESSION["logado"]) || $_SESSION["logado"] !== true || $_SESSION["tipo_usuario"] !== "professor") {
    header("Location: ../index.php");
    exit();
}

$conectar = mysqli_connect("localhost", "root", "", "projeto_residencia");

// Verificar se foi passado um ID de prova para excluir
if (!isset($_GET['id']) || empty($_GET['id'])) {
    header("Location: gerenciar_provas.php?erro=id_nao_informado");
    exit();
}

$prova_id = mysqli_real_escape_string($conectar, $_GET['id']);

// Verificar se a prova pertence ao professor

$sql_verificar = "SELECT * FROM Provas WHERE idProvas = '$prova_id' AND Professor_idProfessor = '{$_SESSION['idProfessor']}'";
$result_verificar = mysqli_query($conectar, $sql_verificar);

if (mysqli_num_rows($result_verificar) === 0) {
    header("Location: gerenciar_provas.php?erro=prova_nao_encontrada");
    exit();
}

$prova = mysqli_fetch_assoc($result_verificar);

// Processar exclusão quando o formulário for submetido
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    if (isset($_POST['confirmar']) && $_POST['confirmar'] === 'sim') {
        
        // Iniciar transação para garantir que todas as exclusões sejam feitas
        mysqli_begin_transaction($conectar);
        

        try {
            // 1. Excluir tentativas/respostas dos alunos
            $sql_excluir_aluno_provas = "DELETE FROM Aluno_Provas WHERE Provas_idProvas = '$prova_id'";
            mysqli_query($conectar, $sql_excluir_aluno_provas);

            // 2. Excluir a prova
            $sql_excluir_prova = "DELETE FROM Provas WHERE idProvas = '$prova_id'";
            mysqli_query($conectar, $sql_excluir_prova);

            // Confirmar a transação
            mysqli_commit($conectar);

            header("Location: ../professores/gerenciar_provas.php?sucesso=prova_excluida");
            exit();

        } catch (Exception $e) {
            // Em caso de erro, reverter a transação
            mysqli_rollback($conectar);
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
</head>
<body>
    <header>
        <nav>
            <div class="logo">AvaliaEduca - Excluir Prova</div>
            <ul class="nav-links">
                <li><a href="../home.php">Home</a></li>
                <li><a href="dashboard_professor.php">Dashboard</a></li>
                <li><a href="criar_prova.php">Criar Prova</a></li>
                <li><a href="gerenciar_provas.php">Minhas Provas</a></li>
                <li><a href="../logout.php">Sair</a></li>
            </ul>
        </nav>
    </header>

    <main>
        <article>
            <h1>Excluir Prova</h1>
            
            <div style="border: 2px solid #ff0000; padding: 20px; margin: 20px 0; background-color: #ffe6e6;">
                <h2 style="color: #ff0000;">⚠️ ATENÇÃO: Esta ação é irreversível!</h2>
                
                <p><strong>Você está prestes a excluir a seguinte prova:</strong></p>
                
                <div style="margin: 15px 0;">
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

                <p style="color: #ff0000; font-weight: bold;">Tem certeza que deseja continuar?</p>
            </div>

            <form action="excluir_prova.php?id=<?php echo $prova_id; ?>" method="POST">
                <div style="margin: 20px 0;">
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
                    <button type="submit" style="background-color: #ff0000; color: white; padding: 10px 20px; border: none; cursor: pointer;">
                        CONFIRMAR EXCLUSÃO
                    </button>
                    <button type="button" onclick="window.location.href='../professores/gerenciar_provas.php'" style="background-color: #666; color: white; padding: 10px 20px; border: none; cursor: pointer;">
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