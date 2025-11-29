-- MySQL dump 10.13  Distrib 8.0.34, for Win64 (x86_64)
--
-- Host: 127.0.0.1    Database: projeto_residencia
-- ------------------------------------------------------
-- Server version	5.5.5-10.4.32-MariaDB

/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!50503 SET NAMES utf8 */;
/*!40103 SET @OLD_TIME_ZONE=@@TIME_ZONE */;
/*!40103 SET TIME_ZONE='+00:00' */;
/*!40014 SET @OLD_UNIQUE_CHECKS=@@UNIQUE_CHECKS, UNIQUE_CHECKS=0 */;
/*!40014 SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0 */;
/*!40101 SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='NO_AUTO_VALUE_ON_ZERO' */;
/*!40111 SET @OLD_SQL_NOTES=@@SQL_NOTES, SQL_NOTES=0 */;

--
-- Table structure for table `aluno`
--

DROP TABLE IF EXISTS `aluno`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `aluno` (
  `idAluno` int(11) NOT NULL AUTO_INCREMENT,
  `codigo_acesso` varchar(45) NOT NULL,
  `nome` varchar(45) NOT NULL,
  `idade` int(11) NOT NULL,
  `email` varchar(45) DEFAULT NULL,
  `cpf` varchar(11) NOT NULL,
  `escolaridade` varchar(250) NOT NULL,
  `endereco` varchar(250) NOT NULL,
  `telefone` varchar(11) NOT NULL,
  `tell_responsavel` varchar(11) DEFAULT NULL,
  `nome_responsavel` varchar(45) DEFAULT NULL,
  `turma` varchar(45) NOT NULL,
  `escola` varchar(75) NOT NULL,
  `data_cadastro` date NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`idAluno`),
  UNIQUE KEY `login_UNIQUE` (`codigo_acesso`)
) ENGINE=InnoDB AUTO_INCREMENT=13 DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `aluno`
--

