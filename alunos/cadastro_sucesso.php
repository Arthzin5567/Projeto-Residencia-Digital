<?php
session_start();
$codigo = isset($_GET['codigo']) ? $_GET['codigo'] : '';
$nome = isset($_GET['nome']) ? $_GET['nome'] : '';

if (empty($codigo)) {
    header("Location: ../cadastro.php");
    exit();
}
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
            <p>Aluno: <strong><?php echo htmlspecialchars($nome); ?></strong></p>
            
            <div class="aluno-proximas-acoes">
                <h2>Seu código de acesso é:</h2>
                <div>
                    <?php echo htmlspecialchars($codigo); ?>
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
                <button onclick="window.print()">
                    Imprimir Código
                </button>
                <button onclick="window.location.href='../index.php'">
                    Página Inicial
                </button>
            </div>

            <div class="aluno-proximas-acoes">
                <h3>📝 Como usar seu código:</h3>
                <ol>
                    <li>Vá para <strong>Área do Aluno</strong> na página inicial</li>
                    <li>Digite seu código: <strong><?php echo htmlspecialchars($codigo); ?></strong></li>
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
        // Copiar código para área de transferência
        function copiarCodigo() {
            const codigo = '<?php echo $codigo; ?>';
            navigator.clipboard.writeText(codigo).then(function() {
                alert('Código copiado para a área de transferência!');
            }, function(err) {
                alert('Erro ao copiar código: ' + err);
            });
        }
    </script>
</body>
</html>