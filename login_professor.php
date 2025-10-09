<?php
session_start();
/*
colocar mais tarde
    <link rel="stylesheet" href="css/style.css"> 
*/
?>

<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - Edukhan</title>
</head>
<body>
    <header>
        <nav>
            <div class="logo">Edukhan</div>
            <ul class="nav-links">
                <li><a href="index.php">Login</a></li>
                <li><a href="cadastro.php">Cadastro</a></li>
                <li><a href="#">Sobre</a></li>
                <li><a href="#">Suporte</a></li>
            </ul>
        </nav>
    </header>

    <main>
        <article class="login-article">
            <div class="login-container">
                <div class="login-header">
                    <h1>Área de Login</h1>
                    <p>Digite suas credenciais para acessar o sistema</p>
                </div>
                
                <form id="loginForm" action="valida_login.php" method="POST">
                    <div class="form-group">
                        <label for="username">Usuário</label>
                        <input type="text" id="username" name="username" placeholder="Digite seu usuário" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="password">Senha</label>
                        <input type="password" id="password" name="password" placeholder="Digite sua senha" required>
                    </div>
                    
                    <button type="submit" class="btn">Entrar</button>
                </form>
                
                <div class="login-links">
                    <p>Não possui conta? <a href="cadastro.php">Cadastre-se aqui</a></p>
                </div>

                <!-- Mensagens de erro/sucesso -->
                <?php if (isset($_GET['error']) && $_GET['error'] == '1'): ?>
                    <div class="error-message">
                        Credenciais inválidas. Tente novamente.
                    </div>
                <?php endif; ?>
                
                <?php if (isset($_GET['success']) && $_GET['success'] == '1'): ?>
                    <div class="success-message">
                        Cadastro realizado com sucesso! Faça login.
                    </div>
                <?php endif; ?>
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
            <p class="copyright">© 2023 AvaliaEduca - Plataforma de Avaliação Educacional. Todos os direitos reservados.</p>
        </div>
    </footer>

    <script>
        // Validação básica no frontend (opcional)
        document.getElementById('loginForm').addEventListener('submit', function(e) {
            const username = document.getElementById('username').value;
            const password = document.getElementById('password').value;
            
            if (username.trim() === '' || password.trim() === '') {
                e.preventDefault();
                alert('Por favor, preencha todos os campos.');
            }
        });
    </script>
</body>
</html>