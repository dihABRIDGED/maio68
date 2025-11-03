-- phpMyAdmin SQL Dump
-- version 4.9.2
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1:3306
-- Tempo de geração: 24-Out-2025 às 12:59
-- Versão do servidor: 10.4.10-MariaDB
-- versão do PHP: 7.3.12

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
SET AUTOCOMMIT = 0;
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Banco de dados: `fluxusdb`
--
CREATE DATABASE IF NOT EXISTS `fluxusdb` DEFAULT CHARACTER SET latin1 COLLATE latin1_swedish_ci;
USE `fluxusdb`;

-- --------------------------------------------------------

--
-- Estrutura da tabela `atividade`
--

DROP TABLE IF EXISTS `atividade`;
CREATE TABLE IF NOT EXISTS `atividade` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `disciplina_id` int(11) NOT NULL,
  `titulo` varchar(200) NOT NULL,
  `descricao` text DEFAULT NULL,
  `data_atividade` date NOT NULL,
  `tipo` varchar(50) NOT NULL DEFAULT 'atividade',
  `criado_por` int(11) NOT NULL,
  `criado_em` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `criado_por` (`criado_por`),
  KEY `disciplina_id` (`disciplina_id`)
) ENGINE=MyISAM AUTO_INCREMENT=15 DEFAULT CHARSET=latin1;

--
-- Extraindo dados da tabela `atividade`
--

INSERT INTO `atividade` (`id`, `disciplina_id`, `titulo`, `descricao`, `data_atividade`, `tipo`, `criado_por`, `criado_em`) VALUES
(8, 1, 'Redação sobre Machado de Assis', 'Escrever uma redação de 30 linhas sobre a obra Dom Casmurro', '2024-12-15', 'redacao', 3, '2025-10-16 18:39:04'),
(9, 1, 'Prova de Literatura', 'Avaliação sobre Romantismo e Realismo', '2024-12-18', 'prova', 3, '2025-10-16 18:39:04'),
(10, 2, 'Lista de Exercícios - Equações', 'Resolver exercícios do capítulo 5', '2024-12-12', 'exercicio', 2, '2025-10-16 18:39:04'),
(11, 2, 'Prova de Matemática', 'Avaliação sobre funções e geometria', '2024-12-20', 'prova', 2, '2025-10-16 18:39:04'),
(12, 3, 'Trabalho sobre Brasil Colonial', 'Pesquisa em grupo sobre período colonial', '2024-12-16', 'trabalho', 3, '2025-10-16 18:39:04'),
(13, 4, 'Mapa do Brasil', 'Desenhar mapa com relevos e rios', '2024-12-14', 'atividade', 2, '2025-10-16 18:39:04'),
(14, 5, 'Relatório de Experimento', 'Relatório sobre observação de células', '2024-12-17', 'relatorio', 3, '2025-10-16 18:39:04');

-- --------------------------------------------------------

--
-- Estrutura da tabela `aula`
--

DROP TABLE IF EXISTS `aula`;
CREATE TABLE IF NOT EXISTS `aula` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `disciplina_id` int(11) NOT NULL,
  `professor_id` int(11) NOT NULL,
  `data` date NOT NULL,
  `horario` time NOT NULL,
  `conteudo` text DEFAULT NULL,
  `criado_por` int(11) NOT NULL,
  PRIMARY KEY (`id`),
  KEY `disciplina_id` (`disciplina_id`),
  KEY `professor_id` (`professor_id`),
  KEY `criado_por` (`criado_por`)
) ENGINE=MyISAM AUTO_INCREMENT=26 DEFAULT CHARSET=latin1;

--
-- Extraindo dados da tabela `aula`
--

