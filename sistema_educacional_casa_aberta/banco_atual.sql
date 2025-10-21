-- phpMyAdmin SQL Dump
-- Versão: 1.0 - Corrigido por Manus AI
-- Baseado nos arquivos 127_0_0_1(7).sql, cronograma_upgrade.sql e dados_realistas.sql

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
-- ESTRUTURA ORIGINAL (Atividade, Disciplina, Frequencia, Matricula, Usuario)
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
) ENGINE=MyISAM AUTO_INCREMENT=8 DEFAULT CHARSET=latin1;

--
-- Estrutura da tabela `disciplina`
--
DROP TABLE IF EXISTS `disciplina`;
CREATE TABLE IF NOT EXISTS `disciplina` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `nome` varchar(100) NOT NULL,
  `professor_id` int(11) NOT NULL,
  `ativo` tinyint(1) DEFAULT 1, -- Adicionado para consistência com cronograma.php
  PRIMARY KEY (`id`),
  KEY `coordenador_id` (`professor_id`)
) ENGINE=MyISAM AUTO_INCREMENT=6 DEFAULT CHARSET=latin1;

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
) ENGINE=MyISAM AUTO_INCREMENT=107 DEFAULT CHARSET=latin1;

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
) ENGINE=MyISAM AUTO_INCREMENT=20 DEFAULT CHARSET=latin1;

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


-- --------------------------------------------------------
-- CORREÇÃO DE LÓGICA: TABELAS AULA E CRONOGRAMA
-- --------------------------------------------------------

--
-- Estrutura da tabela `aula` (Registro de Aulas Ocorridas - Histórico)
-- A coluna 'turma_id' foi substituída por 'disciplina_id' e 'professor_id' para maior granularidade, 
-- conforme a lógica de `cronograma.php`.
--
DROP TABLE IF EXISTS `aula`;
CREATE TABLE IF NOT EXISTS `aula` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `disciplina_id` int(11) NOT NULL,
  `professor_id` int(11) NOT NULL,
  `data` date NOT NULL,
  `horario` TIME NOT NULL, -- Adicionado para registro completo da ocorrência
  `conteudo` text DEFAULT NULL,
  `criado_por` int(11) NOT NULL,
  PRIMARY KEY (`id`),
  KEY `disciplina_id` (`disciplina_id`),
  KEY `professor_id` (`professor_id`),
  KEY `criado_por` (`criado_por`)
) ENGINE=MyISAM AUTO_INCREMENT=26 DEFAULT CHARSET=latin1;

--
-- Estrutura da tabela `cronograma_semanal` (Modelo Fixo Semanal)
-- Esta tabela armazena o modelo de aulas que se repetem semanalmente.
--
DROP TABLE IF EXISTS `cronograma_semanal`;
CREATE TABLE IF NOT EXISTS `cronograma_semanal` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `dia_semana` VARCHAR(20) NOT NULL,
  `horario` TIME NOT NULL,
  `disciplina_id` int(11) NOT NULL,
  `professor_id` int(11) NOT NULL,
  `conteudo` text DEFAULT NULL, -- Conteúdo padrão para o cronograma
  `criado_por` int(11) NOT NULL,
  PRIMARY KEY (`id`),
  KEY `disciplina_id` (`disciplina_id`),
  KEY `professor_id` (`professor_id`),
  UNIQUE KEY `dia_horario` (`dia_semana`, `horario`) -- Garante que não haja duas aulas no mesmo horário/dia
) ENGINE=MyISAM AUTO_INCREMENT=13 DEFAULT CHARSET=latin1;

-- --------------------------------------------------------
-- INSERÇÃO DE DADOS
-- --------------------------------------------------------

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

--
-- Extraindo dados da tabela `disciplina`
-- IDs: 1 a 5
--
INSERT INTO `disciplina` (`id`, `nome`, `professor_id`, `ativo`) VALUES
(1, 'Língua Portuguesa', 3, 1),
(2, 'Matemática', 2, 1),
(3, 'História', 3, 1),
(4, 'Geografia', 2, 1),
(5, 'Ciências', 3, 1);

--
-- Extraindo dados da tabela `matricula`
--
INSERT INTO `matricula` (`aluno_id`, `disciplina_id`) VALUES
(8, 1), (8, 2), (8, 3), (8, 4), (8, 5),
(4, 1), (4, 2), (4, 3),
(5, 1), (5, 2), (5, 4), (5, 5),
(6, 1), (6, 3), (6, 4),
(7, 2), (7, 3), (7, 5);

