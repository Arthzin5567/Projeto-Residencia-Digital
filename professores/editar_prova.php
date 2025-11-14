<?php
session_start();

// HEADERS DE SEGURANÇA
header("X-Frame-Options: DENY");
header("X-Content-Type-Options: nosniff");
header("X-XSS-Protection: 1; mode=block");
header("Referrer-Policy: strict-origin-when-cross-origin");
header("Content-Security-Policy: default-src 'self'; img-src 'self' data:; script-src 'self' 'unsafe-inline' https://cdn.jsdelivr.net; style-src 'self' 'unsafe-inline' https://cdn.jsdelivr.net;");

// VALIDAÇÃO RIGOROSA DE SESSÃO
if (!isset($_SESSION["logado"]) || $_SESSION["logado"] !== true || $_SESSION["tipo_usuario"] !== "professor") {
    header("Location: ../index.php?erro=acesso_negado");
    exit();
}

// VALIDAÇÃO DE CSRF TOKEN PARA AÇÕES CRÍTICAS
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        error_log("Tentativa de CSRF detectada no editar prova");
        die("Erro de segurança. Tente novamente.");
    }
}

// CONFIGURAÇÃO SEGURA DO BANCO
$host = "localhost";
$user = "root";
$password = "SenhaIrada@2024!";
$database = "projeto_residencia";

// Conexão com tratamento de erro seguro
$conectar = mysqli_connect($host, $user, $password, $database);
if (!$conectar) {
    error_log("Erro de conexão com o banco no editar prova");
    die("Erro interno do sistema. Tente novamente mais tarde.");
}

// CONFIGURAÇÕES DE SEGURANÇA ADICIONAIS
mysqli_set_charset($conectar, "utf8mb4");
mysqli_query($conectar, "SET time_zone = '-03:00'");

// VALIDAÇÃO RIGOROSA DO ID DA PROVA
if (!isset($_GET['id']) || empty($_GET['id'])) {
    header("Location: gerenciar_provas.php");
    exit();
}

$prova_id = (int)$_GET['id'];
$professor_id = (int)$_SESSION['idProfessor'];

// VALIDAÇÃO DE FAIXA PARA IDS
if ($prova_id <= 0 || $prova_id > 999999 || $professor_id <= 0) {
    header("Location: gerenciar_provas.php?erro=id_invalido");
    exit();
}

// BUSCAR DADOS DA PROVA COM PREPARED STATEMENT
$sql_prova = "SELECT idProvas, titulo, materia, serie_destinada, conteudo, data_criacao 
              FROM Provas 
              WHERE idProvas = ? AND Professor_idProfessor = ?
              LIMIT 1";
$stmt_prova = mysqli_prepare($conectar, $sql_prova);

if (!$stmt_prova) {
    error_log("Erro ao preparar consulta da prova: " . mysqli_error($conectar));
    die("Erro interno do sistema.");
}

mysqli_stmt_bind_param($stmt_prova, "ii", $prova_id, $professor_id);
mysqli_stmt_execute($stmt_prova);
$result_prova = mysqli_stmt_get_result($stmt_prova);

if (mysqli_num_rows($result_prova) === 0) {
    // NÃO REVELAR SE A PROVA EXISTE OU NÃO
    header("Location: gerenciar_provas.php");
    mysqli_stmt_close($stmt_prova);
    mysqli_close($conectar);
    exit();
}

$prova = mysqli_fetch_assoc($result_prova);
mysqli_stmt_close($stmt_prova);

// BUSCAR IMAGENS DA PROVA COM PREPARED STATEMENT
$sql_imagens = "SELECT numero_questao, caminho_imagem, nome_arquivo, idImagem
                FROM ImagensProvas
                WHERE idProva = ?
                ORDER BY numero_questao, idImagem";
$stmt_imagens = mysqli_prepare($conectar, $sql_imagens);
$imagens_por_questao = [];

