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
$sql_aluno = "SELECT escolaridade, nome FROM Aluno WHERE idAluno = '$aluno_id'";
$result_aluno = mysqli_query($conectar, $sql_aluno);
$aluno = mysqli_fetch_assoc($result_aluno);
$serie_aluno = $aluno['escolaridade'];
$nome_aluno = $aluno['nome'];

// CORREÇÃO: Buscar provas disponíveis para o aluno (usando a variável correta)
$sql_provas = "SELECT p.*, ap.status, ap.nota, ap.data_realizacao 
               FROM Provas p 
               LEFT JOIN Aluno_Provas ap ON p.idProvas = ap.Provas_idProvas AND ap.Aluno_idAluno = '$aluno_id' 
               WHERE ap.Aluno_idAluno IS NULL OR ap.status = 'pendente'
               ORDER BY p.data_criacao DESC";

$result_provas = mysqli_query($conectar, $sql_provas);

// Contadores para estatísticas
$total_provas = 0;
$disponiveis = 0;
$realizadas = 0;
$corrigidas = 0;

// Processar resultados
$provas_data = [];

if ($result_provas && mysqli_num_rows($result_provas) > 0) {
    while ($prova = mysqli_fetch_assoc($result_provas)) {
        $total_provas++;
        
        // CORREÇÃO: Lógica de status corrigida
        if ($prova['status'] === null) {
            $status_prova = 'disponivel';
            $disponiveis++;
        } elseif ($prova['status'] === 'pendente') {
            $status_prova = 'pendente';
            $realizadas++; // Considera como "em andamento"
        } elseif ($prova['status'] === 'realizada') {
            $status_prova = 'realizada';
            $realizadas++;
        } elseif ($prova['status'] === 'corrigida') {
            $status_prova = 'corrigida';
            $corrigidas++;
        }
        
        // Adicionar status corrigido ao array
        $prova['status_corrigido'] = $status_prova;
        
        // Decodificar conteúdo para contar questões
        $conteudo = json_decode($prova['conteudo'], true);
        $prova['num_questoes'] = is_array($conteudo) ? count($conteudo) : 0;
        
        $provas_data[] = $prova;
    }
} else {
    $total_provas = 0;
}
?>

<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Provas Disponíveis - Edukhan</title>
    <link rel="stylesheet" href="../css/style.css">
    <!-- KaTeX CSS -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/katex@0.16.9/dist/katex.min.css">
