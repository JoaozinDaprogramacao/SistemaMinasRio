<?php 
$tabela = 'itens_orc';
require_once("../../../conexao.php");

$id = $_POST['id'];
$quantidade = $_POST['quantidade'];

$query = $pdo->query("SELECT * from $tabela where id = '$id'");
$res = $query->fetchAll(PDO::FETCH_ASSOC);
$id_produto = $res[0]['produto'];
$valor = $res[0]['valor'];

$query = $pdo->query("SELECT * from produtos where id = '$id_produto'");
$res = $query->fetchAll(PDO::FETCH_ASSOC);
$estoque = $res[0]['estoque'];
$tem_estoque = $res[0]['tem_estoque'];
$vendas = $res[0]['vendas'];

$nova_quant = $quantidade - 1;
$novo_total = $valor * $nova_quant;

if($quantidade == 1){
	$pdo->query("DELETE FROM $tabela WHERE id = '$id' ");
}else{
	$pdo->query("UPDATE $tabela SET quantidade = '$nova_quant', total = '$novo_total' WHERE id = '$id' ");
}

echo 'Excluído com Sucesso';



?>