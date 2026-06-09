-- Tabela de categorias para contas a pagar
CREATE TABLE IF NOT EXISTS `categorias_pagar` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `nome` VARCHAR(100) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Categorias padrão (INSERT IGNORE ignora se já existir)
INSERT IGNORE INTO `categorias_pagar` (`id`, `nome`) VALUES
(1,  'Salário'),
(2,  'Adiantamento'),
(3,  '13° Salário / Férias'),
(4,  'FGTS / INSS'),
(5,  'Fornecedor / Compra'),
(6,  'Aluguel'),
(7,  'Água / Energia / Gás'),
(8,  'Telefone / Internet'),
(9,  'Imposto / Taxa'),
(10, 'Manutenção / Reparo'),
(11, 'Outros');

-- Adiciona coluna categoria na tabela pagar
ALTER TABLE `pagar`
  ADD COLUMN IF NOT EXISTS `categoria_pagar` INT(11) DEFAULT NULL;
