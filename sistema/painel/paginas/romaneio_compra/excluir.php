<?php 
$tabela = 'romaneio_compra';
require_once("../../../conexao.php");

$id = $_POST['id'];

// 1) Remove registros dos filhos em ordem
$filhos = [
    // linhas de produto
    "DELETE FROM linha_produto_compra         WHERE id_romaneio      = ?", 
    // comissões
    "DELETE FROM linha_comissao               WHERE id_romaneio      = ?", 
    // observações
    "DELETE FROM linha_observacao             WHERE id_romaneio      = ?", 
    // pagamentos
    "DELETE FROM pagar                        WHERE id_romaneio      = ?", 
    // vendas que referenciam este romaneio de compra
    "DELETE FROM romaneio_venda_compra        WHERE id_romaneio_compra = ?"
];

foreach ($filhos as $sql) {
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$id]);
}

// 2) Agora apaga o romaneio
$del = $pdo->prepare("DELETE FROM {$tabela} WHERE id = ?");
$del->execute([$id]);

echo 'Excluído com Sucesso';
