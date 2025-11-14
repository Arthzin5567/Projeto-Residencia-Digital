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

// VALIDAÇÃO  do ID da prova
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    header("Location: gerenciar_provas.php");
    exit();
}

$prova_id = (int)$_GET['id'];
$professor_id = (int)$_SESSION["idProfessor"];

//  Buscar imagens da prova 
$sql_imagens = "SELECT numero_questao, caminho_imagem, nome_arquivo 
                FROM ImagensProvas 
                WHERE idProva = ? 
                ORDER BY numero_questao, idImagem";
$stmt_imagens = mysqli_prepare($conectar, $sql_imagens);
mysqli_stmt_bind_param($stmt_imagens, "i", $prova_id);
mysqli_stmt_execute($stmt_imagens);
$resultado_imagens = mysqli_stmt_get_result($stmt_imagens);
$imagens_por_questao = [];

if ($resultado_imagens) {
    while ($imagem = mysqli_fetch_assoc($resultado_imagens)) {
        $imagens_por_questao[$imagem['numero_questao']][] = $imagem;
    }
}
mysqli_stmt_close($stmt_imagens);

//  Buscar os dados da prova 
$sql_prova = "SELECT * FROM Provas WHERE idProvas = ? AND Professor_idProfessor = ?";
$stmt_prova = mysqli_prepare($conectar, $sql_prova);
mysqli_stmt_bind_param($stmt_prova, "ii", $prova_id, $professor_id);
mysqli_stmt_execute($stmt_prova);
$resultado_prova = mysqli_stmt_get_result($stmt_prova);

if (mysqli_num_rows($resultado_prova) === 0) {
    header("Location: gerenciar_provas.php?erro=Prova não encontrada");
    mysqli_stmt_close($stmt_prova);
    exit();
}

$prova = mysqli_fetch_assoc($resultado_prova);
mysqli_stmt_close($stmt_prova);

$conteudo = json_decode($prova['conteudo'], true);
$num_questoes = is_array($conteudo) ? count($conteudo) : 0;

//  Buscar estatísticas da prova 
$sql_estatisticas = "SELECT 
    COUNT(*) as total_alunos,
    SUM(CASE WHEN status = 'realizada' THEN 1 ELSE 0 END) as concluidas,
    SUM(CASE WHEN status = 'pendente' THEN 1 ELSE 0 END) as pendentes
    FROM Aluno_Provas WHERE Provas_idProvas = ?";
$stmt_estatisticas = mysqli_prepare($conectar, $sql_estatisticas);
mysqli_stmt_bind_param($stmt_estatisticas, "i", $prova_id);
mysqli_stmt_execute($stmt_estatisticas);
$resultado_estatisticas = mysqli_stmt_get_result($stmt_estatisticas);
$estatisticas = mysqli_fetch_assoc($resultado_estatisticas);
mysqli_stmt_close($stmt_estatisticas);
?>

