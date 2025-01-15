<?php 
$tabela = 'bancos';
require_once("../../../conexao.php");

@session_start();
$id_usuario = @$_SESSION['id'];

$descricao = $_POST['descricao'];
$id = $_POST['id'];

if($descricao == ""){
	$descricao = "Sem descricao.";
}



if($id == ""){
$query = $pdo->prepare("INSERT INTO $tabela SET descricao = :descricao, id_usuario = '$id_usuario'");
	
}else{
$query = $pdo->prepare("UPDATE $tabela SET descricao = :descricao where id = '$id'");
}


$query->bindValue(":descricao", "$descricao");
$query->execute();
$ultimo_id = $pdo->lastInsertId();


echo 'Salvo com Sucesso';
 ?>