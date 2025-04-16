<?php 
require_once("../../../conexao.php");

$id = $_POST['id'];

try {
    $pdo->beginTransaction();

    // Excluir baldeios relacionados
    $pdo->prepare("DELETE FROM baldeio WHERE id_romaneio = ?")->execute([$id]);

    // Excluir linhas relacionadas
    $pdo->prepare("DELETE FROM linha_produto WHERE id_romaneio = ?")->execute([$id]);
    $pdo->prepare("DELETE FROM linha_comissao WHERE id_romaneio = ?")->execute([$id]);
    $pdo->prepare("DELETE FROM linha_observacao WHERE id_romaneio = ?")->execute([$id]);
    
    // Excluir relacionamentos com romaneios de compra
    $pdo->prepare("DELETE FROM romaneio_venda_compra WHERE id_romaneio_venda = ?")->execute([$id]);
    
    // Excluir o romaneio de venda
    $pdo->prepare("DELETE FROM romaneio_venda WHERE id = ?")->execute([$id]);

    $pdo->commit();
    echo 'Excluído com Sucesso';
} catch (Exception $e) {
    $pdo->rollBack();
    echo 'Erro ao excluir: ' . $e->getMessage();
}
?>