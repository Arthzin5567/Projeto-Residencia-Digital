<?php
session_start();
// Verificar se é professor
if (!isset($_SESSION["logado"]) || $_SESSION["logado"] !== true || $_SESSION["tipo_usuario"] !== "professor") {
    header("Location: ../index.php");
    exit();
}

$conectar = mysqli_connect("localhost", "root", "", "projeto_residencia");

// Verificar se foi passado um ID de prova para editar
if (!isset($_GET['id']) || empty($_GET['id'])) {
    header("Location: gerenciar_provas.php");
    exit();
}

$prova_id = mysqli_real_escape_string($conectar, $_GET['id']);

// Buscar dados da prova

// Buscar dados da prova
$sql_prova = "SELECT * FROM Provas WHERE idProvas = '$prova_id' AND Professor_idProfessor = '{$_SESSION['idProfessor']}'";
$result_prova = mysqli_query($conectar, $sql_prova);

if (mysqli_num_rows($result_prova) === 0) {
    header("Location: gerenciar_provas.php");
    exit();
}

$prova = mysqli_fetch_assoc($result_prova);


// Buscar questões do campo JSON da prova
$questoes = json_decode($prova['conteudo'], true);
$numero_questoes = is_array($questoes) ? count($questoes) : 0;
?>

<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Editar Prova - AvaliaEduca</title>
</head>
<body>
    <header>
        <nav>
            <div class="logo">AvaliaEduca - Editar Prova</div>
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
            <h1>Editar Prova: <?php echo htmlspecialchars($prova['titulo']); ?></h1>
            
            <form action="../includes/processa_editar_prova.php" method="POST">
                <input type="hidden" name="prova_id" value="<?php echo $prova_id; ?>">
                
                <div>
                    <label for="titulo">Título da Prova:</label>
                    <input type="text" id="titulo" name="titulo" value="<?php echo htmlspecialchars($prova['titulo']); ?>" required>
                </div>
                
                <div>
                    <label for="materia">Matéria:</label>
                    <select id="materia" name="materia" required>
                        <option value="Matemática" <?php echo $prova['materia'] == 'Matemática' ? 'selected' : ''; ?>>Matemática</option>
                        <option value="Português" <?php echo $prova['materia'] == 'Português' ? 'selected' : ''; ?>>Português</option>
                        <option value="Ciências" <?php echo $prova['materia'] == 'Ciências' ? 'selected' : ''; ?>>Ciências</option>
                        <option value="História" <?php echo $prova['materia'] == 'História' ? 'selected' : ''; ?>>História</option>
                        <option value="Geografia" <?php echo $prova['materia'] == 'Geografia' ? 'selected' : ''; ?>>Geografia</option>
                    </select>
                </div>
                
                <div>
                    <label for="serie_destinada">Série Destinada:</label>
                    <select id="serie_destinada" name="serie_destinada" required>
                        <option value="1º ano" <?php echo $prova['serie_destinada'] == '1º ano' ? 'selected' : ''; ?>>1º ano</option>
                        <option value="2º ano" <?php echo $prova['serie_destinada'] == '2º ano' ? 'selected' : ''; ?>>2º ano</option>
                        <option value="3º ano" <?php echo $prova['serie_destinada'] == '3º ano' ? 'selected' : ''; ?>>3º ano</option>
                        <option value="4º ano" <?php echo $prova['serie_destinada'] == '4º ano' ? 'selected' : ''; ?>>4º ano</option>
                        <option value="5º ano" <?php echo $prova['serie_destinada'] == '5º ano' ? 'selected' : ''; ?>>5º ano</option>
                    </select>
                </div>
                
                <div>
                    <label for="numero_questoes">Número de Questões:</label>
                    <input type="number" id="numero_questoes" name="numero_questoes" min="1" max="20" value="<?php echo $numero_questoes; ?>" required>
                </div>
                
                <div id="questoes-container">
                    <!-- As questões serão carregadas aqui via JavaScript -->
                </div>
                
                <button type="button" onclick="adicionarQuestao()">Atualizar Questões</button>
                <button type="submit">Salvar Alterações</button>
                <button type="button" onclick="window.location.href='gerenciar_provas.php'">Cancelar</button>
            </form>
        </article>
    </main>

    <script>
        // Array para armazenar as questões carregadas do PHP
        const questoesExistentes = <?php echo json_encode($questoes); ?>;

        function adicionarQuestao() {
            const container = document.getElementById('questoes-container');
            const numQuestoes = document.getElementById('numero_questoes').value;
            
            container.innerHTML = '';
            
            for (let i = 1; i <= numQuestoes; i++) {
                // Verificar se existe uma questão para este índice
                const questaoExistente = questoesExistentes.find(q => parseInt(q.numero_questao) === i);
                
                container.innerHTML += `
                    <div class="questao" style="border: 1px solid #ccc; padding: 10px; margin: 10px 0;">
                        <h3>Questão ${i}</h3>
                        <input type="hidden" name="questao_id_${i}" value="${questaoExistente ? questaoExistente.id : ''}">
                        <div>
                            <label>Enunciado:</label>
                            <textarea name="enunciado_${i}" rows="3" style="width: 100%;" required>${questaoExistente ? questaoExistente.enunciado : ''}</textarea>
                        </div>
                        <div>
                            <label>Alternativa A:</label>
                            <input type="text" name="alternativa_a_${i}" value="${questaoExistente ? questaoExistente.alternativa_a : ''}" required>
                        </div>
                        <div>
                            <label>Alternativa B:</label>
                            <input type="text" name="alternativa_b_${i}" value="${questaoExistente ? questaoExistente.alternativa_b : ''}" required>
                        </div>
                        <div>
                            <label>Alternativa C:</label>
                            <input type="text" name="alternativa_c_${i}" value="${questaoExistente ? questaoExistente.alternativa_c : ''}" required>
                        </div>
                        <div>
                            <label>Alternativa D:</label>
                            <input type="text" name="alternativa_d_${i}" value="${questaoExistente ? questaoExistente.alternativa_d : ''}" required>
                        </div>
                        <div>
                            <label>Resposta Correta:</label>
                            <select name="resposta_correta_${i}" required>
                                <option value="A" ${questaoExistente && questaoExistente.resposta_correta === 'A' ? 'selected' : ''}>A</option>
                                <option value="B" ${questaoExistente && questaoExistente.resposta_correta === 'B' ? 'selected' : ''}>B</option>
                                <option value="C" ${questaoExistente && questaoExistente.resposta_correta === 'C' ? 'selected' : ''}>C</option>
                                <option value="D" ${questaoExistente && questaoExistente.resposta_correta === 'D' ? 'selected' : ''}>D</option>
                            </select>
                        </div>
                    </div>
                `;
            }
        }

        // Carregar questões automaticamente quando a página carrega
        document.addEventListener('DOMContentLoaded', function() {
            // Adicionar evento quando o número de questões muda
            document.getElementById('numero_questoes').addEventListener('change', adicionarQuestao);
            
            // Adicionar evento quando o botão é clicado
            document.querySelector('button[onclick="adicionarQuestao()"]').addEventListener('click', adicionarQuestao);
            
            // Carregar questões automaticamente
            adicionarQuestao();
        });
    </script>

    <script>
    // Garantir que as questões sejam enviadas no formulário
    document.querySelector('form').addEventListener('submit', function(e) {
        const numQuestoes = document.getElementById('numero_questoes').value;
        const container = document.getElementById('questoes-container');
        
        // Se não há questões visíveis, gerá-las agora
        if (container.children.length === 0 && numQuestoes > 0) {
            adicionarQuestao();
        }
        
        // Verificar se todas as questões estão preenchidas
        for (let i = 1; i <= numQuestoes; i++) {
            const enunciado = document.querySelector(`textarea[name="enunciado_${i}"]`);
            if (!enunciado || !enunciado.value.trim()) {
                alert(`Por favor, preencha o enunciado da questão ${i}`);
                e.preventDefault();
                return;
            }
        }
    });
    </script>
</body>
</html>