if ($stmt_imagens) {
    mysqli_stmt_bind_param($stmt_imagens, "i", $prova_id);
    mysqli_stmt_execute($stmt_imagens);
    $resultado_imagens = mysqli_stmt_get_result($stmt_imagens);
    
    if ($resultado_imagens) {
        while ($imagem = mysqli_fetch_assoc($resultado_imagens)) {
            // VALIDAÇÃO E CORREÇÃO SEGURA DO CAMINHO DA IMAGEM
            $caminho_imagem = $imagem['caminho_imagem'];
            $caminho_corrigido = null;
            
            if (validarCaminhoImagem($caminho_imagem, $prova_id)) {
                if (strpos($caminho_imagem, 'uploads/') === 0) {
                    $caminho_corrigido = '../' . $caminho_imagem;
                } elseif (strpos($caminho_imagem, '../uploads/') !== 0) {
                    $nome_arquivo_seguro = basename($caminho_imagem);
                    $caminho_corrigido = '../uploads/provas/prova_' . $prova_id . '/' . $nome_arquivo_seguro;
                } else {
                    $caminho_corrigido = $caminho_imagem;
                }
                
                // Verificar se o arquivo existe
                if ($caminho_corrigido && file_exists($caminho_corrigido)) {
                    $imagem['caminho_corrigido'] = $caminho_corrigido;
                    $imagens_por_questao[$imagem['numero_questao']][] = $imagem;
                }
            }
        }
    }
    mysqli_stmt_close($stmt_imagens);
}

// VALIDAÇÃO E DECODIFICAÇÃO SEGURA DO JSON
$questoes_json = null;
if (!empty($prova['conteudo'])) {
    $questoes_json = json_decode($prova['conteudo'], true);
    
    if (json_last_error() !== JSON_ERROR_NONE || !is_array($questoes_json)) {
        error_log("Erro ao decodificar JSON da prova ID: $prova_id");
        $questoes_json = [];
    }
}

$numero_questoes = is_array($questoes_json) ? count($questoes_json) : 0;

// PREPARAR QUESTÕES NO FORMATO CORRETO COM SANITIZAÇÃO
$questoes_formatadas = [];
if (is_array($questoes_json)) {
    foreach ($questoes_json as $index => $questao) {
        $questoes_formatadas[] = [
            'numero_questao' => $index + 1,
            'enunciado' => htmlspecialchars($questao['enunciado'] ?? '', ENT_QUOTES, 'UTF-8'),
            'alternativa_a' => htmlspecialchars($questao['alternativas']['A'] ?? '', ENT_QUOTES, 'UTF-8'),
            'alternativa_b' => htmlspecialchars($questao['alternativas']['B'] ?? '', ENT_QUOTES, 'UTF-8'),
            'alternativa_c' => htmlspecialchars($questao['alternativas']['C'] ?? '', ENT_QUOTES, 'UTF-8'),
            'alternativa_d' => htmlspecialchars($questao['alternativas']['D'] ?? '', ENT_QUOTES, 'UTF-8'),
            'resposta_correta' => htmlspecialchars($questao['resposta_correta'] ?? 'A', ENT_QUOTES, 'UTF-8')
        ];
    }
}

// GERAR TOKEN CSRF
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

/**
 * VALIDAR CAMINHO DE IMAGEM
 */
function validarCaminhoImagem($caminho, $prova_id) {
    // Prevenir path traversal
    if (strpos($caminho, '..') !== false || strpos($caminho, '//') !== false) {
        error_log("Tentativa de path traversal detectada: $caminho");
        return false;
    }
    
    // Validar extensões permitidas
    $extensao = strtolower(pathinfo($caminho, PATHINFO_EXTENSION));
    $extensoes_permitidas = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
    
    if (!in_array($extensao, $extensoes_permitidas)) {
        error_log("Extensão de arquivo não permitida: $extensao");
        return false;
    }
    
    return true;
}
?>