<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Visualizar Prova - Edukhan</title>
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
                <li><a href="criar_prova.php">Criar Prova</a></li>
                <li><a href="gerenciar_provas.php">Minhas Provas</a></li>
                <li><a href="../logout.php">Sair</a></li>
            </ul>
        </nav>
    </header>

    <main>
        <article class="visualizar-prova-container">
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
            <div class="questoes-section">
                <h2>Questões da Prova</h2>

                <?php if ($num_questoes > 0): ?>
                    <?php foreach ($conteudo as $index => $questao): ?>
                        <div class="questao-card">
                            <h3>Questão <?php echo $index + 1; ?></h3>

                            <!-- Exibir imagens da questão, se houver -->
                            <?php $numero_questao = $index + 1; ?>
                            <?php if (isset($imagens_por_questao[$numero_questao]) && !empty($imagens_por_questao[$numero_questao])): ?>
                                <div class="imagens-questao">
                                    <strong>Imagens desta questão:</strong><br>
                                    <div style="display: flex; flex-wrap: wrap; gap: 10px; margin: 10px 0;">
                                        <?php foreach ($imagens_por_questao[$numero_questao] as $imagem): ?>
                                            <div class="imagem-container">
                                                <img src="<?php echo htmlspecialchars($imagem['caminho_imagem']); ?>" 
                                                    alt="Imagem da questão <?php echo $numero_questao; ?>"
                                                    class="imagem-questao"
                                                    onclick="abrirModal('<?php echo htmlspecialchars($imagem['caminho_imagem']); ?>')"
                                                    style="max-width: 200px; cursor: zoom-in; border: 1px solid #ddd; border-radius: 5px; padding: 5px;">
                                                <br>
                                                <small><?php echo htmlspecialchars($imagem['nome_arquivo']); ?></small>
                                            </div>
                                        <?php endforeach; ?>
                                    </div>
                                </div>
                            <?php else: ?>
                                <div class="sem-imagens">
                                    <small>Nenhuma imagem anexada a esta questão</small>
                                </div>
                            <?php endif; ?>

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
            </div>
            
            <!-- Botões de Ação -->
            <div class="bnt-all-provas">
                <a href="gerenciar_provas.php" class="btn btn-voltar">← Voltar para Minhas Provas</a>
                <a href="editar_prova.php?id=<?php echo $prova_id; ?>" class="btn">Editar Prova</a>
                <button onclick="window.print()" class="btn btn-imprimir">🖨️ Imprimir Prova</button>
                <a href="resultados_prova.php?id=<?php echo $prova_id; ?>" class="btn">Ver Resultados</a>
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

    <!-- Modal para visualização ampliada de imagens - COM CSS INLINE -->
    <div id="modalImagem" style="display: none; position: fixed; z-index: 9999; left: 0; top: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.95); justify-content: center; align-items: center;">
        <div style="position: relative; max-width: 90%; max-height: 90%; text-align: center;">
            <span onclick="fecharModal()" style="position: absolute; top: -50px; right: -10px; color: white; font-size: 40px; cursor: pointer; background: rgba(0,0,0,0.7); width: 50px; height: 50px; border-radius: 50%; display: flex; align-items: center; justify-content: center; border: 2px solid white; transition: all 0.3s ease;">&times;</span>
            <img id="imagemModal" src="" alt="Imagem ampliada" style="max-width: 100%; max-height: 80vh; border-radius: 8px; box-shadow: 0 5px 25px rgba(0,0,0,0.5);">
        </div>
    </div>

    <!-- KaTeX JS -->
    <script defer src="https://cdn.jsdelivr.net/npm/katex@0.16.9/dist/katex.min.js"></script>
    <script defer src="https://cdn.jsdelivr.net/npm/katex@0.16.9/dist/contrib/auto-render.min.js"></script>
    <script src="../js/math-config.js"></script>

    <script>
        // ✅ FUNÇÕES DO MODAL - VERSÃO CORRIGIDA
        function abrirModal(src) {
            console.log('Abrindo modal com:', src);
            const modal = document.getElementById('modalImagem');
            const modalImg = document.getElementById('imagemModal');
            
            if (modal && modalImg) {
                modalImg.src = src;
                modal.style.display = 'flex';
                document.body.style.overflow = 'hidden';
            }
        }

        function fecharModal() {
            const modal = document.getElementById('modalImagem');
            if (modal) {
                modal.style.display = 'none';
                document.body.style.overflow = 'auto';
            }
        }

        // ✅ CONFIGURAÇÃO DOS EVENT LISTENERS
        document.addEventListener('DOMContentLoaded', function() {
            const modal = document.getElementById('modalImagem');
            
            // Fechar modal ao clicar fora
            if (modal) {
                modal.addEventListener('click', function(e) {
                    if (e.target === this) {
                        fecharModal();
                    }
                });
            }
            
            // Fechar modal com ESC
            document.addEventListener('keydown', function(e) {
                if (e.key === 'Escape') {
                    fecharModal();
                }
            });
        });

        // ✅ CORREÇÃO: Função para expandir/contrair questões
        document.addEventListener('DOMContentLoaded', function() {
            const questaoCards = document.querySelectorAll('.questao-card');
            
            questaoCards.forEach(card => {
                const enunciado = card.querySelector('.enunciado p');
                if (enunciado && enunciado.textContent.length > 200) {
                    const textoCompleto = enunciado.innerHTML;
                    const textoResumido = textoCompleto.substring(0, 200) + '...';
                    
                    enunciado.innerHTML = textoResumido;
                    
                    const btnExpandir = document.createElement('button');
                    btnExpandir.textContent = 'Ver mais';
                    btnExpandir.style.marginLeft = '10px';
                    btnExpandir.style.padding = '2px 5px';
                    btnExpandir.style.fontSize = '0.8em';
                    btnExpandir.style.cursor = 'pointer';
                    
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