<?php
// Garante que a categoria "Romaneio" sempre exista e fica marcada como protegida
// (não pode ser excluída/renomeada), pois o filtro de Contas a Pagar depende dela
// para localizar os lançamentos gerados automaticamente pelos romaneios de compra.
function garantir_categoria_romaneio($pdo)
{
    $pdo->query("CREATE TABLE IF NOT EXISTS `categorias_pagar` (
        `id` INT(11) NOT NULL AUTO_INCREMENT,
        `nome` VARCHAR(100) NOT NULL,
        `protegida` TINYINT(1) NOT NULL DEFAULT 0,
        PRIMARY KEY (`id`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

    $col = $pdo->query("SELECT COUNT(*) as n FROM INFORMATION_SCHEMA.COLUMNS
        WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'categorias_pagar' AND COLUMN_NAME = 'protegida'")->fetch(PDO::FETCH_ASSOC);
    if (!$col || $col['n'] == 0) {
        $pdo->query("ALTER TABLE `categorias_pagar` ADD COLUMN `protegida` TINYINT(1) NOT NULL DEFAULT 0");
    }

    $row = $pdo->query("SELECT id, protegida FROM categorias_pagar WHERE nome = 'Romaneio'")->fetch(PDO::FETCH_ASSOC);
    if ($row) {
        if (!$row['protegida']) {
            $pdo->query("UPDATE categorias_pagar SET protegida = 1 WHERE id = '{$row['id']}'");
        }
        return (int) $row['id'];
    }

    $pdo->prepare("INSERT INTO categorias_pagar (nome, protegida) VALUES ('Romaneio', 1)")->execute();
    return (int) $pdo->lastInsertId();
}