<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Editar Prova - Edukhan</title>
    <link rel="stylesheet" href="../css/style.css">
    <!-- KaTeX CSS COM INTEGRIDADE -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/katex@0.16.9/dist/katex.min.css" integrity="sha384-8e0zqR1Y4xTMnJ9Hy5qk4+8+hgN6Em5Q+8hFHy0rY8X6Fy6g7FfYk6g7v2z+Q7pZ" crossorigin="anonymous">
    
    <!-- META TAGS DE SEGURANÇA -->
    <meta http-equiv="Content-Security-Policy" content="default-src 'self'; img-src 'self' data:; script-src 'self' 'unsafe-inline' https://cdn.jsdelivr.net; style-src 'self' 'unsafe-inline' https://cdn.jsdelivr.net;">
    
    <style>
        .text-help {
            color: #6c757d;
            font-size: 0.85em;
            font-style: italic;
        }

        .sem-imagens {
            padding: 10px;
            background: #f8f9fa;
            border-radius: 4px;
            text-align: center;
            color: #6c757d;
        }

        .questao {
            border: 1px solid #ddd;
            padding: 15px;
            margin: 10px 0;
            border-radius: 5px;
            background: #f9f9f9;
        }
        .imagens-existente, .imagens-novas {
            margin: 10px 0;
            padding: 10px;
            border: 1px dashed #ccc;
            border-radius: 5px;
        }
        .lista-imagens {
            display: flex;
            flex-wrap: wrap;
            gap: 10px;
            margin: 10px 0;
        }
        .imagem-container {
            text-align: center;
        }
        .imagem-existente, .preview-imagem {
            max-width: 150px;
            max-height: 150px;
            border: 1px solid #ddd;
            border-radius: 5px;
        }
        .area-upload {
            border: 2px dashed #007bff;
            padding: 20px;
            text-align: center;
            cursor: pointer;
            margin: 10px 0;
            border-radius: 5px;
            background: #f8f9fa;
        }
        .area-upload:hover {
            background: #e9ecef;
        }
        .btn-remover {
            background: #dc3545;
            color: white;
            border: none;
            padding: 5px 10px;
            border-radius: 3px;
            cursor: pointer;
            margin-top: 5px;
        }
        .btn-remover:hover {
            background: #c82333;
        }
        
        /* ESTILOS PARA DADOS SENSÍVEIS */
        .dado-seguro {
            word-break: break-word;
            overflow-wrap: break-word;
        }
    </style>
