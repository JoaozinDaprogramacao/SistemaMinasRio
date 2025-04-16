<?php 
$tabela = 'itens_compra';
require_once("../../../conexao.php");

$id = $_POST['id'];
$quantidade = $_POST['quantidade'];

$query = $pdo->query("SELECT * from $tabela where id = '$id'");
$res = $query->fetchAll(PDO::FETCH_ASSOC);
$id_material = $res[0]['material'];
$valor = $res[0]['valor'];

$query = $pdo->query("SELECT * from materiais where id = '$id_material'");
$res = $query->fetchAll(PDO::FETCH_ASSOC);
$estoque = $res[0]['estoque'];
$tem_estoque = $res[0]['tem_estoque'];
$compras = $res[0]['compras'];

$nova_quant = $quantidade - 1;
$novo_total = $valor * $nova_quant;

if($quantidade == 1){
	$pdo->query("DELETE FROM $tabela WHERE id = '$id' ");
}else{
	$pdo->query("UPDATE $tabela SET quantidade = '$nova_quant', total = '$novo_total' WHERE id = '$id' ");
}

echo "Atualizado com Sucesso";

if($tem_estoque == 'Sim'){
	$novo_estoque = $estoque - 1;
	$compras = $compras - 1;
	//adicionar os produtos na tabela produtos
	$pdo->query("UPDATE materiais SET estoque = '$novo_estoque', compras = '$compras' WHERE id = '$id_material'");
}

?>