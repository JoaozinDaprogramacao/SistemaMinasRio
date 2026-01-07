<?php
require_once("../../../conexao.php");

$id = $_POST['id'];

try {
    $pdo->beginTransaction();

    // =============================================================
    // 1. ESTORNO DAS CATEGORIAS (Subtrair quantidades vendidas)
    // =============================================================
    // Buscamos os produtos deste romaneio e suas respectivas categorias antes de deletar
    $query_itens = $pdo->prepare("
        SELECT lp.quant, p.categoria 
        FROM linha_produto lp 
        INNER JOIN produtos p ON lp.variedade = p.id 
        WHERE lp.id_romaneio = ?
    ");
    $query_itens->execute([$id]);
    $produtos_venda = $query_itens->fetchAll(PDO::FETCH_ASSOC);

    foreach ($produtos_venda as $item) {
        if (!empty($item['categoria']) && $item['quant'] > 0) {
            // Subtrai a quantidade da categoria correspondente
            $sql_cat = "UPDATE categorias SET vendas = vendas - :qtd WHERE id = :id_cat";
            $query_cat = $pdo->prepare($sql_cat);
            $query_cat->bindValue(":qtd", $item['quant']);
            $query_cat->bindValue(":id_cat", $item['categoria']);
            $query_cat->execute();
        }
    }

    // =============================================================
    // 2. ATUALIZAR ROMANEIO_COMPRA (Liberar para nova venda)
    // =============================================================
    // Atualiza o campo 'usado' para 0 nos romaneios de compra vinculados
    $stmtUpdate = $pdo->prepare("
        UPDATE romaneio_compra 
        SET usado = 0 
        WHERE id IN (
            SELECT id_romaneio_compra 
            FROM romaneio_venda_compra 
            WHERE id_romaneio_venda = ?
        )
    ");
    $stmtUpdate->execute([$id]);

    // =============================================================
    // 3. EXCLUSÃO DOS DADOS RELACIONADOS
    // =============================================================

    // Excluir baldeios relacionados
    $pdo->prepare("DELETE FROM baldeio WHERE id_romaneio = ?")->execute([$id]);

    // Excluir linhas relacionadas (produtos, comissões, observações)
    $pdo->prepare("DELETE FROM linha_produto WHERE id_romaneio = ?")->execute([$id]);
    $pdo->prepare("DELETE FROM linha_comissao WHERE id_romaneio = ?")->execute([$id]);
    $pdo->prepare("DELETE FROM linha_observacao WHERE id_romaneio = ?")->execute([$id]);

    // Excluir relacionamentos com romaneios de compra
    $pdo->prepare("DELETE FROM romaneio_venda_compra WHERE id_romaneio_venda = ?")->execute([$id]);

    // Excluir o lançamento no Contas a Receber vinculado a este romaneio
    $pdo->prepare("DELETE FROM receber WHERE id_ref = ? AND referencia = 'Romaneio Venda'")->execute([$id]);

    // Excluir o cabeçalho do romaneio de venda
    $pdo->prepare("DELETE FROM romaneio_venda WHERE id = ?")->execute([$id]);

    // Finalizar transação
    $pdo->commit();
    echo 'Excluído com Sucesso';
} catch (Exception $e) {
    // Caso ocorra qualquer erro, desfaz todas as alterações no banco
    if (isset($pdo) && $pdo->inTransaction()) {
        $pdo->rollBack();
    }
    echo 'Erro ao excluir: ' . $e->getMessage();
}
