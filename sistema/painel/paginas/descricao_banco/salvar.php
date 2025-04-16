<?php 
require_once("../../../conexao.php");
$tabela = 'descricao_banco';

$id = $_POST['id'];
$descricao = $_POST['descricao'];


//validar nome
$query = $pdo->query("SELECT * from $tabela where descricao = '$descricao'");
$res = $query->fetchAll(PDO::FETCH_ASSOC);
if(@count($res) > 0 and $id != $res[0]['id']){
	echo 'Descrição já Cadastrado, escolha outro!!';
	exit();
}


if($id == ""){
	$query = $pdo->prepare("INSERT INTO $tabela SET descricao = :descricao");
}else{
	$query = $pdo->prepare("UPDATE $tabela SET descricao = :descricao WHERE id = '$id'");
}

$query->bindValue(":descricao", "$descricao");
$query->execute();

echo 'Salvo com Sucesso';
 ?>