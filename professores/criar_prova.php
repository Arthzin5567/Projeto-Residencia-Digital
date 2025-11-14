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
?>

<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Criar Prova - Edukhan</title>
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
                <li><a href="gerenciar_provas.php">Minhas Provas</a></li>
                <li><a href="../logout.php">Sair</a></li>
            </ul>
        </nav>
    </header>

    <main>
        <article class="criar-prova">
            <h1>Criar Nova Prova</h1>
            
            <form class="form-cria-prov" action="../includes/processa_criar_prova.php" method="POST" enctype="multipart/form-data">
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

    <!-- KaTeX JS -->
    <script defer src="https://cdn.jsdelivr.net/npm/katex@0.16.9/dist/katex.min.js"></script>
    <script defer src="https://cdn.jsdelivr.net/npm/katex@0.16.9/dist/contrib/auto-render.min.js"></script>
    <script src="../js/math-config.js"></script>

    <script>
        function adicionarQuestao() {
        const container = document.getElementById('questoes-container');
        const numQuestoes = document.getElementById('numero_questoes').value;
        
        container.innerHTML = '';
        
        for (let i = 1; i <= numQuestoes; i++) {
            container.innerHTML += `
                <div class="questao">
                    <h3>Questão ${i}</h3>
                    <div>
                        <label>Enunciado:</label>
                        <textarea name="enunciado_${i}" rows="3" placeholder="Ex: Resolva a equação $x^2 + \frac{5}{78} = 9$" required></textarea>
                        <small>Use $equação$ para fórmulas matemáticas</small>
                    </div>
                    <!-- Área de upload de imagens -->
                    <div class="imagens-questao">
                        <label>Imagens para esta questão (opcional):</label>
                        <div class="area-upload" onclick="document.getElementById('imagens_${i}').click()">
                            <p>Clique aqui ou arraste imagens para adicionar</p>
                            <small>Formatos: JPG, PNG, GIF (Máx: 2MB cada)</small>
                        </div>
                        <input type="file" id="imagens_${i}" name="imagens_${i}[]" 
                            multiple accept="image/*" style="display: none;" 
                            onchange="previewImagens(${i}, this.files)">
                        
                        <div id="preview_${i}" class="lista-imagens"></div>
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

        // Renderizar equações após adicionar conteúdo dinâmico
        renderizarEquacoesNoElemento(container);
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
    function previewImagens(numeroQuestao, files) {
    const preview = document.getElementById(`preview_${numeroQuestao}`);
    preview.innerHTML = '';
    
    for (let file of files) {
        if (file.type.startsWith('image/')) {
            const reader = new FileReader();
            reader.onload = function(e) {
                const img = document.createElement('img');
                img.src = e.target.result;
                img.className = 'preview-imagem';
                img.title = file.name;
                preview.appendChild(img);
            }
            reader.readAsDataURL(file);
        }
    }
}

    // Drag and drop functionality
    document.addEventListener('DOMContentLoaded', function() {
        document.addEventListener('dragenter', function(e) {
            e.preventDefault();
        });
        
        document.addEventListener('dragover', function(e) {
            e.preventDefault();
        });
        
        document.addEventListener('drop', function(e) {
            e.preventDefault();
            const target = e.target.closest('.area-upload');
            if (target) {
                const questaoNum = target.previousElementSibling.htmlFor.split('_')[1];
                const input = document.getElementById(`imagens_${questaoNum}`);
                input.files = e.dataTransfer.files;
                previewImagens(questaoNum, input.files);
            }
        });
    });

    
</script>
</body>
</html>