<?php
require_once("../../../conexao.php");
require_once("funcoes_venda.php");

@session_start();
$id_usuario = $_SESSION['id'];
$id = intval($_POST['id'] ?? 0);

$query = $pdo->prepare("SELECT iv.*, m.nome, m.tem_estoque, m.estoque FROM itens_venda iv
	JOIN materiais m ON m.id = iv.material
	WHERE iv.id = :id AND iv.funcionario = :f AND iv.id_venda = 0");
$query->execute([':id' => $id, ':f' => $id_usuario]);
$item = $query->fetch(PDO::FETCH_ASSOC);
if (!$item) {
	echo 'Item não encontrado no carrinho!';
	exit();
}

$nova_quant = $item['quantidade'] + 1;

// O estoque só é abatido ao fechar a venda; aqui apenas avisa
if ($item['tem_estoque'] == 'Sim') {
	$disponivel = $item['estoque'] + venda_qtd_em_edicao($pdo, @$_SESSION['id_venda_em_edicao'], $item['material']);
	if ($nova_quant > $disponivel) {
		echo 'A quantidade vendida não pode ser maior do que o estoque! Disponível de ' . $item['nome'] . ': ' . $disponivel;
		exit();
	}
}

$pdo->prepare("UPDATE itens_venda SET quantidade = :q, total = valor * :q2 WHERE id = :id")
	->execute([':q' => $nova_quant, ':q2' => $nova_quant, ':id' => $id]);

echo 'Atualizado com Sucesso';