INSERT INTO `aula` (`id`, `disciplina_id`, `professor_id`, `data`, `horario`, `conteudo`, `criado_por`) VALUES
(1, 1, 3, '2024-12-01', '08:00:00', 'Introdução à Literatura Brasileira', 3),
(2, 1, 3, '2024-12-03', '08:00:00', 'Análise de Texto - Machado de Assis', 3),
(3, 1, 3, '2024-12-05', '08:00:00', 'Gramática: Classes de Palavras', 3),
(4, 1, 3, '2024-12-08', '08:00:00', 'Redação: Texto Dissertativo', 3),
(5, 1, 3, '2024-12-10', '08:00:00', 'Literatura: Romantismo', 3),
(6, 2, 2, '2024-12-02', '10:00:00', 'Equações do 1º Grau', 2),
(7, 2, 2, '2024-12-04', '10:00:00', 'Equações do 2º Grau', 2),
(8, 2, 2, '2024-12-06', '10:00:00', 'Funções Lineares', 2),
(9, 2, 2, '2024-12-09', '10:00:00', 'Geometria Plana', 2),
(10, 2, 2, '2024-12-11', '10:00:00', 'Trigonometria Básica', 2),
(11, 3, 3, '2024-12-02', '14:00:00', 'Brasil Colonial', 3),
(12, 3, 3, '2024-12-04', '14:00:00', 'Independência do Brasil', 3),
(13, 3, 3, '2024-12-06', '14:00:00', 'República Velha', 3),
(14, 3, 3, '2024-12-09', '14:00:00', 'Era Vargas', 3),
(15, 3, 3, '2024-12-11', '14:00:00', 'Ditadura Militar', 3),
(16, 4, 2, '2024-12-03', '08:00:00', 'Relevo Brasileiro', 2),
(17, 4, 2, '2024-12-05', '08:00:00', 'Hidrografia', 2),
(18, 4, 2, '2024-12-10', '08:00:00', 'Clima e Vegetação', 2),
(19, 5, 3, '2024-12-01', '10:00:00', 'Sistema Solar', 3),
(20, 5, 3, '2024-12-03', '10:00:00', 'Células e Tecidos', 3),
(21, 5, 3, '2024-12-08', '10:00:00', 'Ecossistemas', 3);

-- --------------------------------------------------------

--
-- Estrutura da tabela `contato_suporte`
--

DROP TABLE IF EXISTS `contato_suporte`;
CREATE TABLE IF NOT EXISTS `contato_suporte` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `tipo` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL COMMENT 'Tipo do contato: endereco, email, telefone, social',
  `secao_exibicao` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'institucionais' COMMENT 'Seção onde o contato será exibido: institucionais, professores, sociais',
  `titulo` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL COMMENT 'Título exibido para o usuário',
  `valor` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL COMMENT 'Valor do contato (endereço, email, telefone, URL)',
  `icone` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT 'fas fa-info-circle' COMMENT 'Classe do ícone Font Awesome',
  `cor` varchar(20) COLLATE utf8mb4_unicode_ci DEFAULT '#d32f2f' COMMENT 'Cor do ícone em hexadecimal',
  `ordem` int(11) DEFAULT 0 COMMENT 'Ordem de exibição (menor número aparece primeiro)',
  `ativo` tinyint(1) DEFAULT 1 COMMENT '1 = ativo, 0 = inativo',
  `criado_em` timestamp NOT NULL DEFAULT current_timestamp(),
  `atualizado_em` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `tipo_unico` (`tipo`)
) ENGINE=InnoDB AUTO_INCREMENT=14 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Extraindo dados da tabela `contato_suporte`
--

