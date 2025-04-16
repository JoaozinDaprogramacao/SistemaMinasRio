<?php 
$tabela = 'produtos';
require_once("../../../conexao.php");

$nome = $_POST['nome'];
$categoria = $_POST['categoria'];
$obs = $_POST['obs'];
$id = $_POST['id'];


if($categoria == ""){
	$categoria = 0;
}

//validacao
$query = $pdo->query("SELECT * from $tabela where nome = '$nome'");
$res = $query->fetchAll(PDO::FETCH_ASSOC);
$id_reg = @$res[0]['id'];
if(@count($res) > 0 and $id != $id_reg){
	echo 'Nome já Cadastrado!';
	exit();
}

if($id == ""){
$query = $pdo->prepare("INSERT INTO $tabela SET nome = :nome, categoria = :categoria, obs = :obs, vendas = 0, ativo = 'Sim'");
	
}else{
$query = $pdo->prepare("UPDATE $tabela SET nome = :nome, categoria = :categoria, obs = :obs where id = '$id'");
}
$query->bindValue(":nome", "$nome");
$query->bindValue(":categoria", "$categoria");
$query->bindValue(":obs", "$obs");
$query->execute();

echo 'Salvo com Sucesso';


 ?>
