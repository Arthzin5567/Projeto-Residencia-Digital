<?php
session_start();

// Verificar se é professor
if (!isset($_SESSION["logado"]) || $_SESSION["logado"] !== true || $_SESSION["tipo_usuario"] !== "professor") {
    echo "<script> 
            alert('Acesso negado para professores!');
            location.href = '../index.php';
          </script>";
    exit();
}

$conectar = mysqli_connect("localhost", "root", "", "projeto_residencia");
$professor_id = $_SESSION['idProfessor'];

// Buscar dados do professor
$sql_professor = "SELECT * FROM Professor WHERE idProfessor = '$professor_id'";
$result_professor = mysqli_query($conectar, $sql_professor);
$professor = mysqli_fetch_assoc($result_professor);

// Estatísticas - Total de alunos
$sql_total_alunos = "SELECT COUNT(*) as total FROM Aluno";
$result_total_alunos = mysqli_query($conectar, $sql_total_alunos);
$total_alunos = mysqli_fetch_assoc($result_total_alunos)['total'];

// Estatísticas - Total de provas criadas pelo professor
$sql_total_provas = "SELECT COUNT(*) as total FROM Provas WHERE Professor_idProfessor = '$professor_id'";
$result_total_provas = mysqli_query($conectar, $sql_total_provas);
$total_provas = mysqli_fetch_assoc($result_total_provas)['total'];

// Estatísticas - Provas realizadas
$sql_provas_realizadas = "SELECT COUNT(DISTINCT ap.Provas_idProvas) as total 
                          FROM Aluno_Provas ap 
                          INNER JOIN Provas p ON ap.Provas_idProvas = p.idProvas 
                          WHERE p.Professor_idProfessor = '$professor_id' 
                          AND ap.status IN ('realizada', 'corrigida')";
$result_provas_realizadas = mysqli_query($conectar, $sql_provas_realizadas);
$provas_realizadas = mysqli_fetch_assoc($result_provas_realizadas)['total'];

// Últimas provas criadas
$sql_ultimas_provas = "SELECT * FROM Provas 
                       WHERE Professor_idProfessor = '$professor_id' 
                       ORDER BY data_criacao DESC 
                       LIMIT 5";
$result_ultimas_provas = mysqli_query($conectar, $sql_ultimas_provas);
?>

<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard Professor - AvaliaEduca</title>
    
</head>
<body>
    <header>
        <nav style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 30px;">
            <div class="logo" style="font-size: 24px; font-weight: bold;">AvaliaEduca - Professor</div>
            <ul class="nav-links">
                <li><a href="dashboard_professor.php">Dashboard</a></li>
                <li><a href="lista_alunos.php">Alunos</a></li>
                <li><a href="criar_prova.php">Avaliações</a></li>
                <li><a href="gerenciar_provas.php">Resultados</a></li>
                <li><a href="perfil_professor.php">Meu Perfil</a></li>
                <li><a href="../logout.php" class="btn">Sair</a></li>
            </ul>
        </nav>
    </header>

    <main class="container">
        <article>
            <section class="card" style="margin-bottom: 30px;">
                <h1>🎓 Dashboard do Professor</h1>
                <p>Bem-vindo, <strong><?php echo $_SESSION['usuario']; ?></strong>! 👋</p>
                <p><strong>Email:</strong> <?php echo $professor['email']; ?></p>
            </section>

            <!-- ESTATÍSTICAS -->
            <section class="stats-grid">
                <div class="stat-card stat-alunos">
                    <h3>👥 Total de Alunos</h3>
                    <div class="stat-number"><?php echo $total_alunos; ?></div>
                    <p>Alunos cadastrados</p>
                </div>

                <div class="stat-card stat-provas">
                    <h3>📝 Provas Criadas</h3>
                    <div class="stat-number"><?php echo $total_provas; ?></div>
                    <p>Suas avaliações</p>
                </div>

                <div class="stat-card stat-realizadas">
                    <h3>📊 Provas Realizadas</h3>
                    <div class="stat-number"><?php echo $provas_realizadas; ?></div>
                    <p>Avaliações concluídas</p>
                </div>

                <div class="stat-card stat-ativo">
                    <h3>⭐ Status</h3>
                    <div class="stat-number">Ativo</div>
                    <p>Professor</p>
                </div>
            </section>

            <!-- AÇÕES RÁPIDAS -->
            <section class="card">
                <h2>⚡ Ações Rápidas</h2>
                <div class="actions-grid">
                    <a href="gerenciar_alunos.php" class="action-card action-alunos">
                        <h3>👥 Gerenciar Alunos</h3>
                        <p>Visualize, edite e pesquise alunos cadastrados</p>
                        <small>▶️ Acessar</small>
                    </a>
                    
                    <a href="criar_prova.php" class="action-card action-provas">
                        <h3>📝 Criar Avaliação</h3>
                        <p>Elabore novas provas para os alunos</p>
                        <small>▶️ Acessar</small>
                    </a>
                    
                    <a href="gerenciar_provas.php" class="action-card action-resultados">
                        <h3>📊 Ver Resultados</h3>
                        <p>Analise o desempenho dos alunos</p>
                        <small>▶️ Acessar</small>
                    </a>
                    
                    <a href="perfil_professor.php" class="action-card action-perfil">
                        <h3>👤 Meu Perfil</h3>
                        <p>Atualize suas informações pessoais</p>
                        <small>▶️ Acessar</small>
                    </a>
                </div>
            </section>

            <!-- ÚLTIMAS PROVAS CRIADAS -->
            <section class="card">
                <h2>📋 Suas Últimas Provas</h2>
                
                <?php if (mysqli_num_rows($result_ultimas_provas) > 0): ?>
                    <div style="margin-top: 15px;">
                        <?php while ($prova = mysqli_fetch_assoc($result_ultimas_provas)): ?>
                            <div class="prova-item">
                                <div style="display: flex; justify-content: space-between; align-items: center;">
                                    <div>
                                        <h4 style="margin: 0 0 5px 0;"><?php echo htmlspecialchars($prova['titulo'] ?: $prova['materia'] . ' - Prova'); ?></h4>
                                        <p style="margin: 0;">
                                            <strong>Matéria:</strong> <?php echo htmlspecialchars($prova['materia']); ?> | 
                                            <strong>Questões:</strong> <?php echo htmlspecialchars($prova['numero_questoes']); ?> | 
                                            <strong>Série:</strong> <?php echo htmlspecialchars($prova['serie_destinada']); ?>
                                        </p>
                                    </div>
                                    <small><?php echo date('d/m/Y', strtotime($prova['data_criacao'])); ?></small>
                                </div>
                            </div>
                        <?php endwhile; ?>
                    </div>
                    <div style="text-align: center; margin-top: 15px;">
                        <a href="gerenciar_provas.php" class="btn">Ver Todas as Provas</a>
                    </div>
                <?php else: ?>
                    <p style="text-align: center; padding: 20px; color: #666;">
                        📭 Você ainda não criou nenhuma prova.
                    </p>
                    <div style="text-align: center;">
                        <a href="criar_prova.php" class="btn">Criar Primeira Prova</a>
                    </div>
                <?php endif; ?>
            </section>
        </article>
    </main>

    <footer>
        <p>&copy; 2023 AvaliaEduca - Área do Professor</p>
    </footer>
</body>
</html>

<?php mysqli_close($conectar); ?>