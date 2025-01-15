<?php 
$tabela = 'ordem_compra';
require_once("../../../conexao.php");
@session_start();
$id_usuario = $_SESSION['id'];


$data_atual = date('Y-m-d');
$mes_atual = Date('m');
$ano_atual = Date('Y');
$data_inicio_mes = $ano_atual."-".$mes_atual."-01";
$data_inicio_ano = $ano_atual."-01-01";

$data_ontem = date('Y-m-d', strtotime("-1 days",strtotime($data_atual)));
$data_amanha = date('Y-m-d', strtotime("+1 days",strtotime($data_atual)));


if($mes_atual == '04' || $mes_atual == '06' || $mes_atual == '09' || $mes_atual == '11'){
	$data_final_mes = $ano_atual.'-'.$mes_atual.'-30';
}else if($mes_atual == '02'){
	$bissexto = date('L', @mktime(0, 0, 0, 1, 1, $ano_atual));
	if($bissexto == 1){
		$data_final_mes = $ano_atual.'-'.$mes_atual.'-29';
	}else{
		$data_final_mes = $ano_atual.'-'.$mes_atual.'-28';
	}

}else{
	$data_final_mes = $ano_atual.'-'.$mes_atual.'-31';
}


$id = $_POST['id-baixar'];
$forma_pgto = $_POST['saida-baixar'];
$fornecedor = $_POST['fornecedor'];

$query = $pdo->query("SELECT * FROM $tabela where id = '$id'");
$res = $query->fetchAll(PDO::FETCH_ASSOC);

$valor = $res[0]['valor'];
$data = $res[0]['data'];
$desconto = $res[0]['desconto'];
$tipo_desconto = $res[0]['tipo_desconto'];
$subtotal = $res[0]['subtotal'];
$obs = $res[0]['obs'];
$usuario = $res[0]['usuario'];
$status = $res[0]['status'];
$frete = $res[0]['frete'];
$forma_pgto = $res[0]['forma_pgto'];
$hora = $res[0]['hora'];

if($fornecedor == ""){
	$fornecedor = 0;
}

//verificar caixa aberto
$query1 = $pdo->query("SELECT * from caixas where operador = '$id_usuario' and data_fechamento is null order by id desc limit 1");
$res1 = $query1->fetchAll(PDO::FETCH_ASSOC);
if(@count($res1) > 0){
	$id_caixa = @$res1[0]['id'];
}else{
	$id_caixa = 0;
}
//  

$descricao = 'Compra: ('.$id.')';

$pdo->query("INSERT INTO pagar SET descricao = '$descricao', valor = '$valor', vencimento = curDate(), data_lanc = curDate(), data_pgto = curDate(), usuario_lanc = '$id_usuario', arquivo = 'sem-foto.png', pago = 'Sim', usuario_pgto = '$id_usuario', referencia = 'Compra', hora = curTime(), forma_pgto = '$forma_pgto', desconto = '$desconto', frete = '$frete', tipo_desconto = '$tipo_desconto', subtotal = '$valor', caixa = '$id_caixa', id_compra = '$id', fornecedor = '$fornecedor'");
$id_venda = $pdo->lastInsertId();

//buscar os itens do orçamento para converter em itens venda
$query = $pdo->query("SELECT * from itens_compra where id_orcamento = '$id' order by id asc");	
$res = $query->fetchAll(PDO::FETCH_ASSOC);
$linhas = @count($res);
if($linhas > 0){
	for($i=0; $i<$linhas; $i++){
	
	$produto = $res[$i]['produto'];
	$valor = $res[$i]['valor'];
	$quantidade = $res[$i]['quantidade'];
	$total = $res[$i]['total'];

	//baixa do produto no estoque
	$query5 = $pdo->query("SELECT * from produtos where id = '$produto'");
	$res5 = $query5->fetchAll(PDO::FETCH_ASSOC);
	$estoque = $res5[0]['estoque'];	
	$tem_estoque = $res5[0]['tem_estoque'];	

	if($tem_estoque == 'Sim'){
	$novo_estoque = $estoque + $quantidade;
	
	//adicionar os produtos na tabela produtos
	$pdo->query("UPDATE produtos SET estoque = '$novo_estoque' WHERE id = '$produto'"); 
}

		

	}
}

$pdo->query("UPDATE ordem_compra SET status = 'Aprovada', data_aprovacao = curDate(), fornecedor = '$fornecedor' where id = '$id'");


echo 'Baixado com Sucesso';

?>

