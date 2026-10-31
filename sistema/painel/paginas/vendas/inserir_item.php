<?php
require_once("../../../conexao.php");
require_once("funcoes_venda.php");

@session_start();
$id_usuario = $_SESSION['id'];

$quantidade = venda_valor_br($_POST['quantidade'] ?? '');
$id_material = intval($_POST['id_material'] ?? 0);

if ($quantidade <= 0) {
	echo 'A quantidade deve ser maior que zero!';
	exit();
}

$query = $pdo->prepare("SELECT * FROM materiais WHERE id = :id");
$query->execute([':id' => $id_material]);
$material = $query->fetch(PDO::FETCH_ASSOC);
if (!$material) {
	echo 'Material não encontrado!';
	exit();
}

// Se o material já está no carrinho, soma na mesma linha
$query = $pdo->prepare("SELECT * FROM itens_venda WHERE funcionario = :f AND id_venda = 0 AND material = :m LIMIT 1");
$query->execute([':f' => $id_usuario, ':m' => $id_material]);
$item = $query->fetch(PDO::FETCH_ASSOC);
$nova_quantidade = $quantidade + ($item ? $item['quantidade'] : 0);

// O estoque só é abatido ao fechar a venda; aqui apenas avisa
if ($material['tem_estoque'] == 'Sim') {
	$disponivel = $material['estoque'] + venda_qtd_em_edicao($pdo, @$_SESSION['id_venda_em_edicao'], $id_material);
	if ($nova_quantidade > $disponivel) {
		echo 'A quantidade não pode ser maior que o estoque! Disponível de ' . $material['nome'] . ': ' . $disponivel;
		exit();
	}
}

if ($item) {
	$pdo->prepare("UPDATE itens_venda SET quantidade = :q, total = valor * :q2 WHERE id = :id")
		->execute([':q' => $nova_quantidade, ':q2' => $nova_quantidade, ':id' => $item['id']]);
} else {
	$pdo->prepare("INSERT INTO itens_venda SET material = :m, quantidade = :q, valor = 0, total = 0, id_venda = 0, funcionario = :f")
		->execute([':m' => $id_material, ':q' => $quantidade, ':f' => $id_usuario]);
}

echo 'Inserido com Sucesso';
