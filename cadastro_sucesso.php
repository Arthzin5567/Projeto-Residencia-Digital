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
    <title>Cadastro Realizado - AvaliaEduca</title>
</head>
<body>
    <header>
        <nav>
            <div class="logo">AvaliaEduca - Cadastro Realizado</div>
        </nav>
    </header>

    <main>
        <article>
            <h1>Cadastro Realizado com Sucesso! 🎉</h1>
            <p>Aluno: <strong><?php echo htmlspecialchars($nome); ?></strong></p>
            
            <div>
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

            <div>
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

            <div>
                <h3>📝 Como usar seu código:</h3>
                <ol>
                    <li>Vá para <strong>Área do Aluno</strong> na página inicial</li>
                    <li>Digite seu código: <strong><?php echo htmlspecialchars($codigo); ?></strong></li>
                    <li>Clique em "Entrar"</li>
                </ol>
            </div>
        </article>
    </main>

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