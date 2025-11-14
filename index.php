<?php
session_start();
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edukhan - Sistema de Avaliação</title>
    <link rel="stylesheet" href="css/style.css">
</head>
<body>
    <header>
        <nav>
            <div class="logo">
                <img src="img/LOGOTIPO 1.avif" alt="logo">
            </div>
            <ul class="nav-links">
                <li><a href="index.php">Início</a></li>
                <li><a href="#">Sobre</a></li>
                <li><a href="#">Suporte</a></li>
            </ul>
        </nav>
    </header>

    <main>
        <article class="area-acesso">
            <h1>Bem-vindo ao Edukhan</h1>
            <p>Selecione sua área de acesso:</p>
            
            <div>
                <!-- Área do Aluno -->
                <div>
                    <h2>Área do Aluno</h2>
                    <p>Acesse suas provas e resultados</p>
            <button onclick="window.location.href='alunos/identificar_aluno.php'">
                        Entrar como Aluno
                    </button>
                </div>

                <!-- Área do Professor -->
                <div>
                    <h2>Área do Professor</h2>
                    <p>Gerencie provas e acompanhe resultados</p>
            <button onclick="window.location.href='login_professor.php'">
                        Entrar como Professor
                    </button>
                </div>
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
</body>
</html>
