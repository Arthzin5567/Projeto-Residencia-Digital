<?php
session_start();
require_once __DIR__ . '/../config/funcoes_comuns.php';

$aluno_id = verificarLoginAluno();
$conectar = conectarBanco();

//  Buscar dados do aluno
$sql_aluno = "SELECT * FROM Aluno WHERE idAluno = ?";
$stmt_aluno = mysqli_prepare($conectar, $sql_aluno);
mysqli_stmt_bind_param($stmt_aluno, "i", $aluno_id); // "i" = integer
mysqli_stmt_execute($stmt_aluno);
$result_aluno = mysqli_stmt_get_result($stmt_aluno);
$aluno = mysqli_fetch_assoc($result_aluno);
mysqli_stmt_close($stmt_aluno);

//  Buscar provas disponíveis
$sql_provas = "SELECT p.*, ap.status, ap.nota, ap.data_realizacao
               FROM Provas p
               LEFT JOIN Aluno_Provas ap ON p.idProvas = ap.Provas_idProvas AND ap.Aluno_idAluno = ?
               WHERE (ap.Aluno_idAluno IS NULL OR ap.status = 'pendente')
               AND p.ativa = 1
               ORDER BY p.data_criacao DESC";
$stmt_provas = mysqli_prepare($conectar, $sql_provas);
mysqli_stmt_bind_param($stmt_provas, "i", $aluno_id);
mysqli_stmt_execute($stmt_provas);
$result_provas = mysqli_stmt_get_result($stmt_provas);
$provas_disponiveis = $result_provas ? mysqli_num_rows($result_provas) : 0;
mysqli_stmt_close($stmt_provas);

//  Buscar provas realizadas
$sql_realizadas = "SELECT COUNT(*) as total FROM Aluno_Provas
                   WHERE Aluno_idAluno = ? AND status = 'realizada'";
$stmt_realizadas = mysqli_prepare($conectar, $sql_realizadas);
mysqli_stmt_bind_param($stmt_realizadas, "i", $aluno_id);
mysqli_stmt_execute($stmt_realizadas);
$result_realizadas = mysqli_stmt_get_result($stmt_realizadas);
$provas_realizadas = $result_realizadas ? mysqli_fetch_assoc($result_realizadas)['total'] : 0;
mysqli_stmt_close($stmt_realizadas);

//  Buscar provas corrigidas
$sql_corrigidas = "SELECT COUNT(*) as total FROM Aluno_Provas
                   WHERE Aluno_idAluno = ? AND status = 'corrigida'";
$stmt_corrigidas = mysqli_prepare($conectar, $sql_corrigidas);
mysqli_stmt_bind_param($stmt_corrigidas, "i", $aluno_id);
mysqli_stmt_execute($stmt_corrigidas);
$result_corrigidas = mysqli_stmt_get_result($stmt_corrigidas);
$provas_corrigidas = $result_corrigidas ? mysqli_fetch_assoc($result_corrigidas)['total'] : 0;
mysqli_stmt_close($stmt_corrigidas);
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
            <p>Bem-vindo, <strong><?php echo htmlspecialchars($_SESSION['nome_aluno'] ?? ''); ?></strong>! 👋</p>
            <p><strong>Código de acesso:</strong> <?php echo htmlspecialchars($aluno['codigo_acesso'] ?? ''); ?></p>
        </section>

        <!-- RESUMO ESTATÍSTICO -->
        <section class="resumo-estatistico">
            <h2>📊 Resumo do Seu Desempenho</h2>
            <div>
                <div>
                    <h3>📝 Disponível</h3>
                    <div><?php echo (int)$provas_disponiveis; ?></div>
                    <p>Provas para realizar</p>
                </div>
                <div>
                    <h3>⏳ Em Correção</h3>
                    <div><?php echo (int)$provas_realizadas; ?></div>
                    <p>Aguardando correção</p>
                </div>
                <div>
                    <h3>✅ Corrigidas</h3>
                    <div><?php echo (int)$provas_corrigidas; ?></div>
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
                    <p>Você tem <strong><?php echo (int)$provas_disponiveis; ?> prova(s)</strong> disponível(is) para realizar.</p>
                    
                    <div>
                        <?php
                        mysqli_data_seek($result_provas, 0);
                        while ($prova = mysqli_fetch_assoc($result_provas)){
                        ?>
                            <div>
                                <h4><?php echo htmlspecialchars($prova['titulo'] ?: $prova['materia'] . ' - Prova'); ?></h4>
                                <p>
                                    <strong>Matéria:</strong> <?php echo htmlspecialchars($prova['materia']); ?> |
                                    <strong>Questões:</strong> <?php echo (int)$prova['numero_questoes']; ?> |
                                    <strong>Série:</strong> <?php echo htmlspecialchars($prova['serie_destinada']); ?>
                                </p>
                                <!-- ✅ LINK SEGURO - ID convertido para inteiro -->
                                <a href="fazer_prova.php?id=<?php echo (int)$prova['idProvas']; ?>">
                                    🚀 Iniciar Prova
                                </a>
                            </div>
                        <?php } ?>
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
