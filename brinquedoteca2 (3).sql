-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Tempo de geração: 07/06/2025 às 03:08
-- Versão do servidor: 10.4.32-MariaDB
-- Versão do PHP: 8.2.12

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Banco de dados: `brinquedoteca2`
--

-- --------------------------------------------------------

--
-- Estrutura para tabela `area_desenvolvimento`
--

CREATE TABLE `area_desenvolvimento` (
  `ID_area` int(11) NOT NULL,
  `nome_area` varchar(55) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Despejando dados para a tabela `area_desenvolvimento`
--

INSERT INTO `area_desenvolvimento` (`ID_area`, `nome_area`) VALUES
(3, 'Atenção'),
(4, 'Linguagem'),
(2, 'Memória'),
(6, 'Percepção'),
(5, 'Raciocínio'),
(7, 'Resolução de Problemas');

-- --------------------------------------------------------

--
-- Estrutura para tabela `crianca`
--

CREATE TABLE `crianca` (
  `ID_crianca` int(11) NOT NULL,
  `ID_responsavel` int(11) NOT NULL,
  `nome_crianca` varchar(255) NOT NULL,
  `data_nascimento` date DEFAULT NULL,
  `sexo` enum('M','F','O') DEFAULT NULL COMMENT 'M=Masculino, F=Feminino, O=Outro',
  `ID_area_interesse` int(11) DEFAULT NULL,
  `observacoes` text DEFAULT NULL,
  `data_cadastro` datetime NOT NULL DEFAULT current_timestamp(),
  `nome_funcionario_entrega` varchar(255) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Despejando dados para a tabela `crianca`
--

INSERT INTO `crianca` (`ID_crianca`, `ID_responsavel`, `nome_crianca`, `data_nascimento`, `sexo`, `ID_area_interesse`, `observacoes`, `data_cadastro`, `nome_funcionario_entrega`) VALUES
(11, 15, 'Lucas Souza Santana', '2018-07-14', 'M', NULL, 'Muito ativo, gosta de montar quebra-cabeças.\r\n', '2025-06-02 08:34:37', ''),
(12, 16, 'Isabela Rocha Lima', '2020-02-02', 'F', NULL, 'Tem alergia a amendoim, evitar lanche com nozes.', '2025-06-02 08:35:23', ''),
(13, 17, 'Bruno Martins Costa', '2019-09-26', 'M', NULL, 'Participa bem em grupo, adora pintura.', '2025-06-02 08:36:02', ''),
(14, 18, 'Ana Júlia Ferreira', '2021-06-08', 'F', NULL, 'Está em fase de adaptação, precisa de acolhimento.', '2025-06-02 08:36:53', ''),
(15, 19, 'Pedro Duarte Nunes', '2021-12-11', 'M', NULL, 'Muito comunicativo, ajuda os colegas nas tarefas.', '2025-06-02 08:37:35', ''),
(16, 20, 'Jhennifer Lais', '2020-05-30', 'F', NULL, 'Criança linda', '2025-06-05 02:51:27', ''),
(17, 21, 'Lucas Pietro', '2008-04-08', 'M', NULL, 'Criança jogadora', '2025-06-05 03:30:12', ''),
(18, 22, 'teste', '2025-02-04', 'M', NULL, 'teste', '2025-06-05 03:36:50', ''),
(19, 24, 'Leonardo Rocha', '2003-02-12', 'M', NULL, 'Criança alta', '2025-06-05 22:12:07', ''),
(20, 25, 'Elisangela Quero', '2021-06-10', 'F', NULL, 'Criança morena, baixa e ama doces', '2025-06-06 18:38:36', ''),
(21, 26, 'TESTE2', '2024-02-22', 'M', NULL, 'TESTE', '2025-06-06 19:40:27', '');

-- --------------------------------------------------------

--
-- Estrutura para tabela `emprestimo`
--

CREATE TABLE `emprestimo` (
  `ID_emprestimo` int(11) NOT NULL,
  `ID_crianca` int(11) NOT NULL,
  `ID_funcionario_entrega` int(11) NOT NULL,
  `ID_funcionario_devolucao` int(11) DEFAULT NULL,
  `data_emprestimo` datetime NOT NULL DEFAULT current_timestamp(),
  `data_limite_devolucao` date NOT NULL,
  `data_devolucao` datetime DEFAULT NULL,
  `situacao` enum('Emprestado','Devolvido','Atrasado') NOT NULL DEFAULT 'Emprestado',
  `observacoes_emprestimo` text DEFAULT NULL,
  `observacoes_devolucao` text DEFAULT NULL,
  `ID_item` int(11) NOT NULL,
  `ID_responsavel` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Despejando dados para a tabela `emprestimo`
--

INSERT INTO `emprestimo` (`ID_emprestimo`, `ID_crianca`, `ID_funcionario_entrega`, `ID_funcionario_devolucao`, `data_emprestimo`, `data_limite_devolucao`, `data_devolucao`, `situacao`, `observacoes_emprestimo`, `observacoes_devolucao`, `ID_item`, `ID_responsavel`) VALUES
(51, 19, 34, NULL, '2025-04-05 22:13:13', '2025-03-05', NULL, 'Emprestado', 'Todos cubos estao em ótimo estado', NULL, 20, 24),
(52, 16, 34, NULL, '2025-04-05 22:13:47', '2025-04-13', NULL, 'Emprestado', 'Em otimo estado', NULL, 14, 20),
(55, 16, 34, NULL, '2025-06-06 18:39:19', '2025-05-05', NULL, 'Emprestado', 'Boneca Funcionando', NULL, 17, 20),
(57, 14, 34, NULL, '2025-06-06 19:38:27', '2025-12-22', NULL, 'Emprestado', 'teste', NULL, 16, 26),
(58, 18, 34, NULL, '2025-06-06 19:39:51', '2025-12-12', NULL, 'Emprestado', 'teste', NULL, 16, 26),
(59, 21, 34, NULL, '2025-06-06 19:41:07', '2025-12-12', NULL, 'Emprestado', 'TESTE', NULL, 16, 26),
(60, 21, 34, NULL, '2025-06-06 19:45:49', '2025-12-22', NULL, 'Emprestado', 'TESTE3', NULL, 16, 26),
(61, 21, 34, NULL, '2025-06-06 19:46:41', '2025-12-22', NULL, 'Emprestado', 'TESTE3', NULL, 16, 26),
(62, 21, 34, NULL, '2025-06-06 19:49:46', '2025-06-11', NULL, 'Emprestado', 'TESTE4', NULL, 16, 26),
(63, 15, 34, 34, '2025-06-06 21:59:58', '2025-06-16', '2025-06-06 00:00:00', 'Devolvido', 'Funcionando', NULL, 27, 19);

-- --------------------------------------------------------

--
-- Estrutura para tabela `endereco`
--

CREATE TABLE `endereco` (
  `ID_endereco` int(11) NOT NULL,
  `CEP` char(8) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Despejando dados para a tabela `endereco`
--

INSERT INTO `endereco` (`ID_endereco`, `CEP`) VALUES
(1, '13485346'),
(2, '43798343'),
(3, '11111111'),
(4, '11111111'),
(5, '13485346'),
(6, '23454671'),
(7, '66666666'),
(8, '01001000'),
(9, '20010050'),
(10, '30130100'),
(11, '40020200'),
(12, '50050300'),
(13, '13485346'),
(14, '73489574'),
(15, '13485346'),
(16, '46237473'),
(17, '59263451'),
(18, '47895126'),
(33, '19818189'),
(34, '78416254'),
(35, '48948198');

-- --------------------------------------------------------

--
-- Estrutura para tabela `fornecedor`
--

CREATE TABLE `fornecedor` (
  `ID_fornecedor` int(11) NOT NULL,
  `nome_fornecedor` varchar(100) NOT NULL,
  `cnpj` varchar(14) NOT NULL,
  `email` varchar(100) DEFAULT NULL,
  `data_cadastro` datetime NOT NULL DEFAULT current_timestamp(),
  `ID_endereco` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Despejando dados para a tabela `fornecedor`
--

INSERT INTO `fornecedor` (`ID_fornecedor`, `nome_fornecedor`, `cnpj`, `email`, `data_cadastro`, `ID_endereco`) VALUES
(4, 'ToyMax', '12345678000190', 'contato@empresa1.com.br', '2025-06-02 08:01:03', NULL),
(5, 'Estrela Distribuidora', '23456789000101', 'vendas@loja2.com.br', '2025-06-02 08:01:44', NULL),
(6, 'RC Toys', '34567890000112', 'suporte@tec3.com.br', '2025-06-02 08:02:28', NULL),
(7, 'EducToys', '45678901000123', 'financeiro@empresa4.com.br', '2025-06-02 08:02:58', NULL),
(8, 'Ludeka Brinquedos', '22333444000105', 'financeiro@saude12.com.br', '2025-06-02 08:03:31', NULL);

-- --------------------------------------------------------

--
-- Estrutura para tabela `funcionario`
--

CREATE TABLE `funcionario` (
  `ID_funcionario` int(11) NOT NULL,
  `nome_funcionario` varchar(100) NOT NULL,
  `cpf` varchar(11) NOT NULL,
  `cargo_funcionario` varchar(50) NOT NULL,
  `senha_login_hash` varchar(255) NOT NULL COMMENT 'Armazenar HASH da senha',
  `data_admissao` date DEFAULT NULL,
  `ativo` tinyint(1) NOT NULL DEFAULT 1,
  `ID_endereco` int(11) DEFAULT NULL,
  `email_funcionario` varchar(100) NOT NULL,
  `data_cadastro` date DEFAULT NULL,
  `cep` varchar(8) DEFAULT NULL,
  `nivel_acesso` enum('admin','gerente','funcionario') NOT NULL DEFAULT 'funcionario'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Despejando dados para a tabela `funcionario`
--

INSERT INTO `funcionario` (`ID_funcionario`, `nome_funcionario`, `cpf`, `cargo_funcionario`, `senha_login_hash`, `data_admissao`, `ativo`, `ID_endereco`, `email_funcionario`, `data_cadastro`, `cep`, `nivel_acesso`) VALUES
(29, 'Amanda Ribeiro Costa', '12345678900', 'Coordenadora da Brinquedoteca', '$2y$10$A.pCAItAUu3agipdZLr4r.hGbqKcmze3/rOJlI55Lzwgv9P2TrF8y', '2022-02-15', 1, 8, 'amanda.costa@email.com', NULL, NULL, 'gerente'),
(30, 'Bruno Silva Mendes', '23456789011', 'Monitor de Atividades Lúdicas', '$2y$10$f7VP6ZM.642aUQk/vEtKiOt0gB9BhJX9XeTmv6qJsLzAwovaQguay', '2022-08-01', 1, 9, 'bruno.mendes@email.com', NULL, NULL, 'funcionario'),
(31, 'Carla Lima Soares', '34567890122', 'Auxiliar de Organização', '$2y$10$CcDJTLGQnItqOLJcGzq0COkym7TmWNn0.BHiVABQBnrMAmE/4c5vy', '2023-01-10', 1, 10, 'carla.soares@email.com', NULL, NULL, 'funcionario'),
(32, 'Diego Oliveira Luz', '45678901233', 'Recepcionista', '$2y$10$GDjoPCUO5TlagyXb3xLXsegGt0YvUtCBZ09nkUw7B9T1jt2HERANG', '2024-11-23', 1, 11, 'diego.luz@email.com', NULL, NULL, 'funcionario'),
(33, 'Elaine Martins Rocha', '56789012344', 'Contadora da Brinquedoteca', '$2y$10$tsUIpnQFpmVFirM6WyMSUuzWe3SgjjVoJE8adjkwvV1s5zvj2JlMW', '2025-06-01', 1, 12, 'elaine.rocha@email.com', NULL, NULL, 'funcionario'),
(34, 'admin', '06141901182', 'CEO', '$2y$10$1BQKQoLHzOIaIy7KSb17DePcMQPBV9dhm.zKkt03Kt8stux0k1x1q', '2025-06-03', 1, 13, 'admin@gmail.com', NULL, NULL, 'admin'),
(35, 'Gabriel Xavier', '58764387536', 'Faxineiro', '$2y$10$vsG6G2Jx9xAHYzGZVvQdMO2r6n3WiT9GeFkQAnNBGd2TMe186Vypi', '2025-06-04', 1, 14, 'gabrielXavier@gmail.com', NULL, NULL, 'gerente'),
(36, 'Eduardo Rocha', '87456384739', 'Porteiro', '$2y$10$osIdt.Wyh8ym9nw9znvTg.hb.5Zckk4Raqs6SW9JJmzfMSFSoydI.', '2025-06-04', 1, 15, 'eduardo@gmail.com', NULL, NULL, 'funcionario'),
(38, 'gerente', '98564195268', 'Gerente', '$2y$10$0iUxZqfQbkapduL3s0MnsulQw8Go0zzQxwhBTcYViuNJcRqQZTodq', '2025-06-05', 1, 17, 'gerente@gmail.com', NULL, NULL, 'gerente'),
(39, 'funcionario', '95267841263', 'funcionario', '$2y$10$bHfFZhr5Xpyv5.MlwTrZCOEx49MeCDPM0sElY1lJl3t.r7jogC6TS', '2025-06-05', 1, 18, 'funcionario@gmail.com', NULL, NULL, 'funcionario'),
(40, 'Renato Cardoso', '89481981189', 'Auxiliar Adminstrativo', '$2y$10$hAhkucMXNcE1NygHhC8Mve8ER5.9QAOZIF1W/yWegUEwCVksHye06', '2025-06-06', 1, 35, 'RenatoCardoso@gmail.com', NULL, NULL, 'funcionario');

-- --------------------------------------------------------

--
-- Estrutura para tabela `item`
--

CREATE TABLE `item` (
  `ID_item` int(11) NOT NULL,
  `tipo_item` enum('Brinquedo','Jogo') NOT NULL,
  `nome_item` varchar(100) NOT NULL,
  `marca_item` varchar(50) DEFAULT NULL,
  `numero_serie` varchar(100) DEFAULT NULL,
  `numero_NF` varchar(44) DEFAULT NULL,
  `ID_fornecedor` int(11) NOT NULL,
  `ID_area_desenvolvimento` int(11) DEFAULT NULL,
  `descricao` text DEFAULT NULL,
  `imagem_path` varchar(255) DEFAULT NULL COMMENT 'Caminho para imagem',
  `quantidade_total` int(11) NOT NULL DEFAULT 1,
  `quantidade_disponivel` int(11) NOT NULL DEFAULT 1,
  `data_aquisicao` date DEFAULT NULL,
  `valor_aquisicao` decimal(10,2) DEFAULT NULL,
  `ativo` tinyint(1) NOT NULL DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Despejando dados para a tabela `item`
--

INSERT INTO `item` (`ID_item`, `tipo_item`, `nome_item`, `marca_item`, `numero_serie`, `numero_NF`, `ID_fornecedor`, `ID_area_desenvolvimento`, `descricao`, `imagem_path`, `quantidade_total`, `quantidade_disponivel`, `data_aquisicao`, `valor_aquisicao`, `ativo`) VALUES
(14, 'Brinquedo', 'Quebra-Cabeça 1000 Peças', 'Grow', 'QC-1000-2023', '001235', 7, 5, 'Quebra-cabeça com imagem de paisagem, 1000 peças', 'uploads/itens/item_683d3f16954d5.png', 10, 4, '2023-05-14', 49.00, 1),
(15, 'Jogo', 'Jogo de Tabuleiro Jogo da vida', 'Estrela', 'BT-2022-004', '001237', 6, 3, 'Jogo de simulação de vida com dinheiro fictício', 'uploads/itens/item_683d3f9cdc53c.webp', 5, 4, '2023-07-20', 89.90, 1),
(16, 'Brinquedo', 'Carrinho Controle Remoto', 'Candide', 'CC-RM-887', '001240', 6, 3, 'Carrinho esportivo com controle remoto', 'uploads/itens/item_683d40024d9d5.jpg', 8, 1, '2024-01-10', 150.00, 1),
(17, 'Brinquedo', 'Boneca Interativa', 'Baby Brink', 'BBI-009', '001250', 4, 4, 'Boneca que fala frases e canta músicas infantis', 'uploads/itens/item_683d4052637e5.jpg', 10, 0, '2024-11-30', 99.99, 1),
(18, 'Jogo', 'Jogo de Xadrez Magnético', 'Xalingo', 'JX-MAG-77', '001256', 5, 5, 'Tabuleiro magnético portátil com peças de xadrez', 'uploads/itens/item_683d40952aa4a.jpg', 12, 9, '2025-06-01', 42.00, 1),
(19, '', 'Massinha de Modelar Colorida', 'Acrilex', 'MASS-COLOR-03', '001260', 5, 2, 'Kit com 10 cores de massinha não tóxica', 'uploads/itens/item_683d40eea9d21.webp', 20, 19, '2025-03-15', 18.90, 1),
(20, 'Brinquedo', 'Cubo Mágico 3x3', 'Moyu', 'CUB-MAG-3X3', '001275', 7, 5, 'Cubo mágico de velocidade com bom giro', 'uploads/itens/item_683d4140f2a8d.webp', 21, 20, '2024-05-08', 19.90, 1),
(21, 'Brinquedo', 'Kit Cientista Mirim', 'Science Kids', 'KIT-CIENT-001', '001310', 8, 5, 'Kit com tubos de ensaio, lupa e instruções de experiências simples', 'uploads/itens/item_683d419157052.jpg', 6, 4, '2024-11-01', 78.50, 1),
(22, 'Jogo', 'Jogo de Palavras Cruzadas', 'Palavra Legal', 'PL-CRUZ-001', '001315', 5, 4, 'Tabuleiro com peças para formar palavras como Scrabble', 'uploads/itens/item_683d41e14b3f9.webp', 8, 6, '2023-01-22', 50.00, 1),
(23, 'Brinquedo', 'Trenzinho de Madeira', 'Brinque Bem', 'TRE-MAD-03', '001320', 6, 3, 'Trem com blocos encaixáveis de madeira colorida', 'uploads/itens/item_683d423a23f62.webp', 7, 5, '2024-03-07', 59.90, 1),
(24, 'Jogo', 'Jogo de Dados Educativos', 'Dado Brincar', 'JOG-DADO-EDU', '001325', 8, 5, 'Dados com letras, números e figuras para jogos variados', 'uploads/itens/item_683d4289de373.webp', 16, 12, '2024-05-18', 27.00, 1),
(27, 'Brinquedo', 'Chupa Cabra', 'Estrela', '4198189198198', '65151561', 8, 2, 'Chupa Cabra', 'uploads/itens/item_68438ed858c44.webp', 12, 8, '2000-11-11', 10.00, 1);

-- --------------------------------------------------------

--
-- Estrutura para tabela `item_emprestimo`
--

CREATE TABLE `item_emprestimo` (
  `ID_item_emprestimo` int(11) NOT NULL,
  `ID_emprestimo` int(11) NOT NULL,
  `ID_item` int(11) NOT NULL,
  `quantidade` int(11) NOT NULL DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Despejando dados para a tabela `item_emprestimo`
--

INSERT INTO `item_emprestimo` (`ID_item_emprestimo`, `ID_emprestimo`, `ID_item`, `quantidade`) VALUES
(25, 51, 20, 5),
(26, 52, 14, 2),
(29, 55, 17, 2),
(31, 57, 16, 1),
(32, 58, 16, 1),
(33, 59, 16, 1),
(34, 60, 16, 2),
(35, 61, 16, 1),
(36, 62, 16, 1),
(37, 63, 27, 2);

-- --------------------------------------------------------

--
-- Estrutura para tabela `multa`
--

CREATE TABLE `multa` (
  `ID_multa` int(11) NOT NULL,
  `ID_emprestimo` int(11) DEFAULT NULL,
  `ID_dano` int(11) DEFAULT NULL,
  `valor_multa` decimal(10,2) NOT NULL,
  `data_ocorrencia` datetime NOT NULL DEFAULT current_timestamp(),
  `data_pagamento` date DEFAULT NULL,
  `situacao` enum('Pendente','Paga','Cancelada') NOT NULL DEFAULT 'Pendente',
  `observacoes` text DEFAULT NULL,
  `valor_reparo` decimal(10,2) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Despejando dados para a tabela `multa`
--

INSERT INTO `multa` (`ID_multa`, `ID_emprestimo`, `ID_dano`, `valor_multa`, `data_ocorrencia`, `data_pagamento`, `situacao`, `observacoes`, `valor_reparo`) VALUES
(26, NULL, NULL, 170.00, '2025-06-04 13:08:33', '2025-06-04', 'Pendente', NULL, 0.00),
(27, NULL, NULL, 170.00, '2025-06-04 13:09:17', '2025-06-04', 'Pendente', NULL, 0.00),
(28, NULL, NULL, 170.00, '2025-06-04 13:09:18', '2025-06-04', 'Pendente', NULL, 0.00),
(29, NULL, NULL, 170.00, '2025-06-04 13:13:19', '2025-06-04', 'Pendente', NULL, 0.00),
(30, NULL, NULL, 170.00, '2025-06-04 13:14:14', '2025-06-04', 'Pendente', NULL, 0.00),
(31, NULL, NULL, 10.00, '2025-06-04 00:00:00', NULL, 'Pendente', NULL, 0.00),
(32, NULL, NULL, 170.00, '2025-06-04 13:16:12', '2025-06-04', 'Pendente', NULL, 0.00),
(33, NULL, NULL, 10.00, '2025-06-04 00:00:00', NULL, 'Pendente', NULL, 0.00),
(34, NULL, NULL, 108.00, '2025-06-05 22:15:41', '2025-06-05', 'Pendente', NULL, 0.00);

-- --------------------------------------------------------

--
-- Estrutura para tabela `registro_dano`
--

CREATE TABLE `registro_dano` (
  `ID_dano` int(11) NOT NULL,
  `ID_item` int(11) NOT NULL,
  `ID_emprestimo` int(11) DEFAULT NULL,
  `ID_funcionario_registro` int(11) NOT NULL,
  `descricao_dano` text NOT NULL,
  `data_registro` datetime NOT NULL DEFAULT current_timestamp(),
  `situacao` enum('Registrado','Reparado','Irreparável','Multa Gerada') NOT NULL DEFAULT 'Registrado',
  `observacoes` text NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Despejando dados para a tabela `registro_dano`
--

INSERT INTO `registro_dano` (`ID_dano`, `ID_item`, `ID_emprestimo`, `ID_funcionario_registro`, `descricao_dano`, `data_registro`, `situacao`, `observacoes`) VALUES
(4, 17, NULL, 38, 'Veio sem uma perna', '2025-06-04 00:00:00', 'Registrado', '');

-- --------------------------------------------------------

--
-- Estrutura para tabela `responsavel`
--

CREATE TABLE `responsavel` (
  `ID_responsavel` int(11) NOT NULL,
  `nome` varchar(100) NOT NULL,
  `CPF` char(11) NOT NULL,
  `sexo` enum('M','F','O') NOT NULL COMMENT 'M=Masculino, F=Feminino, O=Outro',
  `email` varchar(100) DEFAULT NULL,
  `data_nascimento` date DEFAULT NULL,
  `data_cadastro` datetime NOT NULL DEFAULT current_timestamp(),
  `ID_endereco` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Despejando dados para a tabela `responsavel`
--

INSERT INTO `responsavel` (`ID_responsavel`, `nome`, `CPF`, `sexo`, `email`, `data_nascimento`, `data_cadastro`, `ID_endereco`) VALUES
(15, 'Mariana Alves Souza', '12345678900', 'F', 'mariana.souza@email.com', '1985-03-10', '2025-06-02 08:30:49', NULL),
(16, 'Felipe Gomes Rocha', '23456789011', 'M', 'felipe.rocha@email.com', '1982-08-22', '2025-06-02 08:31:52', NULL),
(17, 'Priscila Dias Martins', '34567890122', 'F', 'priscila.martins@email.com', '1990-05-17', '2025-06-02 08:32:29', NULL),
(18, 'Rodrigo Lima Ferreira', '45678901233', 'M', 'rodrigo.lima@email.com', '1986-11-04', '2025-06-02 08:32:52', NULL),
(19, 'Aline Santos Duarte', '56789012345', 'F', 'aline.duarte@email.com', '1988-09-29', '2025-06-02 08:33:21', NULL),
(20, 'Eduardo Rocha', '47815274815', 'M', 'eduardofake386@gmail.com', '2000-05-08', '2025-06-05 02:50:43', NULL),
(21, 'Jhennifer Cardoso', '49815849584', 'F', 'jhennifercardoso6@gmail.com', '2004-05-30', '2025-06-05 03:29:37', NULL),
(22, 'Gabriel Flausino', '48941895418', 'M', 'gflausinogaia@gmail.com', '2000-12-12', '2025-06-05 03:36:09', NULL),
(23, 'Renatao Rissato', '48198186189', 'M', 'renatorissatodasilva55@gmail.com', '2000-09-28', '2025-06-05 19:32:43', NULL),
(24, 'Edvalto Thimoteo', '48989981498', 'M', 'thimoteo.rocha123@gmail.com', '1967-04-01', '2025-06-05 22:11:21', NULL),
(25, 'Felipe Migot', '41258941818', 'M', 'felipeMigot@gmail.com', '1970-12-22', '2025-06-06 18:37:44', NULL),
(26, 'Nicholas Eduardo', '42198198198', 'M', 'nicholaseduardocardoso@hotmail.com', '2000-02-22', '2025-06-06 19:37:27', NULL),
(27, 'Zecca', '18119819819', 'M', 'zecca@gmail.com', '2000-11-11', '2025-06-06 21:57:51', NULL);

-- --------------------------------------------------------

--
-- Estrutura para tabela `responsavel_telefone`
--

CREATE TABLE `responsavel_telefone` (
  `ID_responsavel` int(11) NOT NULL,
  `ID_telefone` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Índices para tabelas despejadas
--

--
-- Índices de tabela `area_desenvolvimento`
--
ALTER TABLE `area_desenvolvimento`
  ADD PRIMARY KEY (`ID_area`),
  ADD UNIQUE KEY `nome_area` (`nome_area`);

--
-- Índices de tabela `crianca`
--
ALTER TABLE `crianca`
  ADD PRIMARY KEY (`ID_crianca`),
  ADD KEY `ID_responsavel` (`ID_responsavel`),
  ADD KEY `ID_area_interesse` (`ID_area_interesse`);

--
-- Índices de tabela `emprestimo`
--
ALTER TABLE `emprestimo`
  ADD PRIMARY KEY (`ID_emprestimo`),
  ADD KEY `ID_crianca` (`ID_crianca`),
  ADD KEY `ID_funcionario_entrega` (`ID_funcionario_entrega`),
  ADD KEY `ID_funcionario_devolucao` (`ID_funcionario_devolucao`),
  ADD KEY `fk_emprestimo_item` (`ID_item`),
  ADD KEY `fk_emprestimos_responsavel` (`ID_responsavel`);

--
-- Índices de tabela `endereco`
--
ALTER TABLE `endereco`
  ADD PRIMARY KEY (`ID_endereco`);

--
-- Índices de tabela `fornecedor`
--
ALTER TABLE `fornecedor`
  ADD PRIMARY KEY (`ID_fornecedor`),
  ADD UNIQUE KEY `cnpj` (`cnpj`),
  ADD UNIQUE KEY `email` (`email`),
  ADD KEY `ID_endereco` (`ID_endereco`);

--
-- Índices de tabela `funcionario`
--
ALTER TABLE `funcionario`
  ADD PRIMARY KEY (`ID_funcionario`),
  ADD UNIQUE KEY `cpf` (`cpf`),
  ADD UNIQUE KEY `email` (`ID_funcionario`,`nome_funcionario`,`email_funcionario`),
  ADD KEY `ID_endereco` (`ID_endereco`);

--
-- Índices de tabela `item`
--
ALTER TABLE `item`
  ADD PRIMARY KEY (`ID_item`),
  ADD UNIQUE KEY `numero_serie` (`numero_serie`),
  ADD KEY `ID_fornecedor` (`ID_fornecedor`),
  ADD KEY `ID_area_desenvolvimento` (`ID_area_desenvolvimento`);

--
-- Índices de tabela `item_emprestimo`
--
ALTER TABLE `item_emprestimo`
  ADD PRIMARY KEY (`ID_item_emprestimo`),
  ADD UNIQUE KEY `ID_emprestimo` (`ID_emprestimo`,`ID_item`),
  ADD KEY `ID_item` (`ID_item`);

--
-- Índices de tabela `multa`
--
ALTER TABLE `multa`
  ADD PRIMARY KEY (`ID_multa`),
  ADD KEY `ID_emprestimo` (`ID_emprestimo`),
  ADD KEY `ID_dano` (`ID_dano`);

--
-- Índices de tabela `registro_dano`
--
ALTER TABLE `registro_dano`
  ADD PRIMARY KEY (`ID_dano`),
  ADD KEY `ID_item` (`ID_item`),
  ADD KEY `ID_emprestimo` (`ID_emprestimo`),
  ADD KEY `ID_funcionario_registro` (`ID_funcionario_registro`);

--
-- Índices de tabela `responsavel`
--
ALTER TABLE `responsavel`
  ADD PRIMARY KEY (`ID_responsavel`),
  ADD UNIQUE KEY `CPF` (`CPF`),
  ADD UNIQUE KEY `email` (`email`),
  ADD KEY `ID_endereco` (`ID_endereco`);

--
-- Índices de tabela `responsavel_telefone`
--
ALTER TABLE `responsavel_telefone`
  ADD PRIMARY KEY (`ID_responsavel`,`ID_telefone`),
  ADD KEY `ID_telefone` (`ID_telefone`);

--
-- AUTO_INCREMENT para tabelas despejadas
--

--
-- AUTO_INCREMENT de tabela `area_desenvolvimento`
--
ALTER TABLE `area_desenvolvimento`
  MODIFY `ID_area` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT de tabela `crianca`
--
ALTER TABLE `crianca`
  MODIFY `ID_crianca` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=22;

--
-- AUTO_INCREMENT de tabela `emprestimo`
--
ALTER TABLE `emprestimo`
  MODIFY `ID_emprestimo` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=64;

--
-- AUTO_INCREMENT de tabela `endereco`
--
ALTER TABLE `endereco`
  MODIFY `ID_endereco` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=36;

--
-- AUTO_INCREMENT de tabela `fornecedor`
--
ALTER TABLE `fornecedor`
  MODIFY `ID_fornecedor` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=15;

--
-- AUTO_INCREMENT de tabela `funcionario`
--
ALTER TABLE `funcionario`
  MODIFY `ID_funcionario` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=41;

--
-- AUTO_INCREMENT de tabela `item`
--
ALTER TABLE `item`
  MODIFY `ID_item` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=28;

--
-- AUTO_INCREMENT de tabela `item_emprestimo`
--
ALTER TABLE `item_emprestimo`
  MODIFY `ID_item_emprestimo` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=38;

--
-- AUTO_INCREMENT de tabela `multa`
--
ALTER TABLE `multa`
  MODIFY `ID_multa` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=35;

--
-- AUTO_INCREMENT de tabela `registro_dano`
--
ALTER TABLE `registro_dano`
  MODIFY `ID_dano` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT de tabela `responsavel`
--
ALTER TABLE `responsavel`
  MODIFY `ID_responsavel` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=28;

--
-- Restrições para tabelas despejadas
--

--
-- Restrições para tabelas `crianca`
--
ALTER TABLE `crianca`
  ADD CONSTRAINT `crianca_ibfk_1` FOREIGN KEY (`ID_responsavel`) REFERENCES `responsavel` (`ID_responsavel`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `crianca_ibfk_2` FOREIGN KEY (`ID_area_interesse`) REFERENCES `area_desenvolvimento` (`ID_area`) ON DELETE SET NULL ON UPDATE CASCADE;

--
-- Restrições para tabelas `emprestimo`
--
ALTER TABLE `emprestimo`
  ADD CONSTRAINT `emprestimo_ibfk_1` FOREIGN KEY (`ID_crianca`) REFERENCES `crianca` (`ID_crianca`) ON UPDATE CASCADE,
  ADD CONSTRAINT `emprestimo_ibfk_2` FOREIGN KEY (`ID_funcionario_entrega`) REFERENCES `funcionario` (`ID_funcionario`) ON UPDATE CASCADE,
  ADD CONSTRAINT `emprestimo_ibfk_3` FOREIGN KEY (`ID_funcionario_devolucao`) REFERENCES `funcionario` (`ID_funcionario`) ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_emprestimo_item` FOREIGN KEY (`ID_item`) REFERENCES `item` (`ID_item`) ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_emprestimos_responsavel` FOREIGN KEY (`ID_responsavel`) REFERENCES `responsavel` (`ID_responsavel`) ON UPDATE CASCADE;

--
-- Restrições para tabelas `fornecedor`
--
ALTER TABLE `fornecedor`
  ADD CONSTRAINT `fornecedor_ibfk_1` FOREIGN KEY (`ID_endereco`) REFERENCES `endereco` (`ID_endereco`) ON DELETE SET NULL ON UPDATE CASCADE;

--
-- Restrições para tabelas `funcionario`
--
ALTER TABLE `funcionario`
  ADD CONSTRAINT `fk_funcionario_endereco` FOREIGN KEY (`ID_endereco`) REFERENCES `endereco` (`ID_endereco`) ON DELETE SET NULL ON UPDATE CASCADE,
  ADD CONSTRAINT `funcionario_ibfk_1` FOREIGN KEY (`ID_endereco`) REFERENCES `endereco` (`ID_endereco`) ON DELETE SET NULL ON UPDATE CASCADE;

--
-- Restrições para tabelas `item`
--
ALTER TABLE `item`
  ADD CONSTRAINT `item_ibfk_1` FOREIGN KEY (`ID_fornecedor`) REFERENCES `fornecedor` (`ID_fornecedor`) ON UPDATE CASCADE,
  ADD CONSTRAINT `item_ibfk_2` FOREIGN KEY (`ID_area_desenvolvimento`) REFERENCES `area_desenvolvimento` (`ID_area`) ON DELETE SET NULL ON UPDATE CASCADE;

--
-- Restrições para tabelas `item_emprestimo`
--
ALTER TABLE `item_emprestimo`
  ADD CONSTRAINT `item_emprestimo_ibfk_1` FOREIGN KEY (`ID_emprestimo`) REFERENCES `emprestimo` (`ID_emprestimo`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `item_emprestimo_ibfk_2` FOREIGN KEY (`ID_item`) REFERENCES `item` (`ID_item`) ON UPDATE CASCADE;

--
-- Restrições para tabelas `multa`
--
ALTER TABLE `multa`
  ADD CONSTRAINT `multa_ibfk_1` FOREIGN KEY (`ID_emprestimo`) REFERENCES `emprestimo` (`ID_emprestimo`) ON DELETE SET NULL ON UPDATE CASCADE,
  ADD CONSTRAINT `multa_ibfk_2` FOREIGN KEY (`ID_dano`) REFERENCES `registro_dano` (`ID_dano`) ON DELETE SET NULL ON UPDATE CASCADE;

--
-- Restrições para tabelas `registro_dano`
--
ALTER TABLE `registro_dano`
  ADD CONSTRAINT `registro_dano_ibfk_1` FOREIGN KEY (`ID_item`) REFERENCES `item` (`ID_item`) ON UPDATE CASCADE,
  ADD CONSTRAINT `registro_dano_ibfk_2` FOREIGN KEY (`ID_emprestimo`) REFERENCES `emprestimo` (`ID_emprestimo`) ON DELETE SET NULL ON UPDATE CASCADE,
  ADD CONSTRAINT `registro_dano_ibfk_3` FOREIGN KEY (`ID_funcionario_registro`) REFERENCES `funcionario` (`ID_funcionario`) ON UPDATE CASCADE;

--
-- Restrições para tabelas `responsavel`
--
ALTER TABLE `responsavel`
  ADD CONSTRAINT `responsavel_ibfk_1` FOREIGN KEY (`ID_endereco`) REFERENCES `endereco` (`ID_endereco`) ON DELETE SET NULL ON UPDATE CASCADE;

--
-- Restrições para tabelas `responsavel_telefone`
--
ALTER TABLE `responsavel_telefone`
  ADD CONSTRAINT `responsavel_telefone_ibfk_1` FOREIGN KEY (`ID_responsavel`) REFERENCES `responsavel` (`ID_responsavel`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `responsavel_telefone_ibfk_2` FOREIGN KEY (`ID_telefone`) REFERENCES `telefone` (`ID_telefone`) ON DELETE CASCADE ON UPDATE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
