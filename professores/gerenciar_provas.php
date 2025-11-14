<?php
session_start();
// Verificar se é professor
if (!isset($_SESSION["logado"]) || $_SESSION["logado"] !== true || $_SESSION["tipo_usuario"] !== "professor") {
    header("Location: ../index.php");
    exit();
}

$host = "localhost";
$user = "root";
$password = "SenhaIrada@2024!";
$database = "projeto_residencia";
$conectar = mysqli_connect($host, $user, $password, $database);

// Buscar as provas do professor
$professor_id = $_SESSION["idProfessor"];
$sql_provas = "SELECT * FROM Provas WHERE Professor_idProfessor = $professor_id ORDER BY data_criacao DESC";
$resultado_provas = mysqli_query($conectar, $sql_provas);
?>

<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gerenciar Provas - Edukhan</title>
    <link rel="stylesheet" href="../css/style.css">
    <!-- KaTeX CSS -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/katex@0.16.9/dist/katex.min.css">
</head>
<body>
    <header>
        <nav>
            <div class="logo">
                <img src="../img/LOGOTIPO 1.avif" alt="logo">
            </div>
            <ul class="nav-links">
                <li><a href="dashboard_professor.php">Dashboard</a></li>
                <li><a href="gerenciar_alunos.php">Alunos</a></li>
                <li><a href="criar_prova.php">Criar Prova</a></li>
                <li><a href="perfil_professor.php">Meu Perfil</a></li>
                <li><a href="../logout.php">Sair</a></li>
            </ul>
        </nav>
    </header>

    <main>
        <article class="gerenciar-provas-container">
            <h1>Minhas Provas</h1>
            
            <div class="provas-container">
                <?php if (mysqli_num_rows($resultado_provas) > 0): ?>
                    <?php while ($prova = mysqli_fetch_assoc($resultado_provas)): 
                        // Decodificar o JSON para contar questões
                        $conteudo = json_decode($prova['conteudo'], true);
                        $num_questoes = is_array($conteudo) ? count($conteudo) : 0;
                    ?>
                        <div class="prova-card">
                            <div class="prova-header">
                                <div class="prova-title"><?php echo htmlspecialchars($prova['titulo']); ?></div>
                                <div>
                                    ID: <?php echo $prova['idProvas']; ?> | 
                                    Criada em: <?php echo date('d/m/Y', strtotime($prova['data_criacao'])); ?>
                                </div>
                            </div>
                            
                            <div class="prova-details">
                                <p><strong>Matéria:</strong> <?php echo htmlspecialchars($prova['materia']); ?></p>
                                <p><strong>Série:</strong> <?php echo htmlspecialchars($prova['serie_destinada']); ?></p>
                                <p><strong>Questões:</strong> <?php echo $num_questoes; ?></p>
                                <?php 
                                    $ativa = isset($prova['ativa']) ? $prova['ativa'] : 0;
                                ?>
                                <p><strong>Status:</strong> 
                                    <span>
                                        <?php echo $ativa ? 'Ativa' : 'Inativa'; ?>
                                    </span>
                                </p>
                            </div>
                            
                            <div class="prova-actions">
                                <a href="visualizar_prova.php?id=<?php echo $prova['idProvas']; ?>" class="btn btn-visualizar">Visualizar</a>
                                <a href="editar_prova.php?id=<?php echo $prova['idProvas']; ?>" class="btn btn-editar">Editar</a>
                                <a href="../includes/excluir_prova.php?id=<?php echo $prova['idProvas']; ?>" 
                                   class="btn btn-excluir" 
                                   onclick="return confirm('Tem certeza que deseja excluir esta prova?')">Excluir</a>
                                
                                <!-- Botão para ativar/desativar -->
                                <?php if ($ativa): ?>
                                                <a href="../includes/desativar_prova.php?id=<?php echo $prova['idProvas']; ?>" class="btn">Desativar</a>
                                <?php else: ?>
                                                <a href="../includes/ativar_prova.php?id=<?php echo $prova['idProvas']; ?>" class="btn">Ativar</a>
                                <?php endif; ?>
                                
                                <!-- Botão para ver resultados -->
                                          <a href="resultados_prova.php?id=<?php echo $prova['idProvas']; ?>" class="btn">Resultados</a>
                            </div>
                        </div>
                    <?php endwhile; ?>
                <?php else: ?>
                    <div class="empty-state">
                        <h3>Nenhuma prova encontrada</h3>
                        <p>Você ainda não criou nenhuma prova. <a href="criar_prova.php">Clique aqui para criar a primeira!</a></p>
                    </div>
                <?php endif; ?>
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

    <!-- KaTeX JS -->
    <script defer src="https://cdn.jsdelivr.net/npm/katex@0.16.9/dist/katex.min.js"></script>
    <script defer src="https://cdn.jsdelivr.net/npm/katex@0.16.9/dist/contrib/auto-render.min.js"></script>
    <script src="../js/math-config.js"></script>

    <script>
        // Função para buscar estatísticas das provas (opcional)
        function carregarEstatisticas() {
            // Aqui você pode adicionar AJAX para carregar estatísticas em tempo real
            console.log("Carregando estatísticas das provas...");
        }
        
        // Carregar estatísticas quando a página carregar
        document.addEventListener('DOMContentLoaded', function() {
            carregarEstatisticas();
        });
    </script>
</body>
</html>

<?php mysqli_close($conectar); ?>