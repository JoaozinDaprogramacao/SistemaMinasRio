<?php 
$tabela = 'itens_compra';
require_once("../../../conexao.php");

$id = $_POST['id'];

$query = $pdo->query("SELECT * from $tabela where id = '$id'");
$res = $query->fetchAll(PDO::FETCH_ASSOC);
$id_material = $res[0]['material'];
$quantidade = $res[0]['quantidade'];

$query = $pdo->query("SELECT * from materiais where id = '$id_material'");
$res = $query->fetchAll(PDO::FETCH_ASSOC);
$estoque = $res[0]['estoque'];
$tem_estoque = $res[0]['tem_estoque'];
$compras = $res[0]['compras'];

$pdo->query("DELETE FROM $tabela WHERE id = '$id' ");
echo "Excluído com Sucesso";

if($tem_estoque == 'Sim'){
	$novo_estoque = $estoque - $quantidade;
	$compras = $compras - $quantidade;
	
	//adicionar os produtos na tabela produtos
	$pdo->query("UPDATE materiais SET estoque = '$novo_estoque', compras = '$compras' WHERE id = '$id_material'"); 
}
?>