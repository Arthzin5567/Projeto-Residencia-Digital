<?php
session_start();
require_once __DIR__ . '/../config/funcoes_comuns.php';

$aluno_id = verificarLoginAluno();
$conectar = conectarBanco();

// Detectar página atual
$current_page = basename($_SERVER['PHP_SELF']);
$is_professor = isset($_SESSION['tipo_usuario']) && $_SESSION['tipo_usuario'] === 'professor';
$is_aluno = isset($_SESSION['tipo_usuario']) && $_SESSION['tipo_usuario'] === 'aluno';
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $page_title ?? 'Edukhan'; ?></title>
    <link rel="stylesheet" href="../css/style.css">
    <meta http-equiv="Content-Security-Policy" content="default-src 'self'; img-src 'self' data:; style-src 'self' 'unsafe-inline';">
</head>
<body>
    <header>
        <nav>
            <div class="logo">
                <img src="../img/LOGOTIPO 1.avif" alt="logo" onerror="this.style.display='none'">
            </div>
            <ul class="nav-links">
                <?php if ($is_professor): ?>
                    <!-- MENU PROFESSOR INTELIGENTE -->
                    <li><a href="dashboard_professor.php" 
                           class="<?php echo $current_page === 'dashboard_professor.php' ? 'active' : ''; ?>"
                           rel="noopener">Dashboard</a></li>
                    <li><a href="gerenciar_provas.php" 
                           class="<?php echo $current_page === 'gerenciar_provas.php' ? 'active' : ''; ?>"
                           rel="noopener">Minhas Provas</a></li>
                    <li><a href="gerenciar_alunos.php" 
                           class="<?php echo $current_page === 'gerenciar_alunos.php' ? 'active' : ''; ?>"
                           rel="noopener">Alunos</a></li>
                    <li><a href="perfil_professor.php" 
                           class="<?php echo $current_page === 'perfil_professor.php' ? 'active' : ''; ?>"
                           rel="noopener">Meu Perfil</a></li>
                    <li><a href="../logout.php" rel="noopener">Sair</a></li>

                <?php elseif ($is_aluno): ?>
                    <!-- MENU ALUNO INTELIGENTE -->
                    <li><a href="dashboard_aluno.php" 
                           class="<?php echo $current_page === 'dashboard_aluno.php' ? 'active' : ''; ?>">Dashboard</a></li>
                    <li><a href="provas_disponiveis.php" 
                           class="<?php echo $current_page === 'provas_disponiveis.php' ? 'active' : ''; ?>">Provas</a></li>
                    <li><a href="historico.php" 
                           class="<?php echo $current_page === 'historico.php' ? 'active' : ''; ?>">Desempenho</a></li>
                    <li><a href="perfil.php" 
                           class="<?php echo $current_page === 'perfil.php' ? 'active' : ''; ?>">Meu Perfil</a></li>
                    <li><a href="../logout.php">Sair</a></li>

                <?php else: ?>
                    <!-- MENU VISITANTE -->
                    <li><a href="../index.php" 
                           class="<?php echo $current_page === 'index.php' ? 'active' : ''; ?>">Início</a></li>
                    <li><a href="../alunos/identificar_aluno.php" 
                           class="<?php echo $current_page === 'identificar_aluno.php' ? 'active' : ''; ?>">Área do Aluno</a></li>
                <?php endif; ?>
            </ul>
        </nav>
    </header>
    <main>
