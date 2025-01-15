<?php
$tabela = 'materials';
$tabela_detalhes = 'detalhes_materiais';
require_once("../../../conexao.php");

$nome = $_POST['nome'];
$id = $_POST['id'];


//validacao
$query = $pdo->query("SELECT * from $tabela where nome = '$nome'");
$res = $query->fetchAll(PDO::FETCH_ASSOC);
$id_reg = @$res[0]['id'];
if (@count($res) > 0 and $id != $id_reg) {
	echo 'Nome já Cadastrado!';
	exit();
}

if ($id == "") {
	$query = $pdo->prepare("INSERT INTO $tabela SET nome = :nome");

	$query->bindValue(":nome", "$nome");
	$query->execute();

	$novo_id = $pdo->lastInsertId();


	$query2 = $pdo->prepare("INSERT INTO $tabela_detalhes SET material_id = :id");

	$query2->bindValue(":id", "$novo_id");
	$query2->execute();
} else {
	$query = $pdo->prepare("UPDATE $tabela SET nome = :nome where id = '$id'");

	$query->bindValue(":nome", "$nome");
	$query->execute();
}


echo 'Salvo com Sucesso';
