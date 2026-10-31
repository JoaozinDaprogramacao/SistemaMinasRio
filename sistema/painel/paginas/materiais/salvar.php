<?php
$tabela = 'materiais';
require_once("../../../conexao.php");
require_once("../../verificar.php");

$nome = trim($_POST['nome'] ?? '');
$estoque = trim($_POST['estoque'] ?? '');
$estoque_minimo = trim($_POST['estoque_minimo'] ?? '');
$tem_estoque = ($_POST['tem_estoque'] ?? 'Sim') == 'Não' ? 'Não' : 'Sim';
$id = intval($_POST['id'] ?? 0);

if ($nome == '') {
	echo 'Informe o nome!';
	exit();
}
if (!preg_match('/^-?\d+$/', $estoque) or !preg_match('/^\d+$/', $estoque_minimo)) {
	echo 'Informe o estoque e o estoque mínimo em números inteiros!';
	exit();
}

//validacao
$query = $pdo->prepare("SELECT id from $tabela where nome = :nome and id <> :id");
$query->execute([':nome' => $nome, ':id' => $id]);
if ($query->fetchColumn()) {
	echo 'Nome já Cadastrado!';
	exit();
}

// tem_estoque diz se vendas/compras/romaneios movimentam o estoque deste material;
// vem do formulário e não depende do estoque estar zerado
if ($id == 0) {
	$query = $pdo->prepare("INSERT INTO $tabela SET nome = :nome, estoque = :estoque, estoque_minimo = :estoque_minimo, tem_estoque = :tem_estoque, vendas = 0");
} else {
	$query = $pdo->prepare("UPDATE $tabela SET nome = :nome, estoque = :estoque, estoque_minimo = :estoque_minimo, tem_estoque = :tem_estoque where id = :id");
	$query->bindValue(":id", $id);
}

$query->bindValue(":nome", $nome);
$query->bindValue(":estoque", (int) $estoque);
$query->bindValue(":estoque_minimo", (int) $estoque_minimo);
$query->bindValue(":tem_estoque", $tem_estoque);
$query->execute();


echo 'Salvo com Sucesso';
