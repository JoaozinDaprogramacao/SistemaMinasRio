<?php 
$tabela = 'orcamentos';
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

$query = $pdo->query("SELECT * FROM $tabela where id = '$id'");
$res = $query->fetchAll(PDO::FETCH_ASSOC);

$cliente = $res[0]['cliente'];
$valor = $res[0]['valor'];
$data = $res[0]['data'];
$dias_validade = $res[0]['dias_validade'];	
$desconto = $res[0]['desconto'];
$tipo_desconto = $res[0]['tipo_desconto'];
$subtotal = $res[0]['subtotal'];
$obs = $res[0]['obs'];
$usuario = $res[0]['usuario'];
$status = $res[0]['status'];
$frete = $res[0]['frete'];
$forma_pgto = $res[0]['forma_pgto'];
$hora = $res[0]['hora'];
$tipo = $res[0]['tipo'];


//verificar caixa aberto
$query1 = $pdo->query("SELECT * from caixas where operador = '$id_usuario' and data_fechamento is null order by id desc limit 1");
$res1 = $query1->fetchAll(PDO::FETCH_ASSOC);
if(@count($res1) > 0){
	$id_caixa = @$res1[0]['id'];
}else{
	$id_caixa = 0;
}
//  

$descricao = 'Venda: '.$tipo.' ('.$id.')';

$pdo->query("INSERT INTO receber SET descricao = '$descricao', valor = '$valor', vencimento = curDate(), data_lanc = curDate(), data_pgto = curDate(), usuario_lanc = '$usuario', arquivo = 'sem-foto.png', pago = 'Sim', usuario_pgto = '$id_usuario', cliente = '$cliente', referencia = 'Venda', hora = curTime(), forma_pgto = '$forma_pgto', desconto = '$desconto', troco = '0', frete = '$frete', tipo_desconto = '$tipo_desconto', subtotal = '$valor', caixa = '$id_caixa', id_orcamento = '$id'");
$id_venda = $pdo->lastInsertId();

//buscar os itens do orçamento para converter em itens venda
$query = $pdo->query("SELECT * from itens_orc where id_orcamento = '$id' order by id asc");	
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
	$valor = $res5[0]['valor_venda'];
	$tem_estoque = $res5[0]['tem_estoque'];
	$vendas = $res5[0]['vendas'];
	$unidade = $res5[0]['unidade'];

	$query3 = $pdo->query("SELECT * FROM unidade_medida where id = '$unidade'");
	$res3 = $query3->fetchAll(PDO::FETCH_ASSOC);
	if(@count($res3) > 0){
		$nome_unidade = $res3[0]['nome'];
	}else{
		$nome_unidade = 'Sem Unidade';
	}

	if($tem_estoque == 'Sim'){
	$novo_estoque = $estoque - $quantidade;

	if($nome_unidade == 'Quilogramas' or $nome_unidade == 'Quilo' or $nome_unidade == 'Quilograma' or $nome_unidade == 'KG' or $nome_unidade == 'Metros' or $nome_unidade == 'Metro' or $nome_unidade == 'M' or $nome_unidade == 'm' or $nome_unidade == 'Litro' or $nome_unidade == 'Litros' or $nome_unidade == 'L'){
		$vendas = $vendas + 1;
	}else{
		$vendas = $vendas + $quantidade;
	}

	
	//adicionar os produtos na tabela produtos
	$pdo->query("UPDATE produtos SET estoque = '$novo_estoque', vendas = '$vendas' WHERE id = '$produto'"); 
}

	


	 $pdo->query("INSERT INTO itens_venda SET produto = '$produto', valor = '$valor', quantidade = '$quantidade', total = '$total', id_venda = '$id_venda', funcionario = '$id_usuario'");	

	}
}

$pdo->query("UPDATE orcamentos SET status = 'Concluído', data_aprovacao = curDate() where id = '$id'");


//lançar a comissão do vendedor
$query1 = $pdo->query("SELECT * from usuarios where id = '$usuario'");
$res1 = $query1->fetchAll(PDO::FETCH_ASSOC);
$comissao = $res1[0]['comissao'];
if($comissao > 0){
	
	$valor_da_venda = $subtotal - $frete;
	$valor_da_comissao = $valor_da_venda * $comissao / 100;
	
	$pdo->query("INSERT INTO pagar SET descricao = 'Comissão Venda', funcionario = '$usuario', valor = '$valor_da_comissao', vencimento = '$data_final_mes', data_lanc = curDate(), frequencia = '0', arquivo = 'sem-foto.png', subtotal = '$valor_da_comissao', usuario_lanc = '$id_usuario', pago = 'Não', referencia = 'Comissão', hora = curTime(), id_ref = '$id_venda' ");
}

echo 'Baixado com Sucesso';

?>

