-- ============================================================
-- Execute este script no banco 'sistemaminasrio' UMA VEZ
-- para habilitar a aba Vendas (diversos), Vendas Detalhes e
-- a Lista de Vendas. Pode ser executado de novo sem efeito.
-- ============================================================

-- 1. Cabeçalho da venda (o carrinho fica em itens_venda)
CREATE TABLE IF NOT EXISTS `vendas` (
  `id`                 INT(11) NOT NULL AUTO_INCREMENT,
  `cliente_id`         INT(11) NOT NULL,
  `vendedor_id`        INT(11) NOT NULL,
  `data_venda`         DATETIME NOT NULL,
  `subtotal`           DECIMAL(10,2) NOT NULL DEFAULT 0.00,
  `tipo_desconto`      VARCHAR(10) DEFAULT 'valor',
  `desconto`           DECIMAL(10,2) NOT NULL DEFAULT 0.00,
  `frete`              DECIMAL(10,2) NOT NULL DEFAULT 0.00,
  `valor_total`        DECIMAL(10,2) NOT NULL DEFAULT 0.00,
  `valor_pago`         DECIMAL(10,2) NOT NULL DEFAULT 0.00,
  `valor_restante`     DECIMAL(10,2) NOT NULL DEFAULT 0.00,
  `forma_pagamento_id` INT(11) DEFAULT NULL,
  `status_pagamento`   VARCHAR(30) NOT NULL DEFAULT 'Aguardando Pagamento',
  PRIMARY KEY (`id`),
  KEY `idx_cliente` (`cliente_id`),
  KEY `idx_data` (`data_venda`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 2. Liga itens, contas a receber, comissões e movimentação de material à venda
ALTER TABLE `itens_venda`
  ADD COLUMN IF NOT EXISTS `id_venda_real` INT(11) DEFAULT NULL,
  ADD INDEX IF NOT EXISTS `idx_id_venda_real` (`id_venda_real`);

ALTER TABLE `receber`
  ADD COLUMN IF NOT EXISTS `id_venda_real` INT(11) DEFAULT NULL,
  ADD INDEX IF NOT EXISTS `idx_id_venda_real` (`id_venda_real`);

ALTER TABLE `pagar`
  ADD COLUMN IF NOT EXISTS `id_venda_real` INT(11) DEFAULT NULL;

ALTER TABLE `detalhes_materiais`
  ADD COLUMN IF NOT EXISTS `id_venda_real` INT(11) DEFAULT NULL;

-- 3. Preço unitário era DECIMAL(5,2): qualquer item a partir de R$ 1.000 derrubava a venda
ALTER TABLE `detalhes_materiais`
  MODIFY `preco_unidade` DECIMAL(10,2) DEFAULT NULL,
  MODIFY `valor_venda`   DECIMAL(10,2) DEFAULT NULL;

ALTER TABLE `itens_venda`
  MODIFY `valor` DECIMAL(10,2) DEFAULT 0.00,
  MODIFY `total` DECIMAL(10,2) DEFAULT 0.00;
