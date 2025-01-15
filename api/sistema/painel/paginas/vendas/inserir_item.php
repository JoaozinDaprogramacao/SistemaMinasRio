<?php 
$tabela = 'itens_venda';
require_once("../../../conexao.php");

@session_start();
$id_usuario = $_SESSION['id'];

$quantidade = $_POST['quantidade'];
$quantidade = str_replace('.', '', $quantidade);
$quantidade = str_replace(',', '.', $quantidade);
$id_material = $_POST['id_material'];

$query = $pdo->query("SELECT * from materiais where id = '$id_material'");
$res = $query->fetchAll(PDO::FETCH_ASSOC);
$tem_estoque = $res[0]['tem_estoque'];
$vendas = $res[0]['vendas'];
$estoque = $res[0]['estoque'];

if($quantidade > $estoque and $tem_estoque == 'Sim'){
	echo 'A quantidade de produtos não pode ser maior que a quantidade em estoque, por enquanto você tem '.$estoque.' itens deste produto no estoque!';
	exit();
}

$pdo->query("INSERT INTO itens_venda SET material = '$id_material', quantidade = '$quantidade', id_venda = 0, funcionario = '$id_usuario'");

echo 'Inserido com Sucesso';

if($tem_estoque == 'Sim'){
	$novo_estoque = $estoque - $quantidade;

	
	$vendas = $vendas + $quantidade;

	
	//adicionar os produtos na tabela produtos
	$pdo->query("UPDATE materiais SET estoque = '$novo_estoque', vendas = '$vendas' WHERE id = '$id_material'"); 
}


?>