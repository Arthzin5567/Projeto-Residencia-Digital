<?php
session_start();
// Verificar se é professor
if (!isset($_SESSION["logado"]) || $_SESSION["logado"] !== true || $_SESSION["tipo_usuario"] !== "professor") {
    header("Location: ../index.php");
    exit();
}

$conectar = mysqli_connect("localhost", "root", "", "projeto_residencia");

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
    <title>Gerenciar Provas - AvaliaEduca</title>
</head>
<body>
    <header>
        <nav>
            <div class="logo">AvaliaEduca - Gerenciar Provas</div>
            <ul class="nav-links">
                <li><a href="dashboard_professor.php">Dashboard</a></li>
                <li><a href="criar_prova.php">Criar Prova</a></li>
                <li><a href="gerenciar_provas.php">Minhas Provas</a></li>
                <li><a href="../logout.php">Sair</a></li>
            </ul>
        </nav>
    </header>

    <main>
        <article>
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
                                <div class="prova-info">
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