--
-- Extraindo dados da tabela `atividade`
--
INSERT INTO `atividade` (`disciplina_id`, `titulo`, `descricao`, `data_atividade`, `tipo`, `criado_por`) VALUES
(1, 'Redação sobre Machado de Assis', 'Escrever uma redação de 30 linhas sobre a obra Dom Casmurro', '2024-12-15', 'redacao', 3),
(1, 'Prova de Literatura', 'Avaliação sobre Romantismo e Realismo', '2024-12-18', 'prova', 3),
(2, 'Lista de Exercícios - Equações', 'Resolver exercícios do capítulo 5', '2024-12-12', 'exercicio', 2),
(2, 'Prova de Matemática', 'Avaliação sobre funções e geometria', '2024-12-20', 'prova', 2),
(3, 'Trabalho sobre Brasil Colonial', 'Pesquisa em grupo sobre período colonial', '2024-12-16', 'trabalho', 3),
(4, 'Mapa do Brasil', 'Desenhar mapa com relevos e rios', '2024-12-14', 'atividade', 2),
(5, 'Relatório de Experimento', 'Relatório sobre observação de células', '2024-12-17', 'relatorio', 3);

--
-- Inserindo dados na tabela `aula` (Histórico de Aulas Ocorridas)
-- Os dados foram adaptados para incluir `professor_id` e `horario`
--
INSERT INTO `aula` (`id`, `disciplina_id`, `professor_id`, `data`, `horario`, `conteudo`, `criado_por`) VALUES
-- Aulas de Língua Portuguesa (id=1, professor_id=3)
(1, 1, 3, '2024-12-01', '08:00:00', 'Introdução à Literatura Brasileira', 3),
(2, 1, 3, '2024-12-03', '08:00:00', 'Análise de Texto - Machado de Assis', 3),
(3, 1, 3, '2024-12-05', '08:00:00', 'Gramática: Classes de Palavras', 3),
(4, 1, 3, '2024-12-08', '08:00:00', 'Redação: Texto Dissertativo', 3),
(5, 1, 3, '2024-12-10', '08:00:00', 'Literatura: Romantismo', 3),

-- Aulas de Matemática (id=2, professor_id=2)
(6, 2, 2, '2024-12-02', '10:00:00', 'Equações do 1º Grau', 2),
(7, 2, 2, '2024-12-04', '10:00:00', 'Equações do 2º Grau', 2),
(8, 2, 2, '2024-12-06', '10:00:00', 'Funções Lineares', 2),
(9, 2, 2, '2024-12-09', '10:00:00', 'Geometria Plana', 2),
(10, 2, 2, '2024-12-11', '10:00:00', 'Trigonometria Básica', 2),

-- Aulas de História (id=3, professor_id=3)
(11, 3, 3, '2024-12-02', '14:00:00', 'Brasil Colonial', 3),
(12, 3, 3, '2024-12-04', '14:00:00', 'Independência do Brasil', 3),
(13, 3, 3, '2024-12-06', '14:00:00', 'República Velha', 3),
(14, 3, 3, '2024-12-09', '14:00:00', 'Era Vargas', 3),
(15, 3, 3, '2024-12-11', '14:00:00', 'Ditadura Militar', 3),

-- Aulas de Geografia (id=4, professor_id=2)
(16, 4, 2, '2024-12-03', '08:00:00', 'Relevo Brasileiro', 2),
(17, 4, 2, '2024-12-05', '08:00:00', 'Hidrografia', 2),
(18, 4, 2, '2024-12-10', '08:00:00', 'Clima e Vegetação', 2),

-- Aulas de Ciências (id=5, professor_id=3)
(19, 5, 3, '2024-12-01', '10:00:00', 'Sistema Solar', 3),
(20, 5, 3, '2024-12-03', '10:00:00', 'Células e Tecidos', 3),
(21, 5, 3, '2024-12-08', '10:00:00', 'Ecossistemas', 3);

--
-- Inserindo dados na tabela `cronograma_semanal` (Modelo Fixo Semanal)
-- Os dados foram movidos do script `cronograma_upgrade.sql` e adaptados.
--
INSERT INTO `cronograma_semanal` (`dia_semana`, `horario`, `disciplina_id`, `professor_id`, `conteudo`, `criado_por`) VALUES
-- Segunda-feira
('segunda', '08:00:00', 1, 3, 'Língua Portuguesa - Gramática', 3),
('segunda', '10:00:00', 2, 2, 'Matemática - Álgebra', 2),
('segunda', '14:00:00', 3, 3, 'História - Brasil República', 3),

