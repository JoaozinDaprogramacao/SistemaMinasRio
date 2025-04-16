<?php 
$tabela = 'itens_orc';
require_once("../../../conexao.php");

@session_start();
$id_usuario = $_SESSION['id'];

$quantidade = $_POST['quantidade'];
$quantidade = str_replace('.', '', $quantidade);
$quantidade = str_replace(',', '.', $quantidade);
$id_produto = $_POST['id_produto'];
$id_orc = $_POST['id'];

if($id_orc == ""){
	$id_orc = 0;
}

$query = $pdo->query("SELECT * from produtos where id = '$id_produto'");
$res = $query->fetchAll(PDO::FETCH_ASSOC);
$estoque = $res[0]['estoque'];
$valor = $res[0]['valor_venda'];
$tem_estoque = $res[0]['tem_estoque'];
$vendas = $res[0]['vendas'];
$unidade = $res[0]['unidade'];

if($valor <= 0){
	echo 'O valor do produto tem que ser maior que zero';
	exit();
}

if($quantidade > $estoque and $tem_estoque == 'Sim'){
	echo 'A quantidade de produtos não pode ser maior que a quantidade em estoque, por enquanto você tem '.$estoque.' itens deste produto no estoque!';
	exit();
}

$total = $quantidade * $valor;

$pdo->query("INSERT INTO $tabela SET produto = '$id_produto', valor = '$valor', quantidade = '$quantidade', total = '$total', id_orcamento = '$id_orc', funcionario = '$id_usuario'");

echo 'Inserido com Sucesso';




?>