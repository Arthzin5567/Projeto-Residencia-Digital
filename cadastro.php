<?php
session_start();
?>

<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cadastro de Aluno - Edukhan</title>
    <link rel="stylesheet" href="css/style.css">
</head>
<body>
    <header>
        <nav>
            <div class="logo">
                <img src="img/LOGOTIPO 1.avif" alt="logo">
            </div>
            <ul class="nav-links">
                <li><a href="index.php">Login Professor</a></li>
                <li><a href="cadastro.php">Cadastro Aluno</a></li>
                <li><a href="aluno/identificar_aluno.php">Área do Aluno</a></li>
                <li><a href="#">Suporte</a></li>
            </ul>
        </nav>
    </header>

    <main>
        <article class="cadastro-article">
            <div class="cadastro-container">
                <div class="cadastro-header">
                    <h1>Cadastro de Aluno</h1>
                    <p>Preencha os dados abaixo para cadastrar um novo aluno</p>
                </div>
                
                <form id="cadastroForm" action="includes/processa_cadastro.php" method="POST">
                    <!-- Dados Pessoais do Aluno -->
                    <h3>Dados Pessoais do Aluno</h3>

                    <div class="form-group">
                        <label for="nome">Nome Completo *</label>
                        <input type="text" id="nome" name="nome" placeholder="Digite o nome completo" required>
                    </div>

                    <div class="form-row">
                        <div class="form-group">
                            <label for="idade">Idade *</label>
                            <input type="number" id="idade" name="idade" placeholder="Idade" min="8" max="100" required>
                            <small>Alunos a partir de 8 anos</small>
                        </div>
                        
                        <div class="form-group">
                            <label for="cpf">CPF *</label>
                            <input type="text" id="cpf" name="cpf" placeholder="Apenas números" maxlength="11" required>
                        </div>
                    </div>

                    <div class="form-group">
                        <label for="escolaridade">Série/Escolaridade - sua série atual ou onde parou *</label>
                        <select id="escolaridade" name="escolaridade" required>
                            <option value="">Selecione a série...</option>
                            <option value="1º ano EF">1º ano - Fundamental 1</option>
                            <option value="2º ano EF">2º ano - Fundamental 1</option>
                            <option value="3º ano EF">3º ano - Fundamental 1</option>
                            <option value="4º ano EF">4º ano - Fundamental 1</option>
                            <option value="5º ano EF">5º ano - Fundamental 1</option>
                            <option value="6º ano EF">6º ano - Fundamental 2</option>
                            <option value="7º ano EF">7º ano - Fundamental 2</option>
                            <option value="8º ano EF">8º ano - Fundamental 2</option>
                            <option value="9º ano EF">9º ano - Fundamental 2</option>
                            <option value="1º ano EM">1º ano - Ensino Médio</option>
                            <option value="2º ano EM">2º ano - Ensino Médio</option>
                            <option value="3º ano EM">3º ano - Ensino Médio</option>
                        </select>
                    </div>

                    <!-- Contato -->
                    <h3>Contato</h3>
                    
                    <div class="form-group">
                        <label for="email">E-mail</label>
                        <input type="email" id="email" name="email" placeholder="E-mail para contato">
                    </div>

                    <div class="form-row">
                        <div class="form-group">
                            <label for="telefone">Telefone</label>
                            <input type="text" id="telefone" name="telefone" placeholder="(11) 99999-9999" maxlength="15">
                        </div>
                        
                        <div class="form-group">
                            <label for="endereco">Endereço</label>
                            <input type="text" id="endereco" name="endereco" placeholder="Endereço completo">
                        </div>
                    </div>

                    <!-- Dados do Responsável (CONDICIONAL) -->
                    <div id="campos-responsavel">
                        <h3>Dados do Responsável <span class="obrigatorio">(Obrigatório)</span></h3>
                        
                        <div class="form-group">
                            <label for="nome_responsavel">Nome do Responsável</label>
                            <input type="text" id="nome_responsavel" name="nome_responsavel" placeholder="Nome completo do responsável">
                            <small class="info-responsavel">Obrigatório para menores de 18 anos</small>
                        </div>
                        
                        <div class="form-group">
                            <label for="telefone_responsavel">Telefone do Responsável</label>
                            <input type="text" id="telefone_responsavel" name="telefone_responsavel" placeholder="(11) 99999-9999" maxlength="15">
                            <small class="info-responsavel">Obrigatório para menores de 18 anos</small>
                        </div>
                    </div>

                    <!-- Dados Escolares -->
                    <h3>Dados Escolares</h3>
                    
                    <div class="form-row">
                        <div class="form-group">
                            <label for="escola">Escola - A última escola que você estudou ou ainda estuda</label>
                            <input type="text" id="escola" name="escola" placeholder="Nome da escola">
                        </div>
                        
                        <div class="form-group">
                            <label for="turma">Turma</label>
                            <input type="text" id="turma" name="turma" placeholder="Turma/Classe">
                        </div>
                    </div>

                    <!-- Código de Acesso (GERADO AUTOMATICAMENTE) -->
                    <div class="form-group">
                        <label for="codigo_acesso">Código de Acesso *</label>
                        <input type="text" id="codigo_acesso" name="codigo_acesso" placeholder="Será gerado automaticamente" readonly required>
                        <small>Este código será usado por você para acessar as provas. Não se esqueça dele! Recomendo anotar!</small>
                    </div>
                    
                    <button type="submit" class="btn">Cadastrar</button>
                </form>
                
                <div class="cadastro-links">
                    <p>Você já está cadastrado? <a href="aluno/identificar_aluno.php">Acesse com seu código</a></p>
                    <p>É professor? <a href="login_professor.php">Faça login aqui</a></p>
                </div>

                <!-- Mensagens de feedback -->
                <?php if (isset($_GET['success']) && $_GET['success'] == '1'): ?>
                    <div class="success-message">
                        Cadastro realizado com sucesso! Você já pode fazer login.
                    </div>
                <?php endif; ?>
                
                <?php if (isset($_GET['error'])): ?>
                    <div class="error-message">
                        <?php
                        $errors = [
                            '1' => 'Erro no cadastro. Tente novamente.',
                            '2' => 'Código de acesso já existe. Tente novamente.',
                            '3' => 'E-mail já cadastrado.',
                            '4' => 'CPF já cadastrado.',
                            '5' => 'Preencha todos os campos obrigatórios.'
                        ];
                        echo $errors[$_GET['error']] ?? 'Erro desconhecido.';
                        ?>
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
            <p class="copyright">© 2023 Edukhan - Plataforma de Avaliação Educacional. Todos os direitos reservados.</p>
        </div>
    </footer>

    <script>
        // Gerar código de acesso automaticamente
        function gerarCodigoAcesso() {
            const caracteres = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789';
            let codigo = '';
            for (let i = 0; i < 8; i++) {
                codigo += caracteres.charAt(Math.floor(Math.random() * caracteres.length));
            }
            document.getElementById('codigo_acesso').value = codigo;
        }

        // Gerar código quando a página carregar
        document.addEventListener('DOMContentLoaded', function() {
            gerarCodigoAcesso();
        });

        // Validação no frontend
        document.getElementById('cadastroForm').addEventListener('submit', function(e) {
            const idade = document.getElementById('idade').value;
            const codigoAcesso = document.getElementById('codigo_acesso').value;
            
            // Verificar idade entre 8 e 100 anos (conforme desafio)
            if (idade < 8 || idade > 100) {
                e.preventDefault();
                alert('A idade deve ser entre 8 e 100 anos! Ou você é muito novo, ou é muito velho para estar aqui :)');
                return;
            }
            
            // Verificar se código de acesso foi gerado
            if (!codigoAcesso || codigoAcesso.length !== 8) {
                e.preventDefault();
                alert('Erro ao gerar código de acesso. Recarregue a página.');
                return;
            }

            // Formatação do CPF - apenas números
            const cpf = document.getElementById('cpf');
            cpf.value = cpf.value.replace(/\D/g, '');
        });

        // Formatação do telefone
        document.getElementById('telefone').addEventListener('input', function(e) {
            let value = e.target.value.replace(/\D/g, '');
            if (value.length > 11) value = value.substring(0, 11);
            
            if (value.length > 6) {
                value = value.replace(/(\d{2})(\d{5})(\d{4})/, '($1) $2-$3');
            } else if (value.length > 2) {
                value = value.replace(/(\d{2})(\d{0,5})/, '($1) $2');
            }
            e.target.value = value;
        });

        document.getElementById('telefone_responsavel').addEventListener('input', function(e) {
            let value = e.target.value.replace(/\D/g, '');
            if (value.length > 11) value = value.substring(0, 11);
            
            if (value.length > 6) {
                value = value.replace(/(\d{2})(\d{5})(\d{4})/, '($1) $2-$3');
            } else if (value.length > 2) {
                value = value.replace(/(\d{2})(\d{0,5})/, '($1) $2');
            }
            e.target.value = value;
        });

        // Controlar campos do responsável baseado na idade
        function toggleCamposResponsavel() {
            const idade = parseInt(document.getElementById('idade').value) || 0;
            const camposResponsavel = document.getElementById('campos-responsavel');
            const nomeResponsavel = document.getElementById('nome_responsavel');
            const telResponsavel = document.getElementById('telefone_responsavel');
            
            if (idade >= 18) {
                // Maior de idade - campos opcionais
                camposResponsavel.style.opacity = '0.7';
                nomeResponsavel.required = false;
                telResponsavel.required = false;
                document.querySelector('#campos-responsavel .obrigatorio').textContent = '(Opcional)';
            } else {
                // Menor de idade - campos obrigatórios
                camposResponsavel.style.opacity = '1';
                nomeResponsavel.required = true;
                telResponsavel.required = true;
                document.querySelector('#campos-responsavel .obrigatorio').textContent = '(Obrigatório)';
            }
        }

        // Adicionar ao DOMContentLoaded
        document.addEventListener('DOMContentLoaded', function() {
            gerarCodigoAcesso();
            toggleCamposResponsavel(); // Executar uma vez ao carregar
            
            // Adicionar evento quando idade mudar
            document.getElementById('idade').addEventListener('input', toggleCamposResponsavel);
            document.getElementById('idade').addEventListener('change', toggleCamposResponsavel);
        });
    </script>
</body>
</html>