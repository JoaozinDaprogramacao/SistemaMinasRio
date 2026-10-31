<?php
$tabela = 'produtos';
require_once("../../../conexao.php");
require_once("../../verificar.php");

$id = intval($_POST['id'] ?? 0);

$query = $pdo->prepare("SELECT nome FROM $tabela where id = :id");
$query->execute([':id' => $id]);
$nome = $query->fetchColumn();
if($nome === false){
	echo 'Produto não encontrado!';
	exit();
}

// Produto usado em romaneio ou orçamento não pode sumir: as linhas ficariam sem produto.
// Para tirar de uso, desative o produto.
$usos = [
	'romaneios de venda' => "SELECT COUNT(*) FROM linha_produto WHERE variedade = :id",
	'romaneios de compra' => "SELECT COUNT(*) FROM linha_produto_compra WHERE variedade = :id",
	'orçamentos' => "SELECT COUNT(*) FROM itens_orc WHERE produto = :id",
];
$encontrados = [];
foreach($usos as $onde => $sql){
	$query = $pdo->prepare($sql);
	$query->execute([':id' => $id]);
	$total = (int) $query->fetchColumn();
	if($total > 0){
		$encontrados[] = "$total em $onde";
	}
}
if($encontrados){
	echo "$nome não pode ser excluído porque já foi usado (" . implode(', ', $encontrados) . '). Desative o produto em vez de excluir.';
	exit();
}

$pdo->prepare("DELETE FROM $tabela WHERE id = :id")->execute([':id' => $id]);
echo 'Excluído com Sucesso';