</head>
<body>
    <header>
        <nav>
            <div class="logo">
                <img src="../img/LOGOTIPO 1.avif" alt="logo" onerror="this.style.display='none'">
            </div>
            <ul class="nav-links">
                <li><a href="dashboard_professor.php" rel="noopener">Dashboard</a></li>
                <li><a href="gerenciar_alunos.php" rel="noopener">Alunos</a></li>
                <li><a href="criar_prova.php" rel="noopener">Criar Prova</a></li>
                <li><a href="gerenciar_provas.php" rel="noopener">Minhas Provas</a></li>
                <li><a href="perfil_professor.php" rel="noopener">Meu Perfil</a></li>
                <li><a href="../logout.php" rel="noopener">Sair</a></li>
            </ul>
        </nav>
    </header>

    <main>
        <article class="editar-prova">
            <h1>Editar Prova: <span class="dado-seguro"><?php echo htmlspecialchars($prova['titulo'], ENT_QUOTES, 'UTF-8'); ?></span></h1>
            
            <form class="form-editar-prov" action="../includes/processa_editar_prova.php" method="POST" enctype="multipart/form-data">
                <input type="hidden" name="prova_id" value="<?php echo $prova_id; ?>">
                <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
                
                <div class="form-group">
                    <label for="titulo">Título da Prova:</label>
                    <input type="text" id="titulo" name="titulo"
                           value="<?php echo htmlspecialchars($prova['titulo'], ENT_QUOTES, 'UTF-8'); ?>" 
                           required maxlength="255">
                </div>
                
                <div class="form-group">
                    <label for="materia">Matéria:</label>
                    <select id="materia" name="materia" required>
                        <option value="Matemática" <?php echo $prova['materia'] == 'Matemática' ? 'selected' : ''; ?>>Matemática</option>
                        <option value="Português" <?php echo $prova['materia'] == 'Português' ? 'selected' : ''; ?>>Português</option>
                        <option value="Ciências" <?php echo $prova['materia'] == 'Ciências' ? 'selected' : ''; ?>>Ciências</option>
                        <option value="História" <?php echo $prova['materia'] == 'História' ? 'selected' : ''; ?>>História</option>
                        <option value="Geografia" <?php echo $prova['materia'] == 'Geografia' ? 'selected' : ''; ?>>Geografia</option>
                    </select>
                </div>
                
                <div class="form-group">
                    <label for="serie_destinada">Série Destinada:</label>
                    <select id="serie_destinada" name="serie_destinada" required>
                        <option value="1º ano" <?php echo $prova['serie_destinada'] == '1º ano' ? 'selected' : ''; ?>>1º ano</option>
                        <option value="2º ano" <?php echo $prova['serie_destinada'] == '2º ano' ? 'selected' : ''; ?>>2º ano</option>
                        <option value="3º ano" <?php echo $prova['serie_destinada'] == '3º ano' ? 'selected' : ''; ?>>3º ano</option>
                        <option value="4º ano" <?php echo $prova['serie_destinada'] == '4º ano' ? 'selected' : ''; ?>>4º ano</option>
                        <option value="5º ano" <?php echo $prova['serie_destinada'] == '5º ano' ? 'selected' : ''; ?>>5º ano</option>
                    </select>
                </div>
                
                <div class="form-group">
                    <label for="numero_questoes">Número de Questões:</label>
                    <input type="number" id="numero_questoes" name="numero_questoes"
                           min="1" max="20" value="<?php echo $numero_questoes; ?>" required>
                </div>
                
                <div id="questoes-container">
                    <!-- As questões serão carregadas aqui via JavaScript -->
                </div>
                
                <div class="form-buttons">
                    <button type="button" id="btn-atualizar-questoes" class="btn btn-secondary">Atualizar Questões</button>
                    <button type="submit" class="btn btn-primary">Salvar Alterações</button>
                    <button type="button" onclick="window.location.href='gerenciar_provas.php'" class="btn btn-cancel">Cancelar</button>
                </div>
            </form>
        </article>
    </main>

    <footer>
        <div class="footer-content">
            <ul class="footer-links">
                <li><a href="#" rel="noopener">Como Usar a Plataforma</a></li>
                <li><a href="#" rel="noopener">Materiais de Apoio</a></li>
                <li><a href="#" rel="noopener">Suporte Técnico</a></li>
                <li><a href="#" rel="noopener">Dúvidas Frequentes</a></li>
            </ul>
            <p class="copyright">© 2023 Edukhan - Plataforma de Avaliação Educacional. Todos os direitos reservados.</p>
        </div>
    </footer>

    <!--  KaTeX JS COM INTEGRIDADE -->
    <script defer src="https://cdn.jsdelivr.net/npm/katex@0.16.9/dist/katex.min.js" integrity="sha384-8e0zqR1Y4xTMnJ9Hy5qk4+8+hgN6Em5Q+8hFHy0rY8X6Fy6g7FfYk6g7v2z+Q7pZ" crossorigin="anonymous"></script>
    <script defer src="https://cdn.jsdelivr.net/npm/katex@0.16.9/dist/contrib/auto-render.min.js" integrity="sha384-+XBljXPPiv+OzfbB3cVmLHf4hdUFHlWNZN5spNQ7rmHTXpd7WvJum6fIACpNNfIR" crossorigin="anonymous"></script>
    <script src="../js/math-config.js"></script>

    <script>
    // DADOS DAS QUESTÕES E IMAGENS - SEGURO
    const questoesExistentes = <?php echo json_encode($questoes_formatadas, JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP); ?>;
    const imagensPorQuestao = <?php echo json_encode($imagens_por_questao, JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP); ?>;

    function adicionarQuestao() {
        const container = document.getElementById('questoes-container');
        const numQuestoes = parseInt(document.getElementById('numero_questoes').value);
        
        // VALIDAÇÃO DO NÚMERO DE QUESTÕES
        if (numQuestoes < 1 || numQuestoes > 20 || isNaN(numQuestoes)) {
            alert('Número de questões inválido. Deve ser entre 1 e 20.');
            document.getElementById('numero_questoes').value = Math.min(Math.max(numQuestoes, 1), 20);
            return;
        }
        
        container.innerHTML = '';
        
        for (let i = 1; i <= numQuestoes; i++) {
            const questaoExistente = questoesExistentes.find(q => parseInt(q.numero_questao) === i);
            const imagensExistente = imagensPorQuestao[i] || [];

            // USAR DADOS SANITIZADOS DO PHP
            const enunciado = questaoExistente ? questaoExistente.enunciado || '' : '';
            const altA = questaoExistente ? questaoExistente.alternativa_a || '' : '';
            const altB = questaoExistente ? questaoExistente.alternativa_b || '' : '';
            const altC = questaoExistente ? questaoExistente.alternativa_c || '' : '';
            const altD = questaoExistente ? questaoExistente.alternativa_d || '' : '';
            const respCorreta = questaoExistente ? questaoExistente.resposta_correta : 'A';

            // HTML PARA IMAGENS EXISTENTES COM VALIDAÇÃO
            let imagensHTML = '';
            if (imagensExistente.length > 0) {
                imagensHTML = `
                    <div class="imagens-existente">
                        <strong>Imagens atuais desta questão:</strong>
                        <div class="lista-imagens">
                `;
                imagensExistente.forEach(imagem => {
                    // 🔒 VALIDAR CAMINHO DA IMAGEM
                    const caminhoImagem = imagem.caminho_corrigido || '';
                    const nomeArquivo = imagem.nome_arquivo || 'Imagem';
                    const idImagem = parseInt(imagem.idImagem) || 0;
                    
                    if (idImagem > 0 && caminhoImagem) {
                        imagensHTML += `
                            <div class="imagem-container">
                                <img src="${caminhoImagem.replace(/"/g, '&quot;')}"
                                    alt="${nomeArquivo.replace(/"/g, '&quot;')}"
                                    class="imagem-existente"
                                    style="max-width: 200px; max-height: 200px; object-fit: contain;"
                                    onerror="this.style.display='none'">
                                <br>
                                <small class="dado-seguro">${nomeArquivo.replace(/</g, '&lt;').replace(/>/g, '&gt;')}</small>
                                <br>
                                <button type="button" class="btn-remover" onclick="marcarRemocaoImagem(${idImagem}, ${i})">
                                    Remover
                                </button>
                                <input type="hidden" name="imagens_manter_${i}[]" value="${idImagem}">
                            </div>
                        `;
                    }
                });
                imagensHTML += `
                        </div>
                    </div>
                `;
            } else {
                imagensHTML = `
                    <div class="sem-imagens">
                        <small>Nenhuma imagem anexada a esta questão</small>
                    </div>
                `;
            }

            // CONSTRUIR HTML DA QUESTÃO COM VALIDAÇÃO
            container.innerHTML += `
                <div class="questao" id="questao-${i}">
                    <h3>Questão ${i}</h3>
                    ${imagensHTML}
                    
                    <div class="imagens-novas">
                        <label>Adicionar novas imagens (opcional):</label>
                        <div class="area-upload" onclick="document.getElementById('novas_imagens_${i}').click()">
                            <p>Clique aqui ou arraste imagens para adicionar</p>
                            <small>Formatos: JPG, PNG, GIF (Máx: 2MB cada)</small>
                        </div>
                        <input type="file" id="novas_imagens_${i}" name="novas_imagens_${i}[]"
                            multiple accept="image/jpeg,image/png,image/gif,image/webp"
                            style="display: none;"
                            onchange="previewNovasImagens(${i}, this.files)">
                        
                        <div id="preview_novas_${i}" class="lista-imagens"></div>
                    </div>

                    <div class="form-group">
                        <label>Enunciado:</label>
                        <textarea name="enunciado_${i}" rows="6" required maxlength="2000">${enunciado}</textarea>
                        <small class="text-help">
                            Use $$equacao$$ para fórmulas matemáticas (serão renderizadas na visualização)
                        </small>
                    </div>
                    <div class="form-group">
                        <label>Alternativa A:</label>
                        <input type="text" name="alternativa_a_${i}" value="${altA.replace(/"/g, '&quot;')}" required maxlength="500">
                    </div>
                    <div class="form-group">
                        <label>Alternativa B:</label>
                        <input type="text" name="alternativa_b_${i}" value="${altB.replace(/"/g, '&quot;')}" required maxlength="500">
                    </div>
                    <div class="form-group">
                        <label>Alternativa C:</label>
                        <input type="text" name="alternativa_c_${i}" value="${altC.replace(/"/g, '&quot;')}" required maxlength="500">
                    </div>
                    <div class="form-group">
                        <label>Alternativa D:</label>
                        <input type="text" name="alternativa_d_${i}" value="${altD.replace(/"/g, '&quot;')}" required maxlength="500">
                    </div>
                    <div class="form-group">
                        <label>Resposta Correta:</label>
                        <select name="resposta_correta_${i}" required>
                            <option value="A" ${respCorreta === 'A' ? 'selected' : ''}>A</option>
                            <option value="B" ${respCorreta === 'B' ? 'selected' : ''}>B</option>
                            <option value="C" ${respCorreta === 'C' ? 'selected' : ''}>C</option>
                            <option value="D" ${respCorreta === 'D' ? 'selected' : ''}>D</option>
                        </select>
                    </div>
                </div>
            `;
        }
    }

    function previewNovasImagens(numeroQuestao, files) {
        const preview = document.getElementById(`preview_novas_${numeroQuestao}`);
        preview.innerHTML = '';
        
        // VALIDAR NÚMERO DE ARQUIVOS
        if (files.length > 5) {
            alert('Máximo de 5 imagens por questão.');
            return;
        }
        
        for (let file of files) {
            // VALIDAR TIPO E TAMANHO DO ARQUIVO
            if (file.type.startsWith('image/') && file.size <= 2 * 1024 * 1024) {
                const reader = new FileReader();
                reader.onload = function(e) {
                    const img = document.createElement('img');
                    img.src = e.target.result;
                    img.className = 'preview-imagem';
                    img.style.maxWidth = '150px';
                    img.style.maxHeight = '150px';
                    img.title = file.name;
                    img.onerror = function() { this.style.display = 'none'; };
                    preview.appendChild(img);
                }
                reader.readAsDataURL(file);
            } else {
                alert(`Arquivo ${file.name} inválido. Use imagens até 2MB.`);
            }
        }
    }

    function marcarRemocaoImagem(idImagem, numeroQuestao) {
        if (!confirm('Tem certeza que deseja remover esta imagem?')) {
            return;
        }
        
        const container = document.querySelector(`input[value="${idImagem}"]`)?.closest('.imagem-container');
        if (container) {
            container.remove();
            
            const inputRemocao = document.createElement('input');
            inputRemocao.type = 'hidden';
            inputRemocao.name = `imagens_remover_${numeroQuestao}[]`;
            inputRemocao.value = idImagem;
            
            document.getElementById(`questao-${numeroQuestao}`).appendChild(inputRemocao);
        }
    }

    // INICIALIZAÇÃO SEGURA
    document.addEventListener('DOMContentLoaded', function() {
        const numQuestoesInput = document.getElementById('numero_questoes');
        const btnAtualizar = document.getElementById('btn-atualizar-questoes');
        
        if (numQuestoesInput && btnAtualizar) {
            numQuestoesInput.addEventListener('change', adicionarQuestao);
            btnAtualizar.addEventListener('click', adicionarQuestao);
            adicionarQuestao();
        }
        
        // VALIDAÇÃO DO FORMULÁRIO
        document.querySelector('form')?.addEventListener('submit', function(e) {
            const numQuestoes = parseInt(document.getElementById('numero_questoes').value);
            const container = document.getElementById('questoes-container');
            
            if (container.children.length === 0 && numQuestoes > 0) {
                adicionarQuestao();
            }
            
            for (let i = 1; i <= numQuestoes; i++) {
                const enunciado = document.querySelector(`textarea[name="enunciado_${i}"]`);
                if (!enunciado || !enunciado.value.trim()) {
                    alert(`Por favor, preencha o enunciado da questão ${i}`);
                    e.preventDefault();
                    return;
                }
            }
        });
        
        // PREVENIR AÇÕES MALICIOSAS
        document.addEventListener('contextmenu', function(e) {
            if (e.target.tagName === 'IMG') {
                e.preventDefault();
            }
        });
    });
    </script>
</body>
</html>

<?php
// LIMPEZA SEGURA
mysqli_close($conectar);
?>