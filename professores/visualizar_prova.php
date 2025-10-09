<?php
session_start();
// Verificar se é professor
if (!isset($_SESSION["logado"]) || $_SESSION["logado"] !== true || $_SESSION["tipo_usuario"] !== "professor") {
    header("Location: ../index.php");
    exit();
}

$conectar = mysqli_connect("localhost", "root", "", "projeto_residencia");

// Verificar se o ID da prova foi passado
if (!isset($_GET['id'])) {
    header("Location: gerenciar_provas.php");
    exit();
}

$prova_id = (int)$_GET['id'];
$professor_id = $_SESSION["idProfessor"];

// Buscar os dados da prova
$sql_prova = "SELECT * FROM Provas WHERE idProvas = $prova_id AND Professor_idProfessor = $professor_id";
$resultado_prova = mysqli_query($conectar, $sql_prova);

if (mysqli_num_rows($resultado_prova) === 0) {
    header("Location: gerenciar_provas.php?erro=Prova não encontrada");
    exit();
}

$prova = mysqli_fetch_assoc($resultado_prova);
$conteudo = json_decode($prova['conteudo'], true);
$num_questoes = is_array($conteudo) ? count($conteudo) : 0;

// Buscar estatísticas da prova
$sql_estatisticas = "SELECT 
    COUNT(*) as total_alunos,
    SUM(CASE WHEN status = 'concluido' THEN 1 ELSE 0 END) as concluidas,
    SUM(CASE WHEN status = 'pendente' THEN 1 ELSE 0 END) as pendentes
    FROM Aluno_Provas WHERE Provas_idProvas = $prova_id";
$resultado_estatisticas = mysqli_query($conectar, $sql_estatisticas);
$estatisticas = mysqli_fetch_assoc($resultado_estatisticas);
?>

<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Visualizar Prova - AvaliaEduca</title>
</head>
<body>
    <header>
        <nav>
            <div class="logo">AvaliaEduca - Visualizar Prova</div>
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
            <h1>Visualizar Prova: <?php echo htmlspecialchars($prova['titulo']); ?></h1>
            
            <div class="prova-info">
                <div class="info-grid">
                    <div class="info-item">
                        <strong>ID da Prova:</strong><br>
                        <?php echo $prova['idProvas']; ?>
                    </div>
                    <div class="info-item">
                        <strong>Matéria:</strong><br>
                        <?php echo htmlspecialchars($prova['materia']); ?>
                    </div>
                    <div class="info-item">
                        <strong>Série Destinada:</strong><br>
                        <?php echo htmlspecialchars($prova['serie_destinada']); ?>
                    </div>
                    <div class="info-item">
                        <strong>Data de Criação:</strong><br>
                        <?php echo date('d/m/Y', strtotime($prova['data_criacao'])); ?>
                    </div>
                    <div class="info-item">
                        <strong>Número de Questões:</strong><br>
                        <?php echo $num_questoes; ?>
                    </div>
                    <div class="info-item">
                        <strong>Status:</strong><br>
                        <span>
                            <?php echo ($prova['ativa'] ?? 0) ? 'Ativa' : 'Inativa'; ?>
                        </span>
                    </div>
                </div>
                
                <!-- Estatísticas -->
                <div class="estatisticas">
                    <div class="estatistica-item">
                        <div class="estatistica-numero"><?php echo $estatisticas['total_alunos']; ?></div>
                        <div>Alunos Atribuídos</div>
                    </div>
                    <div class="estatistica-item">
                        <div class="estatistica-numero"><?php echo $estatisticas['concluidas']; ?></div>
                        <div>Provas Concluídas</div>
                    </div>
                    <div class="estatistica-item">
                        <div class="estatistica-numero"><?php echo $estatisticas['pendentes']; ?></div>
                        <div>Provas Pendentes</div>
                    </div>
                    <div class="estatistica-item">
                        <div class="estatistica-numero">
                            <?php echo $estatisticas['total_alunos'] > 0 ? 
                                round(($estatisticas['concluidas'] / $estatisticas['total_alunos']) * 100, 1) : 0; ?>%
                        </div>
                        <div>Taxa de Conclusão</div>
                    </div>
                </div>
            </div>

            <!-- Lista de Questões -->
            <h2>Questões da Prova</h2>
            
            <?php if ($num_questoes > 0): ?>
                <?php foreach ($conteudo as $index => $questao): ?>
                    <div class="questao-card">
                        <h3>Questão <?php echo $index + 1; ?></h3>
                        <div class="enunciado">
                            <strong>Enunciado:</strong><br>
                            <p><?php echo nl2br(htmlspecialchars($questao['enunciado'])); ?></p>
                        </div>
                        
                        <div class="alternativas">
                            <strong>Alternativas:</strong>
                            <?php foreach (['A', 'B', 'C', 'D'] as $letra): ?>
                                <div class="alternativa <?php echo $questao['resposta_correta'] === $letra ? 'correta' : ''; ?>">
                                    <strong><?php echo $letra; ?>)</strong> 
                                    <?php echo htmlspecialchars($questao['alternativas'][$letra]); ?>
                                    <?php if ($questao['resposta_correta'] === $letra): ?>
                                        <span>✓ Resposta Correta</span>
                                    <?php endif; ?>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php else: ?>
                <div>
                    <h3>Nenhuma questão encontrada nesta prova</h3>
                    <p>Esta prova não possui questões cadastradas.</p>
                </div>
            <?php endif; ?>

            <!-- Botões de Ação -->
            <div>
                <a href="gerenciar_provas.php" class="btn btn-voltar">← Voltar para Minhas Provas</a>
                <a href="editar_prova.php?id=<?php echo $prova_id; ?>" class="btn">Editar Prova</a>
                <button onclick="window.print()" class="btn btn-imprimir">🖨️ Imprimir Prova</button>
                <a href="resultados_prova.php?id=<?php echo $prova_id; ?>" class="btn">Ver Resultados</a>
            </div>
        </article>
    </main>

    <script>
        // Função para expandir/contrair questões
        document.addEventListener('DOMContentLoaded', function() {
            const questaoCards = document.querySelectorAll('.questao-card');
            
            questaoCards.forEach(card => {
                const enunciado = card.querySelector('.enunciado p');
                if (enunciado.textContent.length > 200) {
                    const textoCompleto = enunciado.innerHTML;
                    const textoResumido = textoCompleto.substring(0, 200) + '...';
                    
                    enunciado.innerHTML = textoResumido;
                    
                    const btnExpandir = document.createElement('button');
                    btnExpandir.textContent = 'Ver mais';
                    btnExpandir.style.marginLeft = '10px';
                    btnExpandir.style.padding = '2px 5px';
                    btnExpandir.style.fontSize = '0.8em';
                    
                    btnExpandir.addEventListener('click', function() {
                        if (enunciado.innerHTML === textoResumido) {
                            enunciado.innerHTML = textoCompleto;
                            btnExpandir.textContent = 'Ver menos';
                        } else {
                            enunciado.innerHTML = textoResumido;
                            btnExpandir.textContent = 'Ver mais';
                        }
                    });
                    
                    card.querySelector('.enunciado').appendChild(btnExpandir);
                }
            });
        });
    </script>
</body>
</html>

<?php mysqli_close($conectar); ?>