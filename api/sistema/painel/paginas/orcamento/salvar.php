<?php 
$tabela = 'orcamentos';
require_once("../../../conexao.php");

$data_atual = date('Y-m-d');

@session_start();
$id_usuario = $_SESSION['id'];


$desconto = $_POST['desconto'];
$cliente = $_POST['cliente'];
$forma_pgto = $_POST['forma_pgto'];
$usuario = $_POST['usuario'];
$frete = $_POST['frete'];
$tipo = $_POST['tipo'];
$dias_validade = $_POST['dias_validade'];
$tipo_desconto = $_POST['tipo_desconto'];
$subtotal_venda = $_POST['subtotal_venda'];
$obs = $_POST['obs'];
$id = $_POST['id'];

if($id == ""){
	$id = 0;
}


if($dias_validade == ""){
	$dias_validade = 0;
}

if($frete == ""){
	$frete = 0;
}

if($desconto == ""){
	$desconto = 0;
}

$total_v = 0;
//buscar o total da venda
$query = $pdo->query("SELECT * from itens_orc where funcionario = '$id_usuario' and id_orcamento = '$id' order by id asc");
$res = $query->fetchAll(PDO::FETCH_ASSOC);
$linhas = @count($res);
if($linhas > 0){
	for($i=0; $i<$linhas; $i++){	
		$total_das_vendas = $res[$i]['total'];
		$total_v += $total_das_vendas;
	}
}

if($tipo_desconto == '%'){
	if($desconto > 0 and $total_v > 0){
		$total_final = -($total_v * $desconto / 100);
	}else{
		$total_final = 0;
	}
	
}else{
	$total_final = -$desconto;
}

$query = $pdo->query("SELECT * from itens_orc where funcionario = '$id_usuario' and id_orcamento = '$id' order by id asc");
$res = $query->fetchAll(PDO::FETCH_ASSOC);
$linhas = @count($res);
if($linhas > 0){
	for($i=0; $i<$linhas; $i++){
	
	$produto = $res[$i]['produto'];
	$valor = $res[$i]['valor'];
	$quantidade = $res[$i]['quantidade'];
	$total = $res[$i]['total'];

	$total_final += $total;
	$total_finalF = number_format($total_final, 2, ',', '.');
	$valorF = number_format($valor, 2, ',', '.');
	$totalF = number_format($total, 2, ',', '.');
	

}

}

if($total_final <= 0){
	//echo 'O Orçamento está sem valor';
	//exit();
}

if($id == 0){
$pdo->query("INSERT INTO orcamentos SET valor = '$subtotal_venda', data = curDate(), dias_validade = '$dias_validade', usuario = '$usuario', cliente = '$cliente', hora = curTime(), forma_pgto = '$forma_pgto', desconto = '$desconto', frete = '$frete', tipo_desconto = '$tipo_desconto', subtotal = '$subtotal_venda', tipo = '$tipo', obs = '$obs', status = 'Pendente'");
	$id_orcamento = $pdo->lastInsertId();

	$pdo->query("UPDATE itens_orc SET id_orcamento = '$id_orcamento' WHERE id_orcamento = 0 and funcionario = '$id_usuario'");

	echo 'Salvo com Sucesso-'.$id_orcamento;
}else{
	
	$pdo->query("UPDATE orcamentos SET valor = '$subtotal_venda', dias_validade = '$dias_validade', usuario = '$usuario', cliente = '$cliente', forma_pgto = '$forma_pgto', desconto = '$desconto', frete = '$frete', tipo_desconto = '$tipo_desconto', subtotal = '$subtotal_venda', tipo = '$tipo', obs = '$obs' WHERE id = '$id'");
	$id_orcamento = $id;	
	
	echo 'Salvo com Sucesso-'.$id_orcamento;
}





 ?>