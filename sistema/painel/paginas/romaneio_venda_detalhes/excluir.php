<?php
require_once("../../../conexao.php");

$id = $_POST['id'];

try {
    $pdo->beginTransaction();

    // --- NOVO BLOCO: Atualizar romaneio_compra antes de deletar ---
    // Atualiza o campo 'usado' para 0 em todos os romaneios de compra 
    // que estão vinculados a este romaneio de venda.
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
    // -------------------------------------------------------------

    // Excluir baldeios relacionados
    $pdo->prepare("DELETE FROM baldeio WHERE id_romaneio = ?")->execute([$id]);

    // Excluir linhas relacionadas
    $pdo->prepare("DELETE FROM linha_produto WHERE id_romaneio = ?")->execute([$id]);
    $pdo->prepare("DELETE FROM linha_comissao WHERE id_romaneio = ?")->execute([$id]);
    $pdo->prepare("DELETE FROM linha_observacao WHERE id_romaneio = ?")->execute([$id]);

    // Excluir relacionamentos com romaneios de compra
    // (Agora podemos excluir, pois já atualizamos a tabela romaneio_compra acima)
    $pdo->prepare("DELETE FROM romaneio_venda_compra WHERE id_romaneio_venda = ?")->execute([$id]);

    // Excluir o romaneio de venda
    $pdo->prepare("DELETE FROM romaneio_venda WHERE id = ?")->execute([$id]);

    $pdo->commit();
    echo 'Excluído com Sucesso';
} catch (Exception $e) {
    $pdo->rollBack();
    echo 'Erro ao excluir: ' . $e->getMessage();
}