INSERT INTO `contato_suporte` (`id`, `tipo`, `secao_exibicao`, `titulo`, `valor`, `icone`, `cor`, `ordem`, `ativo`, `criado_em`, `atualizado_em`) VALUES
(1, 'endereco_escola', 'institucionais', 'Endereço da Escola', 'Rua Paulo Frontin, 365, Mogi das Cruzes 08710-050, BR', 'fas fa-map-marker-alt', '#d32f2f', 10, 1, '2025-10-24 11:36:14', '2025-10-24 11:36:14'),
(2, 'email_geral', 'institucionais', 'E-mail Geral', 'maio68coletivo@gmail.com', 'fas fa-envelope', '#1976d2', 20, 1, '2025-10-24 11:36:14', '2025-10-24 11:36:14'),
(3, 'telefone_geral', 'institucionais', 'Telefone Geral', '(11) 99999-9999', 'fas fa-phone', '#388e3c', 30, 1, '2025-10-24 11:36:14', '2025-10-24 11:36:14'),
(4, 'instagram_social', 'sociais', 'Instagram', 'https://www.instagram.com/cursinhopopularmaio68/', 'fab fa-instagram', '#e91e63', 40, 1, '2025-10-24 11:36:14', '2025-10-24 11:36:14'),
(5, 'facebook_social', 'sociais', 'Facebook', 'https://www.facebook.com/cursinhopopularmaio68', 'fab fa-facebook', '#3f51b5', 50, 1, '2025-10-24 11:36:14', '2025-10-24 11:36:14'),
(6, 'youtube_social', 'sociais', 'YouTube', 'https://www.youtube.com/@cursinhopopularmaio68', 'fab fa-youtube', '#f44336', 60, 1, '2025-10-24 11:36:14', '2025-10-24 11:36:14'),
(7, 'prof_matematica', 'professores', 'Prof. Matemática', 'matematica@fluxus.edu', 'fas fa-calculator', '#ff9800', 100, 1, '2025-10-24 11:36:14', '2025-10-24 11:36:14'),
(8, 'prof_portugues', 'professores', 'Prof. Português', 'portugues@fluxus.edu', 'fas fa-book-open', '#9c27b0', 110, 1, '2025-10-24 11:36:14', '2025-10-24 11:36:14'),
(9, 'prof_historia', 'professores', 'Prof. História', 'historia@fluxus.edu', 'fas fa-landmark', '#795548', 120, 1, '2025-10-24 11:36:14', '2025-10-24 11:36:14'),
(10, 'coordenacao', 'professores', 'Coordenação Pedagógica', 'coordenacao@fluxus.edu', 'fas fa-user-tie', '#607d8b', 130, 1, '2025-10-24 11:36:14', '2025-10-24 11:36:14'),
(11, 'vvvvvvvvvvvvvvvvvvvvvvvvv', 'professores', 'vvvvvvvvvvvvvvvvvvvvvvvvvvvvvvv', 'vvvvvvvvvvvvvvvvvvvvvvvvvvvvv', 'fab fa-whatsapp', '#e71313', 100, 1, '2025-10-24 11:48:18', '2025-10-24 12:07:45'),
(12, 'iyw5r', 'sociais', '84', '784', 'fab fa-youtube', '#3144d3', 100, 1, '2025-10-24 12:53:02', '2025-10-24 12:53:02'),
(13, 'aaaaaaaaaaaaaaaaaaaaaaaaaaaaa', 'sociais', 'aaaaaaaaaaaaaaaaaaaaaaa', 'aaaaaaaaaaaaaaaaaaaaaaaaaaaa', 'fas fa-chalkboard-teacher', '#fff700', 10, 1, '2025-10-24 12:57:05', '2025-10-24 12:57:05');

-- --------------------------------------------------------

--
-- Estrutura da tabela `cronograma_semanal`
--

DROP TABLE IF EXISTS `cronograma_semanal`;
CREATE TABLE IF NOT EXISTS `cronograma_semanal` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `dia_semana` varchar(20) NOT NULL,
  `horario` time NOT NULL,
  `disciplina_id` int(11) NOT NULL,
  `professor_id` int(11) NOT NULL,
  `conteudo` text DEFAULT NULL,
  `criado_por` int(11) NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `dia_horario` (`dia_semana`,`horario`),
  KEY `disciplina_id` (`disciplina_id`),
  KEY `professor_id` (`professor_id`)
) ENGINE=MyISAM AUTO_INCREMENT=28 DEFAULT CHARSET=latin1;

--
-- Extraindo dados da tabela `cronograma_semanal`
--

INSERT INTO `cronograma_semanal` (`id`, `dia_semana`, `horario`, `disciplina_id`, `professor_id`, `conteudo`, `criado_por`) VALUES
(13, 'segunda', '08:00:00', 1, 2, 'Língua Portuguesa - Gramática', 3),
(14, 'segunda', '10:00:00', 2, 2, 'Matemática - Álgebra', 2),
(15, 'segunda', '14:00:00', 3, 3, 'História - Brasil República', 3),
(16, 'terca', '08:00:00', 4, 2, 'Geografia - Relevo Brasileiro', 2),
(17, 'terca', '10:00:00', 5, 3, 'Ciências - Sistema Solar', 3),
(18, 'terca', '14:00:00', 1, 3, 'Língua Portuguesa - Literatura', 3),
(19, 'quarta', '08:00:00', 2, 2, 'Matemática - Geometria', 2),
(20, 'quarta', '10:00:00', 3, 3, 'História - Era Vargas', 3),
(21, 'quarta', '14:00:00', 4, 2, 'Geografia - Hidrografia', 2),
(22, 'quinta', '08:00:00', 5, 3, 'Ciências - Ecossistemas', 3),
(23, 'quinta', '10:00:00', 1, 3, 'Língua Portuguesa - Redação', 3),
(24, 'quinta', '14:00:00', 2, 2, 'Matemática - Funções', 2),
(25, 'sexta', '08:00:00', 3, 3, 'História - Ditadura Militar', 3),
(26, 'sexta', '10:00:00', 4, 2, 'Geografia - Clima e Vegetação', 2),
(27, 'sexta', '14:00:00', 5, 3, 'Ciências - Células e Tecidos', 3);

