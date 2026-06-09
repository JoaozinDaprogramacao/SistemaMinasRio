-- ============================================================
-- Execute este script no banco 'sistemaminasrio' UMA VEZ
-- para habilitar o novo fluxo multi-pagamentos do Contas a Pagar
-- ============================================================

-- 1. Tabela auxiliar de pagamentos (espelho de receber_pagamentos)
CREATE TABLE IF NOT EXISTS `pagar_pagamentos` (
  `id`               INT(11) NOT NULL AUTO_INCREMENT,
  `id_pagar`         INT(11) NOT NULL,
  `valor`            DECIMAL(10,2) NOT NULL DEFAULT 0.00,
  `data_pgto`        DATE DEFAULT NULL,
  `forma_pgto`       INT(11) DEFAULT NULL,
  `banco`            INT(11) DEFAULT NULL,
  `numero_operacao`  VARCHAR(100) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_id_pagar` (`id_pagar`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 2. Coluna valor_restante na tabela pagar (se ainda não existir)
ALTER TABLE `pagar`
  ADD COLUMN IF NOT EXISTS `valor_restante` DECIMAL(10,2) DEFAULT NULL;
