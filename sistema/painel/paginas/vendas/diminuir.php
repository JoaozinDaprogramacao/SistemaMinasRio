<?php
require_once("../../../conexao.php");

@session_start();
$id_usuario = $_SESSION['id'];
$id = intval($_POST['id'] ?? 0);

$query = $pdo->prepare("SELECT * FROM itens_venda WHERE id = :id AND funcionario = :f AND id_venda = 0");
$query->execute([':id' => $id, ':f' => $id_usuario]);
$item = $query->fetch(PDO::FETCH_ASSOC);
if (!$item) {
	echo 'Item não encontrado no carrinho!';
	exit();
}

$nova_quant = $item['quantidade'] - 1;

if ($nova_quant <= 0) {
	$pdo->prepare("DELETE FROM itens_venda WHERE id = :id")->execute([':id' => $id]);
	echo 'Excluído com Sucesso';
} else {
	$pdo->prepare("UPDATE itens_venda SET quantidade = :q, total = valor * :q2 WHERE id = :id")
		->execute([':q' => $nova_quant, ':q2' => $nova_quant, ':id' => $id]);
	echo 'Atualizado com Sucesso';
}
