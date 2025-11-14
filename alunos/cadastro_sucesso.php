<?php
session_start();

$codigo = isset($_GET['codigo']) ? trim($_GET['codigo']) : '';
$nome = isset($_GET['nome']) ? trim($_GET['nome']) : '';

if (empty($codigo) || strlen($codigo) > 20 || !preg_match('/^[a-zA-Z0-9]+$/', $codigo)) {
    header("Location: ../cadastro.php?erro=codigo_invalido");
    exit();
}

$codigo_seguro = htmlspecialchars($codigo, ENT_QUOTES, 'UTF-8');
$nome_seguro = htmlspecialchars($nome, ENT_QUOTES, 'UTF-8');
?>

<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cadastro Realizado - Edukhan</title>
    <link rel="stylesheet" href="../css/style.css">
</head>
<body>
    <header>
        <nav>
            <div class="logo">
                <img src="../img/LOGOTIPO 1.avif" alt="logo">
            </div>
        </nav>
    </header>

    <main>
        <article class="dashboard-aluno">
            <h1>Cadastro Realizado com Sucesso! 🎉</h1>
            <p>Aluno: <strong><?php echo $nome_seguro; ?></strong></p>
            
            <div class="aluno-proximas-acoes">
                <h2>Seu código de acesso é:</h2>
                <div id="codigo-acesso">
                    <?php echo $codigo_seguro; ?>
                </div>
                <p><strong>⚠️ GUARDE ESTE CÓDIGO COM CUIDADO!</strong></p>
                <p>Você precisará dele para:</p>
                <ul>
                    <li>Acessar o sistema</li>
                    <li>Fazer avaliações</li>
                    <li>Consultar resultados</li>
                </ul>
            </div>

            <div class="aluno-proximas-acoes">
                <button onclick="window.location.href='identificar_aluno.php'">
                    Fazer Login Agora
                </button>
                <button onclick="copiarCodigo()">
                    📋 Copiar Código
                </button>
                <button onclick="window.print()">
                    🖨️ Imprimir Código
                </button>
                <button onclick="window.location.href='../index.php'">
                    🏠 Página Inicial
                </button>
            </div>

            <div class="aluno-proximas-acoes">
                <h3>📝 Como usar seu código:</h3>
                <ol>
                    <li>Vá para <strong>Área do Aluno</strong> na página inicial</li>
                    <li>Digite seu código: <strong><?php echo $codigo_seguro; ?></strong></li>
                    <li>Clique em "Fazer Login Agora"</li>
                </ol>
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

    <script>
        function copiarCodigo() {
            const codigoElement = document.getElementById('codigo-acesso');
            const codigo = codigoElement.textContent.trim();
            
            navigator.clipboard.writeText(codigo).then(function() {
                alert('✅ Código copiado para a área de transferência!');
            }).catch(function(err) {
                const textArea = document.createElement('textarea');
                textArea.value = codigo;
                document.body.appendChild(textArea);
                textArea.select();
                document.execCommand('copy');
                document.body.removeChild(textArea);
                alert('✅ Código copiado para a área de transferência!');
            });
        }
    </script>
</body>
</html>
