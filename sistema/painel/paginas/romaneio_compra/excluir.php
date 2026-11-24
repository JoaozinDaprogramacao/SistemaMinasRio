<?php 
$tabela = 'romaneio_compra';
require_once("../../../conexao.php");
require_once(__DIR__ . "/../../seguranca.php");
require_once(__DIR__ . "/../../funcoes/financeiro.php");
seguranca_exigir_acesso(['produtos']);

$id = (int) $_POST['id'];

try {
$pdo->beginTransaction();
// Contas a pagar do romaneio: estorna pagamentos do extrato antes de apagar
$st_pag = $pdo->prepare("SELECT id FROM pagar WHERE id_romaneio = ? OR (id_ref = ? AND referencia = 'romaneio_compra')");
$st_pag->execute([$id, $id]);
foreach ($st_pag->fetchAll(PDO::FETCH_COLUMN) as $id_pagar) {
    fin_excluir_titulo($pdo, 'pagar', $id_pagar, 'romaneio de compra #' . $id . ' excluído');
}

// 1) Remove registros dos filhos em ordem
$filhos = [
    // linhas de produto
    "DELETE FROM linha_produto_compra         WHERE id_romaneio         = ?", 
    // comissões
    "DELETE FROM linha_comissao               WHERE id_romaneio         = ?", 
    // observações
    "DELETE FROM linha_observacao             WHERE id_romaneio         = ?", 
    // vendas que referenciam este romaneio de compra
    "DELETE FROM romaneio_venda_compra        WHERE id_romaneio_compra  = ?",
    // contas a receber vinculadas a este romaneio
];

foreach ($filhos as $sql) {
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$id]);
}

// 2) Agora apaga o romaneio
$del = $pdo->prepare("DELETE FROM {$tabela} WHERE id = ?");
$del->execute([$id]);

$pdo->commit();
echo 'Excluído com Sucesso';
} catch (Throwable $e) {
    if ($pdo->inTransaction()) $pdo->rollBack();
    echo 'Erro ao excluir: ' . $e->getMessage();
}