-- --------------------------------------------------------

--
-- Estrutura da tabela `disciplina`
--

DROP TABLE IF EXISTS `disciplina`;
CREATE TABLE IF NOT EXISTS `disciplina` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `nome` varchar(100) NOT NULL,
  `professor_id` int(11) NOT NULL,
  `ativo` tinyint(1) DEFAULT 1,
  PRIMARY KEY (`id`),
  KEY `coordenador_id` (`professor_id`)
) ENGINE=MyISAM AUTO_INCREMENT=6 DEFAULT CHARSET=latin1;

--
-- Extraindo dados da tabela `disciplina`
--

INSERT INTO `disciplina` (`id`, `nome`, `professor_id`, `ativo`) VALUES
(1, 'Língua Portuguesa', 3, 1),
(2, 'Matemática', 2, 1),
(3, 'História', 3, 1),
(4, 'Geografia', 2, 1),
(5, 'Ciências', 3, 1);

-- --------------------------------------------------------

--
-- Estrutura da tabela `frequencia`
--

DROP TABLE IF EXISTS `frequencia`;
CREATE TABLE IF NOT EXISTS `frequencia` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `aula_id` int(11) NOT NULL,
  `aluno_id` int(11) NOT NULL,
  `presente` tinyint(1) NOT NULL DEFAULT 0,
  PRIMARY KEY (`id`),
  UNIQUE KEY `aula_id` (`aula_id`,`aluno_id`),
  KEY `aluno_id` (`aluno_id`)
) ENGINE=MyISAM AUTO_INCREMENT=185 DEFAULT CHARSET=latin1;

--
-- Extraindo dados da tabela `frequencia`
--

INSERT INTO `frequencia` (`id`, `aula_id`, `aluno_id`, `presente`) VALUES
(107, 1, 8, 1),
(108, 1, 4, 1),
(109, 1, 5, 1),
(110, 1, 6, 1),
(111, 2, 8, 0),
(112, 2, 4, 1),
(113, 2, 5, 1),
(114, 2, 6, 1),
(115, 3, 8, 1),
(116, 3, 4, 0),
(117, 3, 5, 1),
(118, 3, 6, 1),
(119, 4, 8, 1),
(120, 4, 4, 1),
(121, 4, 5, 0),
(122, 4, 6, 1),
(123, 5, 8, 1),
(124, 5, 4, 1),
(125, 5, 5, 1),
(126, 5, 6, 0),
(127, 6, 8, 1),
(128, 6, 4, 1),
(129, 6, 5, 1),
(130, 6, 7, 1),
(131, 7, 8, 1),
(132, 7, 4, 0),
(133, 7, 5, 1),
(134, 7, 7, 1),
(135, 8, 8, 0),
(136, 8, 4, 1),
(137, 8, 5, 1),
(138, 8, 7, 1),
(139, 9, 8, 1),
(140, 9, 4, 1),
(141, 9, 5, 1),
(142, 9, 7, 0),
(143, 10, 8, 1),
(144, 10, 4, 1),
(145, 10, 5, 0),
(146, 10, 7, 1),
(147, 11, 8, 1),
(148, 11, 4, 1),
(149, 11, 6, 1),
(150, 11, 7, 1),
(151, 12, 8, 0),
(152, 12, 4, 1),
(153, 12, 6, 1),
(154, 12, 7, 1),
(155, 13, 8, 1),
(156, 13, 4, 1),
(157, 13, 6, 0),
(158, 13, 7, 1),
(159, 14, 8, 1),
(160, 14, 4, 0),
(161, 14, 6, 1),
(162, 14, 7, 1),
(163, 15, 8, 1),
(164, 15, 4, 1),
(165, 15, 6, 1),
(166, 15, 7, 0),
(167, 16, 8, 1),
(168, 16, 5, 1),
(169, 16, 6, 1),
(170, 17, 8, 0),
(171, 17, 5, 1),
(172, 17, 6, 1),
(173, 18, 8, 1),
(174, 18, 5, 0),
(175, 18, 6, 1),
(176, 19, 8, 1),
(177, 19, 5, 1),
(178, 19, 7, 1),
(179, 20, 8, 1),
(180, 20, 5, 1),
(181, 20, 7, 0),
(182, 21, 8, 0),
(183, 21, 5, 1),
(184, 21, 7, 1);

