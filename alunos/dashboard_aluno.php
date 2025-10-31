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

// Buscar provas corrigidas
$sql_corrigidas = "SELECT COUNT(*) as total FROM Aluno_Provas 
                   WHERE Aluno_idAluno = '$aluno_id' AND status = 'corrigida'";
$result_corrigidas = mysqli_query($conectar, $sql_corrigidas);
$provas_corrigidas = $result_corrigidas ? mysqli_fetch_assoc($result_corrigidas)['total'] : 0;
?>

<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard do Aluno - Edukhan</title>
    <link rel="stylesheet" href="../css/style.css">
</head>
<body>
    <header>
        <nav>
            <div class="logo">
                <img src="../img/LOGOTIPO 1.avif" alt="logo">
            </div>
            <ul class="nav-links">
                <li><a href="dashboard_aluno.php">Dashboard</a></li>
                <li><a href="provas_disponiveis.php">Provas</a></li>
                <li><a href="historico.php">Desempenho</a></li>
                <li><a href="perfil.php">Meu Perfil</a></li>
                <li><a href="../logout.php">Sair</a></li>
            </ul>
        </nav>
    </header>

    <main>
        <article class="dashboard-aluno">
            <section class="welcome-professor">
                <h1>🎓 Dashboard do Aluno</h1>
                <p>Bem-vindo, <strong><?php echo $_SESSION['nome_aluno']; ?></strong>! 👋</p>
                <p><strong>Código de acesso:</strong> <?php echo $aluno['codigo_acesso']; ?></p>
            </section>

            <!-- RESUMO ESTATÍSTICO -->
            <section class="resumo-estatistico">
                <h2>📊 Resumo do Seu Desempenho</h2>
                <div>
                    <div>
                        <h3>📝 Disponível</h3>
                        <div><?php echo $provas_disponiveis; ?></div>
                        <p>Provas para realizar</p>
                    </div>
                    <div>
                        <h3>⏳ Em Correção</h3>
                        <div><?php echo $provas_realizadas; ?></div>
                        <p>Aguardando correção</p>
                    </div>
                    <div>
                        <h3>✅ Corrigidas</h3>
                        <div><?php echo $provas_corrigidas; ?></div>
                        <p>Com nota disponível</p>
                    </div>
                </div>
            </section>


            <!-- PRÓXIMAS AÇÕES -->
            <section class="aluno-proximas-acoes">
                <h2>🚀 Suas Próximas Ações</h2>
                
                <?php if ($provas_disponiveis > 0): ?>
                    <!-- SE HÁ PROVAS DISPONÍVEIS -->
                    <div>
                        <h3>📚 Continue Estudando</h3>
                        <p>Você tem <strong><?php echo $provas_disponiveis; ?> prova(s)</strong> disponível(is) para realizar.</p>
                        
                        <div>
                            <?php while ($prova = mysqli_fetch_assoc($result_provas)): ?>
                                <div>
                                    <h4><?php echo htmlspecialchars($prova['titulo'] ?: $prova['materia'] . ' - Prova'); ?></h4>
                                    <p>
                                        <strong>Matéria:</strong> <?php echo htmlspecialchars($prova['materia']); ?> | 
                                        <strong>Questões:</strong> <?php echo htmlspecialchars($prova['numero_questoes']); ?> | 
                                        <strong>Série:</strong> <?php echo htmlspecialchars($prova['serie_destinada']); ?>
                                    </p>
                                    <a href="fazer_prova.php?id=<?php echo $prova['idProvas']; ?>">
                                        🚀 Iniciar Prova
                                    </a>
                                </div>
                            <?php endwhile; ?>
                        </div>
                        
                        <a href="provas_disponiveis.php">
                            📋 Ver Todas as Provas Disponíveis
                        </a>
                    </div>
                <?php else: ?>
                    <!-- SE NÃO HÁ PROVAS DISPONÍVEIS -->
                    <div>
                        <h3>🎉 Parabéns!</h3>
                        <p>Você está em dia com todas as avaliações disponíveis.</p>
                        <p><strong>O que você gostaria de fazer agora?</strong></p>
                        
                        <div>
                            <a href="historico.php">
                                📊 Ver Meu Desempenho
                            </a>
                            <a href="perfil.php">
                                👤 Atualizar Perfil
                            </a>
                        </div>
                    </div>
                <?php endif; ?>
            </section>

            <!-- ACESSO RÁPIDO -->
            <section class="actions-container-aluno">
                <h2>⚡ Acesso Rápido</h2>
                <div class="action-card-aluno">
                    <div class="acoes-aluno">
                        <a href="provas_disponiveis.php">
                            <h3>📝 Provas</h3>
                            <p>Ver todas as avaliações</p>
                            <small>▶️ Acessar</small>
                        </a>
                    </div>
                    <div class="acoes-aluno">
                        <a href="historico.php">
                            <h3>📊 Desempenho</h3>
                            <p>Consultar notas e resultados</p>
                            <small>▶️ Acessar</small>
                        </a>
                    </div>
                    <div class="acoes-aluno">
                        <a href="perfil.php">
                            <h3>👤 Perfil</h3>
                            <p>Atualizar informações</p>
                            <small>▶️ Acessar</small>
                        </a>
                    </div>
                </div>
            </section>
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

<?php mysqli_close($conectar); ?>