-- Terça-feira
('terca', '08:00:00', 4, 2, 'Geografia - Relevo Brasileiro', 2),
('terca', '10:00:00', 5, 3, 'Ciências - Sistema Solar', 3),
('terca', '14:00:00', 1, 3, 'Língua Portuguesa - Literatura', 3),

-- Quarta-feira
('quarta', '08:00:00', 2, 2, 'Matemática - Geometria', 2),
('quarta', '10:00:00', 3, 3, 'História - Era Vargas', 3),
('quarta', '14:00:00', 4, 2, 'Geografia - Hidrografia', 2),

-- Quinta-feira
('quinta', '08:00:00', 5, 3, 'Ciências - Ecossistemas', 3),
('quinta', '10:00:00', 1, 3, 'Língua Portuguesa - Redação', 3),
('quinta', '14:00:00', 2, 2, 'Matemática - Funções', 2),

-- Sexta-feira
('sexta', '08:00:00', 3, 3, 'História - Ditadura Militar', 3),
('sexta', '10:00:00', 4, 2, 'Geografia - Clima e Vegetação', 2),
('sexta', '14:00:00', 5, 3, 'Ciências - Células e Tecidos', 3);

--
-- Inserindo dados na tabela `frequencia` (Adaptado para novos IDs de aula)
-- Os IDs de aula foram ajustados para iniciar em 1 e seguir a ordem de inserção na tabela `aula`
--
INSERT INTO `frequencia` (`aula_id`, `aluno_id`, `presente`) VALUES
-- Aulas de Língua Portuguesa (aula_id 1-5)
(1, 8, 1), (1, 4, 1), (1, 5, 1), (1, 6, 1), -- Aula 1 - todos presentes
(2, 8, 0), (2, 4, 1), (2, 5, 1), (2, 6, 1), -- Aula 2 - Rodrigo faltou
(3, 8, 1), (3, 4, 0), (3, 5, 1), (3, 6, 1), -- Aula 3 - Diego faltou
(4, 8, 1), (4, 4, 1), (4, 5, 0), (4, 6, 1), -- Aula 4 - Fernanda faltou
(5, 8, 1), (5, 4, 1), (5, 5, 1), (5, 6, 0), -- Aula 5 - Gustavo faltou

-- Aulas de Matemática (aula_id 6-10)
(6, 8, 1), (6, 4, 1), (6, 5, 1), (6, 7, 1), -- Aula 1 - todos presentes
(7, 8, 1), (7, 4, 0), (7, 5, 1), (7, 7, 1), -- Aula 2 - Diego faltou
(8, 8, 0), (8, 4, 1), (8, 5, 1), (8, 7, 1), -- Aula 3 - Rodrigo faltou
(9, 8, 1), (9, 4, 1), (9, 5, 1), (9, 7, 0), -- Aula 4 - Mariana faltou
(10, 8, 1), (10, 4, 1), (10, 5, 0), (10, 7, 1), -- Aula 5 - Fernanda faltou

-- Aulas de História (aula_id 11-15)
(11, 8, 1), (11, 4, 1), (11, 6, 1), (11, 7, 1), -- Aula 1 - todos presentes
(12, 8, 0), (12, 4, 1), (12, 6, 1), (12, 7, 1), -- Aula 2 - Rodrigo faltou
(13, 8, 1), (13, 4, 1), (13, 6, 0), (13, 7, 1), -- Aula 3 - Gustavo faltou
(14, 8, 1), (14, 4, 0), (14, 6, 1), (14, 7, 1), -- Aula 4 - Diego faltou
(15, 8, 1), (15, 4, 1), (15, 6, 1), (15, 7, 0), -- Aula 5 - Mariana faltou

-- Aulas de Geografia (aula_id 16-18)
(16, 8, 1), (16, 5, 1), (16, 6, 1), -- Aula 1 - todos presentes
(17, 8, 0), (17, 5, 1), (17, 6, 1), -- Aula 2 - Rodrigo faltou
(18, 8, 1), (18, 5, 0), (18, 6, 1), -- Aula 3 - Fernanda faltou

-- Aulas de Ciências (aula_id 19-21)
(19, 8, 1), (19, 5, 1), (19, 7, 1), -- Aula 1 - todos presentes
(20, 8, 1), (20, 5, 1), (20, 7, 0), -- Aula 2 - Mariana faltou
(21, 8, 0), (21, 5, 1), (21, 7, 1); -- Aula 3 - Rodrigo faltou

COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;

