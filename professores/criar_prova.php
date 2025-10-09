<?php
session_start();
// Verificar se é professor
if (!isset($_SESSION["logado"]) || $_SESSION["logado"] !== true || $_SESSION["tipo_usuario"] !== "professor") {
    header("Location: ../index.php");
    exit();
}

$conectar = mysqli_connect("localhost", "root", "", "projeto_residencia");
?>

<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Criar Prova - AvaliaEduca</title>
</head>
<body>
    <header>
        <nav>
            <div class="logo">AvaliaEduca - Criar Prova</div>
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
            <h1>Criar Nova Prova</h1>
            
            <form action="../includes/processa_criar_prova.php" method="POST">
                <div>
                    <label for="titulo">Título da Prova:</label>
                    <input type="text" id="titulo" name="titulo" required>
                </div>
                
                <div>
                    <label for="materia">Matéria:</label>
                    <select id="materia" name="materia" required>
                        <option value="Matemática">Matemática</option>
                        <option value="Português">Português</option>
                        <option value="Ciências">Ciências</option>
                        <option value="História">História</option>
                        <option value="Geografia">Geografia</option>
                    </select>
                </div>
                
                <div>
                    <label for="serie_destinada">Série Destinada:</label>
                    <select id="serie_destinada" name="serie_destinada" required>
                        <option value="1º ano">1º ano</option>
                        <option value="2º ano">2º ano</option>
                        <option value="3º ano">3º ano</option>
                        <option value="4º ano">4º ano</option>
                        <option value="5º ano">5º ano</option>
                    </select>
                </div>
                
                <div>
                    <label for="numero_questoes">Número de Questões:</label>
                    <input type="number" id="numero_questoes" name="numero_questoes" min="1" max="20" required>
                </div>
                
                <div id="questoes-container">
                    <!-- As questões serão adicionadas aqui via JavaScript -->
                </div>
                
                <button type="button" onclick="adicionarQuestao()">Adicionar Questão</button>
                <button type="submit">Criar Prova</button>
            </form>
        </article>
    </main>

    <script>
        function adicionarQuestao() {
        const container = document.getElementById('questoes-container');
        const numQuestoes = document.getElementById('numero_questoes').value;
        
        container.innerHTML = '';
        
        for (let i = 1; i <= numQuestoes; i++) {
            container.innerHTML += `
                <div class="questao" style="border: 1px solid #ccc; padding: 10px; margin: 10px 0;">
                    <h3>Questão ${i}</h3>
                    <div>
                        <label>Enunciado:</label>
                        <textarea name="enunciado_${i}" rows="3" style="width: 100%;" required></textarea>
                    </div>
                    <div>
                        <label>Alternativa A:</label>
                        <input type="text" name="alternativa_a_${i}" required>
                    </div>
                    <div>
                        <label>Alternativa B:</label>
                        <input type="text" name="alternativa_b_${i}" required>
                    </div>
                    <div>
                        <label>Alternativa C:</label>
                        <input type="text" name="alternativa_c_${i}" required>
                    </div>
                    <div>
                        <label>Alternativa D:</label>
                        <input type="text" name="alternativa_d_${i}" required>
                    </div>
                    <div>
                        <label>Resposta Correta:</label>
                        <select name="resposta_correta_${i}" required>
                            <option value="A">A</option>
                            <option value="B">B</option>
                            <option value="C">C</option>
                            <option value="D">D</option>
                        </select>
                    </div>
                </div>
            `;
        }
    }
    
    // Adiciona questões automaticamente quando a página carrega
    document.addEventListener('DOMContentLoaded', function() {
        // Adicionar evento quando o número de questões muda
        document.getElementById('numero_questoes').addEventListener('change', adicionarQuestao);
        
        // Adicionar evento quando o botão é clicado
        document.querySelector('button[onclick="adicionarQuestao()"]').addEventListener('click', adicionarQuestao);
        
        // Adicionar questões automaticamente se já houver um valor
        const numQuestoes = document.getElementById('numero_questoes').value;
        if (numQuestoes > 0) {
            adicionarQuestao();
        }
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