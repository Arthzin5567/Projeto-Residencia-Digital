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

// Buscar dados do professor
$professor_id = $_SESSION['idProfessor'];
$sql_professor = "SELECT * FROM Professor WHERE idProfessor = '$professor_id'";
$result_professor = mysqli_query($conectar, $sql_professor);
$professor = mysqli_fetch_assoc($result_professor);

// Processar pesquisa
$pesquisa = "";
$where_condition = "1=1";

if (isset($_GET['pesquisa']) && !empty($_GET['pesquisa'])) {
    $pesquisa = mysqli_real_escape_string($conectar, $_GET['pesquisa']);
    
    // Verificar se é número (ID) ou texto (nome)
    if (is_numeric($pesquisa)) {
        $where_condition = "a.idAluno = '$pesquisa'";
    } else {
        $where_condition = "a.nome LIKE '%$pesquisa%'";
    }
}

// Buscar alunos com estatísticas
$sql_alunos = "SELECT 
                a.idAluno,
                a.nome,
                a.email,
                a.escolaridade,
                a.data_cadastro,
                COUNT(ap.idRegistro_prova) as total_provas,
                AVG(ap.nota) as media_geral,
                MAX(ap.nota) as melhor_nota,
                MIN(ap.nota) as pior_nota,
                SUM(CASE WHEN ap.nota >= 7 THEN 1 ELSE 0 END) as provas_aprovadas
               FROM Aluno a
               LEFT JOIN Aluno_Provas ap ON a.idAluno = ap.Aluno_idAluno 
               AND (ap.status = 'realizada' OR ap.status = 'corrigida')
               WHERE $where_condition
               GROUP BY a.idAluno, a.nome, a.email, a.escolaridade, a.data_cadastro
               ORDER BY a.nome ASC";

$result_alunos = mysqli_query($conectar, $sql_alunos);
$total_alunos = mysqli_num_rows($result_alunos);
?>

<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gerenciar Alunos - Edukhan</title>
    <link rel="stylesheet" href="../css/style.css">
</head>
<body>
    <header>
        <nav>
            <div class="logo">
                <img src="../img/LOGOTIPO 1.avif" alt="logo">
            </div>
            <ul class="nav-links">
                <li><a href="dashboard_professor.php">Dashboard</a></li>
                <li><a href="gerenciar_provas.php">Minhas Provas</a></li>
                <li><a href="criar_prova.php">Criar Provas</a></li>
                <li><a href="perfil_professor.php">Meu Perfil</a></li>
                <li><a href="../logout.php">Sair</a></li>
            </ul>
        </nav>
    </header>

    <main>
        <article class="gerenciar-alunos">
            <section class="gerenciar-alunos-informacoes">
                <h1>👥 Gerenciar Alunos</h1>
                <p>Visualize e pesquise por alunos cadastrados no sistema</p>
                
                <!-- Formulário de Pesquisa -->
                <form method="GET" action="gerenciar_alunos.php">
                    <div>
                        <input type="text" name="pesquisa" value="<?php echo htmlspecialchars($pesquisa); ?>" 
                               placeholder="Pesquisar por ID ou nome do aluno..." size="50">
                        <button type="submit">Pesquisar</button>
                    </div>
                </form>

                <!-- Informações dos Resultados -->
                <div class="gerenciar-alunos-resultado-pesquisa">
                    <p><strong><?php echo $total_alunos; ?></strong> aluno(s) encontrado(s)
                    <?php if (!empty($pesquisa)): ?>
                        para "<strong><?php echo htmlspecialchars($pesquisa); ?></strong>"
                        <a href="gerenciar_alunos.php">[Limpar pesquisa]</a>
                    <?php endif; ?>
                    </p>
                </div>
            </section>

            <!-- LISTA DE ALUNOS -->
            <section class="gerenciar-alunos-lista">
                <h2>📋 Lista de Alunos</h2>
                
                <?php if ($total_alunos > 0): ?>
                    <div class="gerenciar-alunos-cards">
                        <?php while ($aluno = mysqli_fetch_assoc($result_alunos)): 
                            // Calcular taxa de aprovação
                            $taxa_aprovacao = $aluno['total_provas'] > 0 ? 
                                ($aluno['provas_aprovadas'] / $aluno['total_provas']) * 100 : 0;
                            
                            // Definir cor da média
                            $media_class = 'media-media';
                            if ($aluno['media_geral'] >= 7) {
                                $media_class = 'media-alta';
                            } elseif ($aluno['media_geral'] < 5) {
                                $media_class = 'media-baixa';
                            }
                        ?>
                            <div>
                                <div>
                                    <div>
                                        <h3><?php echo htmlspecialchars($aluno['nome']); ?></h3>
                                        <div>
                                            <strong>ID:</strong> <?php echo $aluno['idAluno']; ?> | 
                                            <strong>Série:</strong> <?php echo htmlspecialchars($aluno['escolaridade']); ?> | 
                                            <strong>Email:</strong> <?php echo htmlspecialchars($aluno['email']); ?>
                                        </div>
                                    </div>
                                    <div>
                                        <a href="perfil_aluno.php?id=<?php echo $aluno['idAluno']; ?>">
                                            👁️ Ver Perfil Completo
                                        </a>
                                    </div>
                                </div>

                                <!-- Estatísticas do Aluno -->
                                <div>
                                    <div>
                                        <div><strong>Provas Realizadas</strong></div>
                                        <div><?php echo $aluno['total_provas']; ?></div>
                                    </div>
                                    
                                    <div>
                                        <div><strong>Média Geral</strong></div>
                                        <div>
                                            <?php echo $aluno['media_geral'] ? number_format($aluno['media_geral'], 1) : '0.0'; ?>
                                        </div>
                                    </div>
                                    
                                    <div>
                                        <div><strong>Melhor Nota</strong></div>
                                        <div>
                                            <?php echo $aluno['melhor_nota'] ? number_format($aluno['melhor_nota'], 1) : '0.0'; ?>
                                        </div>
                                    </div>
                                    
                                    <div>
                                        <div><strong>Taxa de Aprovação</strong></div>
                                        <div>
                                            <?php echo number_format($taxa_aprovacao, 1); ?>%
                                        </div>
                                    </div>
                                </div>

                                <!-- Informações adicionais -->
                                <div>
                                    <div>
                                        <strong>Cadastrado em:</strong> <?php echo date('d/m/Y', strtotime($aluno['data_cadastro'])); ?>
                                    </div>
                                    <div>
                                        <strong>Provas realizadas:</strong> <?php echo $aluno['total_provas']; ?>
                                    </div>
                                </div>
                            </div>
                        <?php endwhile; ?>
                    </div>
                <?php else: ?>
                    <div class="bnt-all-provas">
                        <h3>📭 Nenhum aluno encontrado</h3>
                        <p>
                            <?php if (!empty($pesquisa)): ?>
                                Não foram encontrados alunos para "<strong><?php echo htmlspecialchars($pesquisa); ?></strong>".
                            <?php else: ?>
                                Não há alunos cadastrados no sistema.
                            <?php endif; ?>
                        </p>
                        <?php if (!empty($pesquisa)): ?>
                            <a href="gerenciar_alunos.php">
                                Ver todos os alunos
                            </a>
                        <?php endif; ?>
                    </div>
                <?php endif; ?>
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
            <p><small>Total de alunos no sistema: <strong><?php echo $total_alunos; ?></strong></small></p>
        </div>
    </footer>
</body>
</html>

<?php mysqli_close($conectar); ?>