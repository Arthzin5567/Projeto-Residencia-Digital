<?php
session_start();

// Verificar se o aluno está identificado
if (!isset($_SESSION['aluno_identificado'])) {
    echo "<script> 
            alert('Acesso negado! Identifique-se primeiro.');
            location.href = '../index.php';
          </script>";
    exit();
}

$conectar = mysqli_connect("localhost", "root", "", "projeto_residencia");
$aluno_id = $_SESSION['id_aluno'];

// Buscar dados do aluno
$sql_aluno = "SELECT * FROM Aluno WHERE idAluno = '$aluno_id'";
$result_aluno = mysqli_query($conectar, $sql_aluno);
$aluno = mysqli_fetch_assoc($result_aluno);

// Buscar provas disponíveis
$sql_provas = "SELECT p.*, ap.status, ap.nota, ap.data_realizacao 
               FROM Provas p 
               LEFT JOIN Aluno_Provas ap ON p.idProvas = ap.Provas_idProvas AND ap.Aluno_idAluno = '$aluno_id' 
               WHERE ap.Aluno_idAluno IS NULL OR ap.status = 'pendente'
               ORDER BY p.data_criacao DESC";
$result_provas = mysqli_query($conectar, $sql_provas);
$provas_disponiveis = $result_provas ? mysqli_num_rows($result_provas) : 0;

// Buscar provas realizadas
$sql_realizadas = "SELECT COUNT(*) as total FROM Aluno_Provas 
                   WHERE Aluno_idAluno = '$aluno_id' AND status = 'realizada'";
$result_realizadas = mysqli_query($conectar, $sql_realizadas);
$provas_realizadas = $result_realizadas ? mysqli_fetch_assoc($result_realizadas)['total'] : 0;
?>

<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard Aluno - AvaliaEduca</title>
</head>
<body>
    <header>
        <nav>
            <div class="logo">AvaliaEduca - Aluno</div>
            <ul class="nav-links">
                <li><a href="dashboard_aluno.php">Dashboard</a></li>
                <li><a href="provas_disponiveis.php">Provas</a></li>
                <li><a href="historico.php">Desempenho</a></li>
                <li><a href="perfil.php">Meu Perfil</a></li>
                <li><a href="../logout.php" class="btn">Sair</a></li>
            </ul>
        </nav>
    </header>

    <main>
        <article>
            <section>
                <h1>Dashboard do Aluno</h1>
                <p>Bem-vindo, <strong><?php echo $_SESSION['nome_aluno']; ?></strong>! 👋</p>
                <p>Seu código de acesso: <strong><?php echo $aluno['codigo_acesso']; ?></strong></p>
            </section>

            <!-- CARDS DE RESUMO -->
            <section>
                <div>
                    <h3>📝 Provas Disponíveis</h3>
                    <p><?php echo $provas_disponiveis; ?></p>
                    <p>Avaliações para realizar</p>
                    <a href="provas_disponiveis.php">
                        Ver Provas
                    </a>
                </div>

                <div>
                    <h3>📊 Provas Realizadas</h3>
                    <p><?php echo $provas_realizadas; ?></p>
                    <p>Avaliações concluídas</p>
                    <a href="historico.php">
                        Ver Histórico
                    </a>
                </div>

                <div>
                    <h3>👤 Meu Perfil</h3>
                    <p>📋</p>
                    <p>Dados pessoais</p>
                    <a href="perfil.php">
                        Ver Perfil
                    </a>
                </div>
            </section>

             <!-- PROVAS DISPONÍVEIS -->
            <section style="margin-bottom: 30px;">
                <h2>📚 Provas Disponíveis para Realizar</h2>
                
                <?php if ($provas_disponiveis > 0): ?>
                    <div>
                        <?php while ($prova = mysqli_fetch_assoc($result_provas)): ?>
                            <div>
                                <!-- CORREÇÃO: Mostrar título ao invés do conteúdo JSON -->
                                <h4><?php echo htmlspecialchars($prova['titulo'] ?: $prova['materia'] . ' - Prova'); ?></h4>
                                <p><strong>Matéria:</strong> <?php echo htmlspecialchars($prova['materia']); ?></p>
                                <p><strong>Série:</strong> <?php echo htmlspecialchars($prova['serie_destinada']); ?></p>
                                <p><strong>Questões:</strong> <?php echo htmlspecialchars($prova['numero_questoes']); ?></p>
                                <a href="fazer_prova.php?id=<?php echo $prova['idProvas']; ?>">
                                    Iniciar Prova
                                </a>
                            </div>
                        <?php endwhile; ?>
                    </div>
                <?php else: ?>
                    <p>
                        🎉 Não há provas disponíveis no momento. Parabéns por estar em dia!
                    </p>
                <?php endif; ?>
            </section>

            <!-- AÇÕES RÁPIDAS -->
            <section>
                <h2>⚡ Ações Rápidas</h2>
                <div>
                    <a href="provas_disponiveis.php">
                        <h4>📝 Todas as Provas</h4>
                        <p>Veja todas as avaliações disponíveis</p>
                    </a>
                    
                    <a href="historico.php">
                        <h4>📊 Meu Desempenho</h4>
                        <p>Consulte suas notas e resultados</p>
                    </a>
                    
                    <a href="perfil.php">
                        <h4>👤 Meus Dados</h4>
                        <p>Atualize suas informações</p>
                    </a>
                </div>
            </section>
        </article>
    </main>

    <footer>
        <p>&copy; 2023 AvaliaEduca - Área do Aluno</p>
        <p><small>Seu código de acesso: <strong><?php echo $aluno['codigo_acesso']; ?></strong></small></p>
    </footer>
</body>
</html>

<?php mysqli_close($conectar); ?>