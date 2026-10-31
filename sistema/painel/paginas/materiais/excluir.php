<?php
$tabela = 'materiais';
require_once("../../../conexao.php");
require_once("../../verificar.php");

$id = intval($_POST['id'] ?? 0);

$query = $pdo->prepare("SELECT nome FROM $tabela WHERE id = :id");
$query->execute([':id' => $id]);
$nome = $query->fetchColumn();
if ($nome === false) {
	echo 'Material não encontrado!';
	exit();
}

// Material com histórico não pode sumir: vendas, compras, romaneios e
// movimentações de estoque ficariam apontando para um material inexistente
$usos = [
	'vendas' => "SELECT COUNT(*) FROM itens_venda WHERE material = :id",
	'compras' => "SELECT COUNT(*) FROM itens_compra WHERE material = :id",
	'romaneios' => "SELECT COUNT(*) FROM linha_observacao WHERE descricao = :id",
	'entradas de estoque' => "SELECT COUNT(*) FROM entradas WHERE material = :id",
	'saídas de estoque' => "SELECT COUNT(*) FROM saidas WHERE material = :id",
];
$encontrados = [];
foreach ($usos as $onde => $sql) {
	$query = $pdo->prepare($sql);
	$query->execute([':id' => $id]);
	$total = (int) $query->fetchColumn();
	if ($total > 0) {
		$encontrados[] = "$total em $onde";
	}
}
if ($encontrados) {
	echo "$nome não pode ser excluído porque já foi usado (" . implode(', ', $encontrados) . ').';
	exit();
}

$pdo->prepare("DELETE FROM detalhes_materiais WHERE material_id = :id")->execute([':id' => $id]);
$pdo->prepare("DELETE FROM $tabela WHERE id = :id")->execute([':id' => $id]);
echo 'Excluído com Sucesso';
