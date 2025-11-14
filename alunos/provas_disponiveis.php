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

require_once '../config/database_config.php';

$host = $db_config['host'];
$user = $db_config['user'];
$password = $db_config['password'];
$database = $db_config['database'];
$conectar = mysqli_connect($host, $user, $password, $database);

//  Verificar conexão
if (!$conectar) {
    die("Erro de conexão: " . mysqli_connect_error());
}

$aluno_id = (int)$_SESSION['id_aluno'];

//  Buscar dados do aluno (SEGURA)
$sql_aluno = "SELECT escolaridade, nome FROM Aluno WHERE idAluno = ?";
$stmt_aluno = mysqli_prepare($conectar, $sql_aluno);
mysqli_stmt_bind_param($stmt_aluno, "i", $aluno_id);
mysqli_stmt_execute($stmt_aluno);
$result_aluno = mysqli_stmt_get_result($stmt_aluno);
$aluno = mysqli_fetch_assoc($result_aluno);
mysqli_stmt_close($stmt_aluno);

$serie_aluno = $aluno['escolaridade'] ?? '';
$nome_aluno = $aluno['nome'] ?? '';

//  Buscar provas disponíveis para o aluno (SEGURA)
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
        
        //  LÓGICA DE STATUS CORRIGIDA E SEGURA
        $status_prova = 'disponivel'; // padrão
        
        if ($prova['status'] === null) {
            $status_prova = 'disponivel';
            $disponiveis++;
        } elseif ($prova['status'] === 'pendente') {
            $status_prova = 'pendente';
            $realizadas++;
        } elseif ($prova['status'] === 'realizada') {
            $status_prova = 'realizada';
            $realizadas++;
        } elseif ($prova['status'] === 'corrigida') {
            $status_prova = 'corrigida';
            $corrigidas++;
        }
        
        //  Adicionar status corrigido ao array
        $prova['status_corrigido'] = $status_prova;
        
        //  Decodificar conteúdo para contar questões (com validação)
        $conteudo = json_decode($prova['conteudo'] ?? '[]', true);
        $prova['num_questoes'] = is_array($conteudo) ? count($conteudo) : 0;
        
        $provas_data[] = $prova;
    }
} else {
    $total_provas = 0;
}

//  FECHAR STATEMENT DAS PROVAS
mysqli_stmt_close($stmt_provas);
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
        <article class="provas-disponiveis">
            <section class="header-provas-disponiveis">
                <h1>📚 Provas Disponíveis</h1>
                <p>Aluno: <strong><?php echo htmlspecialchars($nome_aluno); ?></strong> | Série: <strong><?php echo htmlspecialchars($serie_aluno); ?></strong></p>
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
                                        <!-- ✅ Título sanitizado -->
                                        <h3>
                                            <?php echo htmlspecialchars($prova['titulo'] ?: $prova['materia'] . ' - Avaliação'); ?>
                                        </h3>
                                        <div>
                                            <span class="badge badge-materia">
                                                📚 <?php echo htmlspecialchars($prova['materia']); ?>
                                            </span>
                                            <span class="badge badge-questoes">
                                                🔢 <?php echo (int)$prova['num_questoes']; ?> questões
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
                                            <span class="status-tag tag-corrigida">📊 Nota: <?php echo number_format((float)$prova['nota'], 1); ?></span>
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
                                        <!-- ✅ Link seguro com ID convertido para inteiro -->
                                        <a href="fazer_prova.php?id=<?php echo (int)$prova['idProvas']; ?>" class="btn btn-iniciar">
                                            🚀 Iniciar Prova
                                        </a>
                                    <?php elseif ($status === 'pendente'): ?>
                                        <a href="fazer_prova.php?id=<?php echo (int)$prova['idProvas']; ?>" class="btn btn-iniciar">
                                            ➡️ Continuar Prova
                                        </a>
                                    <?php elseif ($status === 'realizada'): ?>
                                        <button class="btn" disabled style="background: #2196F3; color: white;">
                                            ⏳ Aguardando Correção
                                        </button>
                                    <?php elseif ($status === 'corrigida'): ?>
                                        <a href="ver_resultado.php?id=<?php echo (int)$prova['idProvas']; ?>" class="btn btn-resultado">
                                            📊 Ver Resultado
                                        </a>
                                    <?php endif; ?>
                                    
                                    <!-- ✅ Link seguro para detalhes -->
                                    <a href="detalhes_prova.php?id=<?php echo (int)$prova['idProvas']; ?>" class="btn btn-detalhes">
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