</head>
<body>
    <header>
        <nav>
            <div class="logo">Edukhan - Provas</div>
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
        <article class="provas-disponiveis">
            <section class="header-provas-disponiveis">
                <h1>📚 Provas Disponíveis</h1>
                <p>Aluno: <strong><?php echo $nome_aluno; ?></strong> | Série: <strong><?php echo $serie_aluno; ?></strong></p>
            </section>

            <!-- ESTATÍSTICAS RÁPIDAS -->
            <section class="estatisticas-rapidas-provas-disponiveis">
                <div>
                    <h3><?php echo $disponiveis; ?></h3>
                    <p>Disponíveis</p>
                    <small>Para realizar</small>
                </div>
                <div>
                    <h3><?php echo $realizadas; ?></h3>
                    <p>Em Andamento</p>
                    <small>Pendentes</small>
                </div>
                <div>
                    <h3><?php echo $corrigidas; ?></h3>
                    <p>Corrigidas</p>
                    <small>Com nota disponível</small>
                </div>
                <div>
                    <h3><?php echo $total_provas; ?></h3>
                    <p>Total</p>
                    <small>Provas atribuídas</small>
                </div>
            </section>

            <!-- LISTA DE PROVAS -->
            <section class="lista-provas-disponiveis">
                <h2>📋 Lista de Provas</h2>
                
                <?php if ($total_provas > 0): ?>
                    <div id="listaProvas">
                        <?php foreach ($provas_data as $prova): 
                            $status = $prova['status_corrigido'];
                            $classe_status = "status-" . $status;
                            $tag_status = "tag-" . $status;
                        ?>
                            <div class="prova-card <?php echo $classe_status; ?>">
                                
                                <div>
                                    <div>
                                        <!-- CORREÇÃO: Título formatado elegantemente -->
                                        <h3>
                                            <?php echo htmlspecialchars($prova['titulo'] ?: $prova['materia'] . ' - Avaliação'); ?>
                                        </h3>
                                        <div>
                                            <span class="badge badge-materia">
                                                📚 <?php echo htmlspecialchars($prova['materia']); ?>
                                            </span>
                                            <span class="badge badge-questoes">
                                                🔢 <?php echo $prova['num_questoes']; ?> questões
                                            </span>
                                            <span class="badge badge-serie">
                                                🎯 Série: <?php echo htmlspecialchars($prova['serie_destinada']); ?>
                                            </span>
                                        </div>
                                    </div>
                                    
                                    <div class="status-tag">
                                        <?php if ($status === 'disponivel'): ?>
                                            <span class="status-tag tag-disponivel">✅ Disponível</span>
                                        <?php elseif ($status === 'pendente'): ?>
                                            <span class="status-tag tag-pendente">⏳ Em Andamento</span>
                                        <?php elseif ($status === 'realizada'): ?>
                                            <span class="status-tag tag-realizada">📤 Aguardando correção</span>
                                        <?php elseif ($status === 'corrigida'): ?>
                                            <span class="status-tag tag-corrigida">📊 Nota: <?php echo number_format($prova['nota'], 1); ?></span>
                                        <?php endif; ?>
                                    </div>
                                </div>

                                <!-- Informações adicionais -->
                                <div>
                                    <div>
                                        <strong>Criada em:</strong> 
                                        <?php echo date('d/m/Y', strtotime($prova['data_criacao'])); ?>
                                    </div>
                                    <div>
                                        <strong>Realizada em:</strong> 
                                        <?php echo $prova['data_realizacao'] ? date('d/m/Y', strtotime($prova['data_realizacao'])) : '--/--/----'; ?>
                                    </div>
                                </div>

                                <!-- Ações -->
                                <div>
                                    <?php if ($status === 'disponivel'): ?>
                                        <a href="fazer_prova.php?id=<?php echo $prova['idProvas']; ?>" class="btn btn-iniciar">
                                            🚀 Iniciar Prova
                                        </a>
                                    <?php elseif ($status === 'pendente'): ?>
                                        <a href="fazer_prova.php?id=<?php echo $prova['idProvas']; ?>" class="btn btn-iniciar">
                                            ➡️ Continuar Prova
                                        </a>
                                    <?php elseif ($status === 'realizada'): ?>
                                        <button class="btn" disabled style="background: #2196F3; color: white;">
                                            ⏳ Aguardando Correção
                                        </button>
                                    <?php elseif ($status === 'corrigida'): ?>
                                        <a href="ver_resultado.php?id=<?php echo $prova['idProvas']; ?>" class="btn btn-resultado">
                                            📊 Ver Resultado
                                        </a>
                                    <?php endif; ?>
                                    
                                    <a href="detalhes_prova.php?id=<?php echo $prova['idProvas']; ?>" class="btn btn-detalhes">
                                        ℹ️ Detalhes
                                    </a>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php else: ?>
                    <div>
                        <h3>📭 Nenhuma prova disponível no momento!</h3>
                        <p>Não há provas disponíveis para você realizar no momento.</p>
                        <p><small>Verifique com seu professor se há novas avaliações disponíveis.</small></p>
                    </div>
                <?php endif; ?>
            </section>
        </article>
    </main>

    <!-- KaTeX JS -->
    <script defer src="https://cdn.jsdelivr.net/npm/katex@0.16.9/dist/katex.min.js"></script>
    <script defer src="https://cdn.jsdelivr.net/npm/katex@0.16.9/dist/contrib/auto-render.min.js"></script>
    <script src="../js/math-config.js"></script>

    <footer>
    <p>&copy; 2023 Edukhan - Área do Aluno</p>
    </footer>
</body>
</html>

<?php mysqli_close($conectar); ?>