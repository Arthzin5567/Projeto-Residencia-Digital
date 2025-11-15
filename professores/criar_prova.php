<?php
session_start();
require_once __DIR__ . '/../config/funcoes_comuns.php';
$conectar = conectarBanco();

verificarloginProfessor();



//  Verificar conexão com banco
if (!$conectar) {
    error_log("Erro de conexão em criar_prova.php");
    // Continua funcionando mesmo sem conexão para não quebrar a experiência
}
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
    
    <!-- Meta tags de segurança -->
    <meta http-equiv="Content-Security-Policy" content="default-src 'self'; script-src 'self' 'unsafe-inline' https://cdn.jsdelivr.net; style-src 'self' 'unsafe-inline' https://cdn.jsdelivr.net; img-src 'self' data:;">
    <meta http-equiv="X-Content-Type-Options" content="nosniff">
    <meta http-equiv="X-Frame-Options" content="DENY">
</head>
<body>
    <header>
        <nav>
            <div class="logo">
                <img src="../img/LOGOTIPO 1.avif" alt="logo" loading="lazy">
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
            
            <form class="form-cria-prov" action="../includes/processa_criar_prova.php" method="POST" enctype="multipart/form-data" id="form-prova">
                <!-- CSRF Token (opcional - pode adicionar depois) -->
                <!-- <input type="hidden" name="csrf_token" value="<?php echo bin2hex(random_bytes(32)); ?>"> -->
                
                <div>
                    <label for="titulo">Título da Prova:</label>
                    <input type="text" id="titulo" name="titulo" required
                           maxlength="255"
                           pattern="[A-Za-z0-9áéíóúâêîôûãõçÁÉÍÓÚÂÊÎÔÛÃÕÇ\s\.\-_!?]{1,255}"
                           title="Máximo 255 caracteres. Use apenas letras, números e espaços.">
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
                    <input type="number" id="numero_questoes" name="numero_questoes"
                           min="1" max="20" required
                           oninput="validarNumeroQuestoes(this)">
                </div>
                
                <div id="questoes-container">
                    <!-- As questões serão adicionadas aqui via JavaScript -->
                </div>
                
                <button type="button" onclick="adicionarQuestao()">Adicionar Questão</button>
                <button type="submit" id="btn-submit">Criar Prova</button>
            </form>
        </article>
    </main>

    <!-- KaTeX JS -->
    <script defer src="https://cdn.jsdelivr.net/npm/katex@0.16.9/dist/katex.min.js"></script>
    <script defer src="https://cdn.jsdelivr.net/npm/katex@0.16.9/dist/contrib/auto-render.min.js"></script>
    <script src="../js/math-config.js"></script>

    <script>
        //  Validação do número de questões
        function validarNumeroQuestoes(input) {
            const valor = parseInt(input.value);
            if (valor < 1) input.value = 1;
            if (valor > 20) input.value = 20;
        }

        function adicionarQuestao() {
            const container = document.getElementById('questoes-container');
            const numQuestoes = document.getElementById('numero_questoes').value;
            
            //  Validar número de questões
            if (numQuestoes < 1 || numQuestoes > 20) {
                alert('Número de questões deve ser entre 1 e 20');
                return;
            }
            
            container.innerHTML = '';
            
            for (let i = 1; i <= numQuestoes; i++) {
                container.innerHTML += `
                    <div class="questao">
                        <h3>Questão ${i}</h3>
                        <div>
                            <label>Enunciado:</label>
                            <textarea name="enunciado_${i}" rows="3"
                                      placeholder="Ex: Resolva a equação $x^2 + \frac{5}{78} = 9$"
                                      required
                                      maxlength="2000"></textarea>
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
                                multiple accept="image/jpeg,image/png,image/gif"
                                style="display: none;"
                                onchange="previewImagens(${i}, this.files)">
                            
                            <div id="preview_${i}" class="lista-imagens"></div>
                        </div>
                        <div>
                            <label>Alternativa A:</label>
                            <input type="text" name="alternativa_a_${i}" required maxlength="500">
                        </div>
                        <div>
                            <label>Alternativa B:</label>
                            <input type="text" name="alternativa_b_${i}" required maxlength="500">
                        </div>
                        <div>
                            <label>Alternativa C:</label>
                            <input type="text" name="alternativa_c_${i}" required maxlength="500">
                        </div>
                        <div>
                            <label>Alternativa D:</label>
                            <input type="text" name="alternativa_d_${i}" required maxlength="500">
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
            if (typeof renderizarEquacoesNoElemento !== 'undefined') {
                renderizarEquacoesNoElemento(container);
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

        //  Garantir que as questões sejam enviadas no formulário
        document.querySelector('form').addEventListener('submit', function(e) {
            const numQuestoes = document.getElementById('numero_questoes').value;
            const container = document.getElementById('questoes-container');
            
            // Validar número de questões
            if (numQuestoes < 1 || numQuestoes > 20) {
                alert('Número de questões inválido!');
                e.preventDefault();
                return;
            }
            
            // Se não há questões visíveis, gerá-las agora
            if (container.children.length === 0 && numQuestoes > 0) {
                adicionarQuestao();
                
                // Pequeno delay para garantir renderização
                setTimeout(() => {
                    this.submit();
                }, 100);
                
                e.preventDefault();
                return;
            }
            
            // Verificar se todas as questões estão preenchidas
            for (let i = 1; i <= numQuestoes; i++) {
                const enunciado = document.querySelector(`textarea[name="enunciado_${i}"]`);
                if (!enunciado || !enunciado.value.trim()) {
                    alert(`Por favor, preencha o enunciado da questão ${i}`);
                    e.preventDefault();
                    return;
                }
                
                // Validar alternativas
                const altA = document.querySelector(`input[name="alternativa_a_${i}"]`);
                const altB = document.querySelector(`input[name="alternativa_b_${i}"]`);
                const altC = document.querySelector(`input[name="alternativa_c_${i}"]`);
                const altD = document.querySelector(`input[name="alternativa_d_${i}"]`);
                
                if (!altA.value.trim() || !altB.value.trim() || !altC.value.trim() || !altD.value.trim()) {
                    alert(`Por favor, preencha todas as alternativas da questão ${i}`);
                    e.preventDefault();
                    return;
                }
            }
        });

        function previewImagens(numeroQuestao, files) {
            const preview = document.getElementById(`preview_${numeroQuestao}`);
            preview.innerHTML = '';
            
            //  Limitar número de imagens
            if (files.length > 5) {
                alert('Máximo 5 imagens por questão');
                return;
            }
            
            for (let file of files) {
                //  Validar tipo de arquivo
                if (!file.type.startsWith('image/')) {
                    alert('Apenas arquivos de imagem são permitidos');
                    continue;
                }
                
                //  Validar tamanho (2MB)
                if (file.size > 2 * 1024 * 1024) {
                    alert('Arquivo muito grande: ' + file.name + '. Máximo 2MB.');
                    continue;
                }
                
                const reader = new FileReader();
                reader.onload = function(e) {
                    const img = document.createElement('img');
                    img.src = e.target.result;
                    img.className = 'preview-imagem';
                    img.alt = 'Preview ' + file.name;
                    img.title = file.name;
                    preview.appendChild(img);
                }
                reader.readAsDataURL(file);
            }
        }

        //  Drag and drop functionality com validação
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
                    try {
                        const questaoNum = target.previousElementSibling.htmlFor.split('_')[1];
                        const input = document.getElementById(`imagens_${questaoNum}`);
                        input.files = e.dataTransfer.files;
                        previewImagens(questaoNum, input.files);
                    } catch (error) {
                        console.error('Erro no drag and drop:', error);
                    }
                }
            });
        });
    </script>
</body>
</html>