LOCK TABLES `aluno` WRITE;
/*!40000 ALTER TABLE `aluno` DISABLE KEYS */;
INSERT INTO `aluno` VALUES (1,'im afraid','Arthur Morgan',12,'ronaldinhosoccer@gmail.com','2147483647','','','0',NULL,NULL,'','','2025-09-25'),(2,'coringuei','teste2',12,'ronaldinhosoccer@gmail.com','2147483647','','','0',NULL,NULL,'','','2025-09-25'),(3,'coringuei2','teste3',12,'ronaldinhosoccer@gmail.com','2147483647','','','0',NULL,NULL,'','','2025-09-25'),(4,'aluno','aluno',12,'ronaldinhosoccer@gmail.com','2147483647','','Brasília edit','11999999999','99111111111','Antonio','7','Dom Bosco','2025-09-26'),(5,'S36JU7TQ','teste5',20,'fodase_vose@gmail.com','2147483647','1º ano EF','xique xique bahia','0',NULL,NULL,'5','colegio buxamove','2025-10-01'),(6,'P75GVATT','Lucas',20,'aasudhiauhd@awd','2147483647','2º ano EF','xique xique bahia','0',NULL,NULL,'5','colegio buxamove','2025-10-03'),(7,'QAJPVPMJ','Breno',12,'sdoijads@aokdj','2147483647','3º ano EF','xique xique bahia','0','0','Arthur','5','colegio buxamove','2025-10-03'),(8,'8KZIFO55','Noel',18,'noelnoel@gmail.com','2147483647','9º ano EF','xique xique bahia2','0',NULL,NULL,'5','militar','2025-10-31'),(9,'MAJDZFRG','Noel2',18,'hellyeah@gmail.com','2147483647','4º ano EF','xique xique bahia2','0',NULL,NULL,'5','militar','2025-10-31'),(10,'D0VAT5YP','Batista',10,'batista@gmail.com','2147483647','4º ano EF','brasília','0','0','Maria','5','militar','2025-11-07'),(11,'V81NB06P','Jão',15,'mestre@gmail.com','12345678910','2º ano EM','0','11111111111','2147483647','herobrine','5','dom pedro','2025-11-14'),(12,'519G14RA','art',12,'mestre2@gmail.com','98765432100','1º ano EM','av pitangueiras','(99) 99999-',NULL,'herobrine','5','colegio buxamove','2025-11-14');
/*!40000 ALTER TABLE `aluno` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `aluno_provas`
--

DROP TABLE IF EXISTS `aluno_provas`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `aluno_provas` (
  `idRegistro_prova` int(11) NOT NULL AUTO_INCREMENT,
  `Aluno_idAluno` int(11) NOT NULL,
  `Provas_idProvas` int(11) NOT NULL,
  `nota` decimal(5,2) NOT NULL,
  `data_realizacao` date NOT NULL DEFAULT current_timestamp(),
  `status` enum('pendente','realizada','corrigida') NOT NULL DEFAULT 'pendente',
  `observacoes` varchar(250) NOT NULL,
  `respostas` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NOT NULL CHECK (json_valid(`respostas`)),
  PRIMARY KEY (`idRegistro_prova`,`Aluno_idAluno`,`Provas_idProvas`),
  KEY `fk_Aluno_has_Provas_Provas1_idx` (`Provas_idProvas`),
  KEY `fk_Aluno_has_Provas_Aluno1_idx` (`Aluno_idAluno`),
  CONSTRAINT `fk_Aluno_has_Provas_Aluno1` FOREIGN KEY (`Aluno_idAluno`) REFERENCES `aluno` (`idAluno`) ON DELETE NO ACTION ON UPDATE NO ACTION,
  CONSTRAINT `fk_Aluno_has_Provas_Provas1` FOREIGN KEY (`Provas_idProvas`) REFERENCES `provas` (`idProvas`) ON DELETE NO ACTION ON UPDATE NO ACTION
) ENGINE=InnoDB AUTO_INCREMENT=27 DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `aluno_provas`
--

LOCK TABLES `aluno_provas` WRITE;
/*!40000 ALTER TABLE `aluno_provas` DISABLE KEYS */;
INSERT INTO `aluno_provas` VALUES (1,5,1,10.00,'2025-10-02','realizada','Prova realizada com sucesso','[\"D\"]'),(2,7,1,10.00,'2025-10-03','realizada','Prova realizada com sucesso','[\"D\"]'),(3,4,6,10.00,'2025-10-31','realizada','Prova realizada com sucesso','[\"A\"]'),(4,1,5,0.00,'2025-10-31','realizada','Prova realizada com sucesso','[\"B\"]'),(5,1,6,10.00,'2025-10-31','realizada','Prova realizada com sucesso','[\"A\"]'),(6,1,1,10.00,'2025-10-31','realizada','Prova realizada com sucesso','[\"D\"]'),(7,1,3,5.00,'2025-10-31','realizada','Prova realizada com sucesso','[\"C\",\"A\"]'),(8,2,5,10.00,'2025-10-31','realizada','Prova realizada com sucesso','[\"A\"]'),(9,2,6,0.00,'2025-10-31','realizada','Prova realizada com sucesso','[\"C\"]'),(10,2,1,0.00,'2025-10-31','realizada','Prova realizada com sucesso','[\"A\"]'),(11,2,3,5.00,'2025-10-31','realizada','Prova realizada com sucesso','[\"B\",\"C\"]'),(12,3,5,0.00,'2025-10-31','realizada','Prova realizada com sucesso','[\"B\"]'),(13,3,6,10.00,'2025-10-31','realizada','Prova realizada com sucesso','[\"A\"]'),(14,3,1,10.00,'2025-10-31','realizada','Prova realizada com sucesso','[\"D\"]'),(15,3,3,10.00,'2025-10-31','realizada','Prova realizada com sucesso','[\"B\",\"A\"]'),(16,5,6,10.00,'2025-10-31','realizada','Prova realizada com sucesso','[\"A\"]'),(17,4,8,0.00,'2025-10-31','realizada','Prova realizada com sucesso','[\"C\"]'),(18,4,7,10.00,'2025-10-31','realizada','Prova realizada com sucesso','[\"A\"]'),(19,4,5,10.00,'2025-10-31','realizada','Prova realizada com sucesso','[\"A\"]'),(20,4,1,10.00,'2025-10-31','realizada','Prova realizada com sucesso','[\"D\"]'),(21,4,3,0.00,'2025-10-31','realizada','Prova realizada com sucesso','[\"D\",\"C\"]'),(22,4,10,10.00,'2025-10-31','realizada','Prova realizada com sucesso','[\"A\"]'),(23,4,9,10.00,'2025-10-31','realizada','Prova realizada com sucesso','[\"A\"]'),(24,10,9,10.00,'2025-11-07','realizada','Prova realizada com sucesso','[\"A\"]'),(25,10,6,0.00,'2025-11-07','realizada','Prova realizada com sucesso','[\"B\"]'),(26,10,3,5.00,'2025-11-07','realizada','Prova realizada com sucesso','[\"B\",\"B\"]');
/*!40000 ALTER TABLE `aluno_provas` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `imagensprovas`
--

DROP TABLE IF EXISTS `imagensprovas`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `imagensprovas` (
  `idImagem` int(11) NOT NULL AUTO_INCREMENT,
  `idProva` int(11) NOT NULL,
  `numero_questao` int(11) NOT NULL,
  `caminho_imagem` varchar(255) NOT NULL,
  `nome_arquivo` varchar(100) NOT NULL,
  `data_upload` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`idImagem`),
  KEY `idProva` (`idProva`),
  CONSTRAINT `imagensprovas_ibfk_1` FOREIGN KEY (`idProva`) REFERENCES `provas` (`idProvas`)
) ENGINE=InnoDB AUTO_INCREMENT=10 DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `imagensprovas`
--

LOCK TABLES `imagensprovas` WRITE;
/*!40000 ALTER TABLE `imagensprovas` DISABLE KEYS */;
INSERT INTO `imagensprovas` VALUES (1,6,1,'../uploads/provas/prova_6/69041145a1ced_questao_1.jfif','all-might-pose.jfif','2025-10-31 01:30:45'),(2,7,1,'../uploads/provas/prova_7/69043af6a43af_questao_1.jfif','all-might-pose.jfif','2025-10-31 04:28:38'),(3,11,1,'../uploads/provas/prova_11/690e04562fa88_questao_1.jpg','1080p-pictures-mrnxkorqrlmhayie.jpg','2025-11-07 14:38:14'),(4,15,1,'uploads/provas/prova_15/691781a3ba469_questao_1.webp','e43b602a-d5b1-40b8-a0b7-fcae39e1e060.webp','2025-11-14 19:23:15'),(5,16,1,'uploads/provas/prova_16/6917830bd3efc_questao_1.png','images.png','2025-11-14 19:29:15'),(6,14,1,'uploads/provas/prova_14/691ba023c0691_questao_1.jpg','mash.jpg','2025-11-17 22:22:27'),(7,15,1,'uploads/provas/prova_15/691ba657390bd_questao_1.jpg','mash.jpg','2025-11-17 22:48:55'),(8,34,1,'uploads/provas/prova_34/691bb663906cb_questao_1.jpg','mash.jpg','2025-11-17 23:57:23'),(9,35,1,'uploads/provas/prova_35/691bd10f0d0c4_questao_1.jpg','mash.jpg','2025-11-18 01:51:11');
/*!40000 ALTER TABLE `imagensprovas` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `professor`
--

DROP TABLE IF EXISTS `professor`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `professor` (
  `idProfessor` int(11) NOT NULL AUTO_INCREMENT,
  `login` varchar(45) NOT NULL,
  `senha` varchar(45) NOT NULL,
  `nome` varchar(45) NOT NULL,
  `email` varchar(45) NOT NULL,
  `cpf` varchar(45) NOT NULL,
  `data_cadastro` date NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`idProfessor`),
  UNIQUE KEY `login_UNIQUE` (`login`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `professor`
--

LOCK TABLES `professor` WRITE;
/*!40000 ALTER TABLE `professor` DISABLE KEYS */;
INSERT INTO `professor` VALUES (1,'admin','1234','Professor Admin','professor@escola.com','12345678910','2025-09-25');
/*!40000 ALTER TABLE `professor` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `provas`
--

DROP TABLE IF EXISTS `provas`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `provas` (
  `idProvas` int(11) NOT NULL AUTO_INCREMENT,
  `titulo` varchar(45) DEFAULT NULL,
  `materia` varchar(45) NOT NULL,
  `numero_questoes` int(11) NOT NULL,
  `conteudo` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NOT NULL CHECK (json_valid(`conteudo`)),
  `serie_destinada` varchar(45) NOT NULL,
  `data_criacao` varchar(45) NOT NULL DEFAULT 'CURRENT_TIMESTAMP()',
  `Professor_idProfessor` int(11) NOT NULL,
  `ativa` tinyint(1) NOT NULL DEFAULT 1,
  PRIMARY KEY (`idProvas`),
  KEY `fk_Provas_Professor_idx` (`Professor_idProfessor`),
  CONSTRAINT `fk_Provas_Professor` FOREIGN KEY (`Professor_idProfessor`) REFERENCES `professor` (`idProfessor`) ON DELETE NO ACTION ON UPDATE NO ACTION
) ENGINE=InnoDB AUTO_INCREMENT=38 DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `provas`
--

LOCK TABLES `provas` WRITE;
/*!40000 ALTER TABLE `provas` DISABLE KEYS */;
INSERT INTO `provas` VALUES (1,'teste','Matemática',1,'[{\"enunciado\":\"É só um teste, né?\",\"alternativas\":{\"A\":\"sim?\",\"B\":\"não?\",\"C\":\"talvez?\",\"D\":\"ROCK AND ROLL\"},\"resposta_correta\":\"D\"}]','1º ano','2025-09-25',1,0),(3,'teste3','Matemática',2,'[{\"enunciado\":\"(Enem 2022) Uma cozinheira produz docinhos especiais por encomenda. Usando uma receita-base de massa, ela prepara uma porção, com a qual produz 50 docinhos maciços de formato esférico, com 2 cm de diâmetro. Um cliente encomenda 150 desses docinhos, mas pede que cada um tenha formato esférico com 4 cm de diâmetro. A cozinheira pretende preparar o número exato de porções da receita-base de massa necessário para produzir os docinhos dessa encomenda.\\r\\n\\r\\nQuantas porções da receita-base de massa ela deve preparar para atender esse cliente?\",\"alternativas\":{\"A\":\"43\",\"B\":\"24\",\"C\":\"12\",\"D\":\"4\"},\"resposta_correta\":\"B\"},{\"enunciado\":\"1+1=?\",\"alternativas\":{\"A\":\"2\",\"B\":\"23\",\"C\":\"4\",\"D\":\"12\"},\"resposta_correta\":\"A\"}]','1º ano','2025-09-25',1,0),(5,'teste imagem','Matemática',1,'[{\"enunciado\":\"a\",\"alternativas\":{\"A\":\"a\",\"B\":\"a\",\"C\":\"a\",\"D\":\"a\"},\"resposta_correta\":\"A\"}]','1º ano','2025-10-30',1,0),(6,'teste imagem2','Matemática',1,'[{\"enunciado\":\"a\",\"alternativas\":{\"A\":\"a\",\"B\":\"a\",\"C\":\"a\",\"D\":\"a\"},\"resposta_correta\":\"A\"}]','1º ano','2025-10-30',1,0),(7,'apresentação','Português',1,'[{\"enunciado\":\"teste\",\"alternativas\":{\"A\":\"a\",\"B\":\"a\",\"C\":\"a\",\"D\":\"a\"},\"resposta_correta\":\"A\"}]','1º ano','2025-10-31',1,0),(8,'Teste Caracteres','Matemática',1,'[{\"enunciado\":\"±∓β÷δ∨≈≠√∛∜ℚℝℤ∀∵∴∝≡≅\",\"alternativas\":{\"A\":\"±∓β÷δ∨≈≠√∛∜ℚℝℤ∀∵∴∝≡≅\",\"B\":\"±∓β÷δ∨≈≠√∛∜ℚℝℤ∀∵∴∝≡≅\",\"C\":\"±∓β÷δ∨≈≠√∛∜ℚℝℤ∀∵∴∝≡≅\",\"D\":\"±∓β÷δ∨≈≠√∛∜ℚℝℤ∀∵∴∝≡≅\"},\"resposta_correta\":\"A\"}]','1º ano','2025-10-31',1,0),(9,'teste fração','Matemática',1,'[{\"enunciado\":\"$x^2+\\\\rac{5}{78}=9$\\r\\n\\r\\n$E = mc^2$\\r\\n$a^2 + b^2 = c^2$\\r\\n$x = \\\\frac{-b \\\\pm \\\\sqrt{b^2 - 4ac}}{2a}$\\r\\n$f(x) = x^2 + 2x + 1$\\r\\n$\\\\pi \\\\approx 3.14159$\",\"alternativas\":{\"A\":\"a\",\"B\":\"a\",\"C\":\"a\",\"D\":\"a\"},\"resposta_correta\":\"A\"}]','1º ano','2025-10-31',1,0),(10,'teste fração 2','Matemática',1,'[{\"enunciado\":\"$x^2 + \\\\frac{5}{78} = 9$\",\"alternativas\":{\"A\":\"a\",\"B\":\"a\",\"C\":\"a\",\"D\":\"a\"},\"resposta_correta\":\"A\"}]','1º ano','2025-10-31',1,0),(11,'Prova','Português',3,'[{\"enunciado\":\"qualquer coisa, só que editado\",\"alternativas\":{\"A\":\"a\",\"B\":\"a\",\"C\":\"a\",\"D\":\"a\"},\"resposta_correta\":\"C\"},{\"enunciado\":\"$x^2 + \\\\frac{5}{78} = 9$\\r\\n$$x^2 + \\\\frac{5}{78} = 9$$\\r\\n$$x^2 + \\\\frac{5}{78} = 9$$\\r\\n$x^2 + \\\\frac{x^2+2}{\\\\sqrt{y+2}} = 9$\\r\\nAjeitado2?\",\"alternativas\":{\"A\":\"$x^2 + frac{5}{78} = 9$\",\"B\":\"a\",\"C\":\"a\",\"D\":\"a\"},\"resposta_correta\":\"A\"},{\"enunciado\":\"*Algo assim*\\r\\n**Algo assim**\\r\\n_sublinhado_\\r\\ntextbf{alguma coisa}\\r\\ntextit{alguma coisa}\\r\\nAjeitado?\",\"alternativas\":{\"A\":\"textbf{alguma coisa}\",\"B\":\"textit{alguma coisa}\",\"C\":\"a\",\"D\":\"a\"},\"resposta_correta\":\"A\"}]','3º ano','2025-11-07',1,0),(12,'prova2','Matemática',1,'[{\"enunciado\":\"$\\\\textbf{alguma coisa}$\\r\\n$\\\\textit{alguma coisa}$\",\"alternativas\":{\"A\":\"$\\\\textbf{alguma coisa}$\",\"B\":\"$\\\\textit{alguma coisa}$\",\"C\":\"a\",\"D\":\"a\"},\"resposta_correta\":\"A\"}]','1º ano','2025-11-07',1,0),(14,'Prova 3','Matemática',1,'[{\"enunciado\":\"wsedfczsedf edit\",\"alternativas\":{\"A\":\"ewd\",\"B\":\"awsd\",\"C\":\"wads\",\"D\":\"wasd\"},\"resposta_correta\":\"A\"}]','1º ano','2025-11-14',1,1),(15,'teste234','Matemática',1,'[{\"enunciado\":\"sdfsd teste\",\"alternativas\":{\"A\":\"a\",\"B\":\"a\",\"C\":\"a\",\"D\":\"a\"},\"resposta_correta\":\"A\"}]','1º ano','2025-11-14',1,0),(16,'Prova teste','Matemática',1,'[{\"enunciado\":\"dwdse\",\"alternativas\":{\"A\":\"awda\",\"B\":\"awda\",\"C\":\"awsd\",\"D\":\"awd\"},\"resposta_correta\":\"A\"}]','2º ano','2025-11-14',1,0),(34,'debug 3','Matemática',1,'[{\"enunciado\":\"awed\",\"alternativas\":{\"A\":\"a\",\"B\":\"a\",\"C\":\"a\",\"D\":\"a\"},\"resposta_correta\":\"A\"}]','1º ano','2025-11-17',1,0),(35,'teste12313123','Matemática',1,'[{\"enunciado\":\"awda\",\"alternativas\":{\"A\":\"a\",\"B\":\"a\",\"C\":\"a\",\"D\":\"a\"},\"resposta_correta\":\"A\"}]','1º ano','2025-11-17',1,0),(36,'teste sla','Matemática',1,'[{\"enunciado\":\"adwae\",\"alternativas\":{\"A\":\"a\",\"B\":\"a\",\"C\":\"a\",\"D\":\"a\"},\"resposta_correta\":\"A\"}]','1º ano','2025-11-18 04:25:59',1,0),(37,'teste desativada','Matemática',1,'[{\"enunciado\":\"a\",\"alternativas\":{\"A\":\"a\",\"B\":\"a\",\"C\":\"a\",\"D\":\"a\"},\"resposta_correta\":\"A\"}]','1º ano','2025-11-21 00:38:53',1,0);
/*!40000 ALTER TABLE `provas` ENABLE KEYS */;
UNLOCK TABLES;
/*!40103 SET TIME_ZONE=@OLD_TIME_ZONE */;

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;

-- Dump completed on 2025-11-28 19:43:31
