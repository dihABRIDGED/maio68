-- Script SQL para criar tabela de contatos de suporte
-- Sistema Educacional - TCC

USE fluxusdb;

-- Criar tabela para contatos de suporte
CREATE TABLE IF NOT EXISTS `contato_suporte` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `tipo` VARCHAR(50) NOT NULL COMMENT 'Tipo do contato: endereco, email, telefone, social',
  `titulo` VARCHAR(100) NOT NULL COMMENT 'Título exibido para o usuário',
  `valor` VARCHAR(255) NOT NULL COMMENT 'Valor do contato (endereço, email, telefone, URL)',
  `icone` VARCHAR(50) DEFAULT 'fas fa-info-circle' COMMENT 'Classe do ícone Font Awesome',
  `cor` VARCHAR(20) DEFAULT '#d32f2f' COMMENT 'Cor do ícone em hexadecimal',
  `ordem` INT(11) DEFAULT 0 COMMENT 'Ordem de exibição (menor número aparece primeiro)',
  `ativo` TINYINT(1) DEFAULT 1 COMMENT '1 = ativo, 0 = inativo',
  `criado_em` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `atualizado_em` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `tipo_unico` (`tipo`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Inserir dados iniciais de contato
INSERT INTO `contato_suporte` (`tipo`, `titulo`, `valor`, `icone`, `cor`, `ordem`, `ativo`) VALUES
('endereco_escola', 'Endereço da Escola', 'Rua Paulo Frontin, 365, Mogi das Cruzes 08710-050, BR', 'fas fa-map-marker-alt', '#d32f2f', 10, 1),
('email_geral', 'E-mail Geral', 'maio68coletivo@gmail.com', 'fas fa-envelope', '#1976d2', 20, 1),
('telefone_geral', 'Telefone Geral', '(11) 99999-9999', 'fas fa-phone', '#388e3c', 30, 1),
('instagram_social', 'Instagram', 'https://www.instagram.com/cursinhopopularmaio68/', 'fab fa-instagram', '#e91e63', 40, 1),
('facebook_social', 'Facebook', 'https://www.facebook.com/cursinhopopularmaio68', 'fab fa-facebook', '#3f51b5', 50, 1),
('youtube_social', 'YouTube', 'https://www.youtube.com/@cursinhopopularmaio68', 'fab fa-youtube', '#f44336', 60, 1);

-- Inserir alguns contatos de professores como exemplo
INSERT INTO `contato_suporte` (`tipo`, `titulo`, `valor`, `icone`, `cor`, `ordem`, `ativo`) VALUES
('prof_matematica', 'Prof. Matemática', 'matematica@fluxus.edu', 'fas fa-calculator', '#ff9800', 100, 1),
('prof_portugues', 'Prof. Português', 'portugues@fluxus.edu', 'fas fa-book-open', '#9c27b0', 110, 1),
('prof_historia', 'Prof. História', 'historia@fluxus.edu', 'fas fa-landmark', '#795548', 120, 1),
('coordenacao', 'Coordenação Pedagógica', 'coordenacao@fluxus.edu', 'fas fa-user-tie', '#607d8b', 130, 1);
