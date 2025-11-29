# Projeto Residencia Digital
Este é um projeto referente à matéria de Residência Digital da minha faculdade. Não sei se posso dar muitos detalhes aqui, mas é um site referente à uma plataforma de educação. O backend foi inteiramente feito em PHP e no momento da criação deste repositório o CSS ainda não foi aplicado. É recomendado o uso do XAMPP para hospedar o projeto no localhost, mas pode funcionar em programas semelhantes.

## Atualizações:

- A partir de quando os commits ganharam uma versão ao lado, significa que o site já estava em sua fase final de desenvolvimento.
- A partir da V1.2 as atualizações foram focadas na qualidade de software do site usando a ferramenta da SonarCloud. O foco foi reduzir o máximo de issues possível.
- Na versão V1.3.1 o site conseguiu passar nos requisitos mínimos da SonarCloud, mas o sistema ainda não está pronto, então ele pode sair de algum requisito após alguma atualização que adicione alguma feature.
- As próximas features a serem adicionadas vão ser a possibilidade de ativar e desativar as provas e talvez ter a possibilidade de adicionar imagens às alternativas das provas.
- A partir da versão 1.4 o site já pode ser implantado e implementado, pois ele está finalizado. Ele já vai estar funcionando corretamente.
  
## Tecnologias usadas:
- XAMPP
- MySQL Workbench
- Visual Code
- SonarCloud

## Linguagens usadas:
- PHP
- SQL
- HTML
- CSS

# Manual

- OBS.: as instruções a seguir são voltadas para hospedagem em localhost no Windows 10/11.

## Requisitos

Para rodar o projeto em localhost você precisa:

- XAMPP (Apache + MySQL)
- MySQL WorkBench 8.0 CE
- Qualquer navegador de internet com suporte pra rodar sites em PHP (Chrome, Opera, Firefox, etc)

## Download do projeto

- Baixe e extraia o arquivo ZIP dentro de C:\xampp\htdocs\

## Criando o banco de dados

- Abra o MySQL WorkBench e crie uma conexão
- Caso você tenha um usuário diferente de root ou uma senha diferente, basta ir no arquivo /config/database_config.php dentro do projeto e alterar o usuário e a senha do banco
- Abra o arquivo dentro do projeto /banco_de_dados/backup/criacao_banco.sql no MySQL WorkBench
- Execute o Script clicando no botão do raio
- Atualize os Schemas para ver se o banco foi ou não criado

## Hospedando com o XAMPP

- Abra o XAMPP Control Panel
- Inicie o Apache e o MySQL
- OBS.: Caso queira visualizar o banco pelo navegador, clique em Admin na linha do MySQL para abrir o PhpMyAdmin.

## Acessando o site

- Com os serviços do XAMPP ligados, no navegador digite na URL http://localhost/Projeto-Residencia-Digital/index.php.
- OBS.: ajuste o caminho caso o seu diretório tenha outro nome.

### Logins de teste

- Professor
Login: admin
Senha: 1234

- Aluno
Código: aluno

## Pronto!

- Seguindo o manual corretamente, o site estará funcionando!