-- --------------------------------------------------------

--
-- Estrutura da tabela `matricula`
--

DROP TABLE IF EXISTS `matricula`;
CREATE TABLE IF NOT EXISTS `matricula` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `aluno_id` int(11) NOT NULL,
  `disciplina_id` int(11) NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `aluno_id` (`aluno_id`,`disciplina_id`),
  KEY `turma_id` (`disciplina_id`)
) ENGINE=MyISAM AUTO_INCREMENT=38 DEFAULT CHARSET=latin1;

--
-- Extraindo dados da tabela `matricula`
--

INSERT INTO `matricula` (`id`, `aluno_id`, `disciplina_id`) VALUES
(20, 8, 1),
(21, 8, 2),
(22, 8, 3),
(23, 8, 4),
(24, 8, 5),
(25, 4, 1),
(26, 4, 2),
(27, 4, 3),
(28, 5, 1),
(29, 5, 2),
(30, 5, 4),
(31, 5, 5),
(32, 6, 1),
(33, 6, 3),
(34, 6, 4),
(35, 7, 2),
(36, 7, 3),
(37, 7, 5);

-- --------------------------------------------------------

--
-- Estrutura da tabela `usuario`
--

DROP TABLE IF EXISTS `usuario`;
CREATE TABLE IF NOT EXISTS `usuario` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `nome` varchar(100) NOT NULL,
  `email` varchar(100) NOT NULL,
  `tipo` enum('aluno','professor','coordenador') NOT NULL,
  `login` varchar(50) NOT NULL,
  `senha` varchar(255) NOT NULL,
  `ativo` tinyint(1) DEFAULT 1,
  PRIMARY KEY (`id`),
  UNIQUE KEY `email` (`email`),
  UNIQUE KEY `login` (`login`)
) ENGINE=MyISAM AUTO_INCREMENT=9 DEFAULT CHARSET=latin1;

--
-- Extraindo dados da tabela `usuario`
--

INSERT INTO `usuario` (`id`, `nome`, `email`, `tipo`, `login`, `senha`, `ativo`) VALUES
(1, 'Ana Souza', 'ana.souza@fluxus.edu', 'coordenador', 'ana.souza@fluxus.edu', '123456', 1),
(2, 'Bruno Almeida', 'bruno.almeida@fluxus.edu', 'professor', 'bruno.almeida@fluxus.edu', '123456', 1),
(3, 'Carla Ribeiro', 'carla.ribeiro@fluxus.edu', 'professor', 'carla.ribeiro@fluxus.edu', '123456', 1),
(4, 'Diego Martins', 'diego.martins@estudante.fluxus.edu', 'aluno', 'diego.martins@estudante.fluxus.edu', '123456', 1),
(5, 'Fernanda Lopes', 'fernanda.lopes@estudante.fluxus.edu', 'aluno', 'fernanda.lopes@estudante.fluxus.edu', '123456', 1),
(6, 'Gustavo Pereira', 'gustavo.pereira@estudante.fluxus.edu', 'aluno', 'gustavo.pereira@estudante.fluxus.edu', '123456', 1),
(7, 'Mariana Costa', 'mariana.costa@estudante.fluxus.edu', 'aluno', 'mariana.costa@estudante.fluxus.edu', '123456', 1),
(8, 'Rodrigo Silva', 'rodrigo.silva@estudante.fluxus.edu', 'aluno', 'rodrigo.silva@estudante.fluxus.edu', '123456', 1);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
