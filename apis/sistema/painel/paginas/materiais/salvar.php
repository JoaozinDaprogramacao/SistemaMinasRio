<?php
$tabela = 'materiais';
require_once("../../../conexao.php");

$nome = $_POST['nome'];
$estoque = $_POST['estoque'];
$estoque_minimo = $_POST['estoque_minimo'];
$id = $_POST['id'];

//validacao
$query = $pdo->query("SELECT * from $tabela where nome = '$nome'");
$res = $query->fetchAll(PDO::FETCH_ASSOC);
$id_reg = @$res[0]['id'];
if (@count($res) > 0 and $id != $id_reg) {
	echo 'Nome já Cadastrado!';
	exit();
}



$tem_estoque = "Sim";

if ($estoque <= 0) {
	$tem_estoque = "Não";
}

if ($id == "") {
	$query = $pdo->prepare("INSERT INTO $tabela SET nome = :nome, estoque = :estoque, estoque_minimo = :estoque_minimo, tem_estoque = :tem_estoque");
} else {
	$query = $pdo->prepare("UPDATE $tabela SET nome = :nome, estoque = :estoque, estoque_minimo = :estoque_minimo, tem_estoque = :tem_estoque where id = '$id'");
}

$query->bindValue(":nome", "$nome");
$query->bindValue(":estoque", "$estoque");
$query->bindValue(":estoque_minimo", "$estoque_minimo");
$query->bindValue(":tem_estoque", "$tem_estoque");
$query->execute();


echo 'Salvo com Sucesso';
