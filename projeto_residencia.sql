-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Tempo de geração: 03/10/2025 às 20:52
-- Versão do servidor: 10.4.32-MariaDB
-- Versão do PHP: 8.0.30

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Banco de dados: `projeto_residencia`
--

-- --------------------------------------------------------

--
-- Estrutura para tabela `aluno`
--

CREATE TABLE `aluno` (
  `idAluno` int(11) NOT NULL,
  `codigo_acesso` varchar(45) NOT NULL,
  `nome` varchar(45) NOT NULL,
  `idade` int(11) NOT NULL,
  `email` varchar(45) DEFAULT NULL,
  `cpf` int(11) NOT NULL,
  `escolaridade` varchar(250) NOT NULL,
  `endereco` varchar(250) NOT NULL,
  `telefone` int(11) NOT NULL,
  `tell_responsavel` int(11) DEFAULT NULL,
  `nome_responsavel` varchar(45) DEFAULT NULL,
  `turma` varchar(45) NOT NULL,
  `escola` varchar(75) NOT NULL,
  `data_cadastro` date NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;

--
-- Despejando dados para a tabela `aluno`
--

INSERT INTO `aluno` (`idAluno`, `codigo_acesso`, `nome`, `idade`, `email`, `cpf`, `escolaridade`, `endereco`, `telefone`, `tell_responsavel`, `nome_responsavel`, `turma`, `escola`, `data_cadastro`) VALUES
(1, 'im afraid', 'Arthur Morgan', 12, 'ronaldinhosoccer@gmail.com', 2147483647, '', '', 0, NULL, NULL, '', '', '2025-09-25'),
(2, 'coringuei', 'teste2', 12, 'ronaldinhosoccer@gmail.com', 2147483647, '', '', 0, NULL, NULL, '', '', '2025-09-25'),
(3, 'coringuei2', 'teste3', 12, 'ronaldinhosoccer@gmail.com', 2147483647, '', '', 0, NULL, NULL, '', '', '2025-09-25'),
(4, 'aluno', 'aluno', 12, 'ronaldinhosoccer@gmail.com', 2147483647, '', '', 0, NULL, NULL, '', '', '2025-09-26'),
(5, 'S36JU7TQ', 'teste5', 20, 'fodase_vose@gmail.com', 2147483647, '1º ano EF', 'xique xique bahia', 0, NULL, NULL, '5', 'colegio buxamove', '2025-10-01'),
(6, 'P75GVATT', 'Lucas', 20, 'aasudhiauhd@awd', 2147483647, '2º ano EF', 'xique xique bahia', 0, NULL, NULL, '5', 'colegio buxamove', '2025-10-03'),
(7, 'QAJPVPMJ', 'Breno', 12, 'sdoijads@aokdj', 2147483647, '3º ano EF', 'xique xique bahia', 0, 0, 'Arthur', '5', 'colegio buxamove', '2025-10-03');

-- --------------------------------------------------------

--
-- Estrutura para tabela `aluno_provas`
--

CREATE TABLE `aluno_provas` (
  `idRegistro_prova` int(11) NOT NULL,
  `Aluno_idAluno` int(11) NOT NULL,
  `Provas_idProvas` int(11) NOT NULL,
  `nota` decimal(5,2) NOT NULL,
  `data_realizacao` date NOT NULL DEFAULT current_timestamp(),
  `status` enum('pendente','realizada','corrigida') NOT NULL DEFAULT 'pendente',
  `observacoes` varchar(250) NOT NULL,
  `respostas` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NOT NULL CHECK (json_valid(`respostas`))
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;

--
-- Despejando dados para a tabela `aluno_provas`
--

INSERT INTO `aluno_provas` (`idRegistro_prova`, `Aluno_idAluno`, `Provas_idProvas`, `nota`, `data_realizacao`, `status`, `observacoes`, `respostas`) VALUES
(1, 5, 1, 10.00, '2025-10-02', 'realizada', 'Prova realizada com sucesso', '[\"D\"]'),
(2, 7, 1, 10.00, '2025-10-03', 'realizada', 'Prova realizada com sucesso', '[\"D\"]');

-- --------------------------------------------------------

--
-- Estrutura para tabela `professor`
--

CREATE TABLE `professor` (
  `idProfessor` int(11) NOT NULL,
  `login` varchar(45) NOT NULL,
  `senha` varchar(45) NOT NULL,
  `nome` varchar(45) NOT NULL,
  `email` varchar(45) NOT NULL,
  `cpf` varchar(45) NOT NULL,
  `data_cadastro` date NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;

--
-- Despejando dados para a tabela `professor`
--

INSERT INTO `professor` (`idProfessor`, `login`, `senha`, `nome`, `email`, `cpf`, `data_cadastro`) VALUES
(1, 'admin', '1234', 'Professor Admin', 'professor@escola.com', '12345678910', '2025-09-25');

-- --------------------------------------------------------

--
-- Estrutura para tabela `provas`
--

CREATE TABLE `provas` (
  `idProvas` int(11) NOT NULL,
  `titulo` varchar(45) DEFAULT NULL,
  `materia` varchar(45) NOT NULL,
  `numero_questoes` int(11) NOT NULL,
  `conteudo` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NOT NULL CHECK (json_valid(`conteudo`)),
  `serie_destinada` varchar(45) NOT NULL,
  `data_criacao` varchar(45) NOT NULL DEFAULT 'CURRENT_TIMESTAMP()',
  `Professor_idProfessor` int(11) NOT NULL,
  `ativa` tinyint(1) NOT NULL DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;

--
-- Despejando dados para a tabela `provas`
--

INSERT INTO `provas` (`idProvas`, `titulo`, `materia`, `numero_questoes`, `conteudo`, `serie_destinada`, `data_criacao`, `Professor_idProfessor`, `ativa`) VALUES
(1, 'teste', 'Matemática', 1, '[{\"enunciado\":\"É só um teste, né?\",\"alternativas\":{\"A\":\"sim?\",\"B\":\"não?\",\"C\":\"talvez?\",\"D\":\"ROCK AND ROLL\"},\"resposta_correta\":\"D\"}]', '1º ano', '2025-09-25', 1, 1),
(3, 'teste3', 'Matemática', 2, '[{\"enunciado\":\"(Enem 2022) Uma cozinheira produz docinhos especiais por encomenda. Usando uma receita-base de massa, ela prepara uma porção, com a qual produz 50 docinhos maciços de formato esférico, com 2 cm de diâmetro. Um cliente encomenda 150 desses docinhos, mas pede que cada um tenha formato esférico com 4 cm de diâmetro. A cozinheira pretende preparar o número exato de porções da receita-base de massa necessário para produzir os docinhos dessa encomenda.\\r\\n\\r\\nQuantas porções da receita-base de massa ela deve preparar para atender esse cliente?\",\"alternativas\":{\"A\":\"43\",\"B\":\"24\",\"C\":\"12\",\"D\":\"4\"},\"resposta_correta\":\"B\"},{\"enunciado\":\"1+1=?\",\"alternativas\":{\"A\":\"2\",\"B\":\"23\",\"C\":\"4\",\"D\":\"12\"},\"resposta_correta\":\"A\"}]', '1º ano', '2025-09-25', 1, 1);

--
-- Índices para tabelas despejadas
--

--
-- Índices de tabela `aluno`
--
ALTER TABLE `aluno`
  ADD PRIMARY KEY (`idAluno`),
  ADD UNIQUE KEY `login_UNIQUE` (`codigo_acesso`);

--
-- Índices de tabela `aluno_provas`
--
ALTER TABLE `aluno_provas`
  ADD PRIMARY KEY (`idRegistro_prova`,`Aluno_idAluno`,`Provas_idProvas`),
  ADD KEY `fk_Aluno_has_Provas_Provas1_idx` (`Provas_idProvas`),
  ADD KEY `fk_Aluno_has_Provas_Aluno1_idx` (`Aluno_idAluno`);

--
-- Índices de tabela `professor`
--
ALTER TABLE `professor`
  ADD PRIMARY KEY (`idProfessor`),
  ADD UNIQUE KEY `login_UNIQUE` (`login`);

--
-- Índices de tabela `provas`
--
ALTER TABLE `provas`
  ADD PRIMARY KEY (`idProvas`),
  ADD KEY `fk_Provas_Professor_idx` (`Professor_idProfessor`);

--
-- AUTO_INCREMENT para tabelas despejadas
--

--
-- AUTO_INCREMENT de tabela `aluno`
--
ALTER TABLE `aluno`
  MODIFY `idAluno` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT de tabela `aluno_provas`
--
ALTER TABLE `aluno_provas`
  MODIFY `idRegistro_prova` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT de tabela `professor`
--
ALTER TABLE `professor`
  MODIFY `idProfessor` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT de tabela `provas`
--
ALTER TABLE `provas`
  MODIFY `idProvas` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- Restrições para tabelas despejadas
--

--
-- Restrições para tabelas `aluno_provas`
--
ALTER TABLE `aluno_provas`
  ADD CONSTRAINT `fk_Aluno_has_Provas_Aluno1` FOREIGN KEY (`Aluno_idAluno`) REFERENCES `aluno` (`idAluno`) ON DELETE NO ACTION ON UPDATE NO ACTION,
  ADD CONSTRAINT `fk_Aluno_has_Provas_Provas1` FOREIGN KEY (`Provas_idProvas`) REFERENCES `provas` (`idProvas`) ON DELETE NO ACTION ON UPDATE NO ACTION;

--
-- Restrições para tabelas `provas`
--
ALTER TABLE `provas`
  ADD CONSTRAINT `fk_Provas_Professor` FOREIGN KEY (`Professor_idProfessor`) REFERENCES `professor` (`idProfessor`) ON DELETE NO ACTION ON UPDATE NO ACTION;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
