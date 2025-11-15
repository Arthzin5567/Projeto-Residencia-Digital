<?php
session_start();
require_once __DIR__ . '/../config/funcoes_comuns.php';

$aluno_id = verificarLoginAluno();
$conectar = conectarBanco();
?>
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
            <?php if (isset($_SESSION['usuario'])): ?>
                <p><small>Usuário: <strong class="dado-seguro"><?php echo htmlspecialchars($_SESSION['usuario']); ?></strong></small></p>
            <?php endif; ?>
        </div>
    </footer>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            document.addEventListener('contextmenu', function(e) {
                if (e.target.tagName === 'IMG') {
                    e.preventDefault();
                }
            });
        });
    </script>
</body>
</html>
