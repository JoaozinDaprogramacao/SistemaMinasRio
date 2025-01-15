<?php
$tabela = 'receber';
require_once("../../../conexao.php");


$data_atual = date('Y-m-d');
$mes_atual = Date('m');
$ano_atual = Date('Y');
$data_inicio_mes = $ano_atual . "-" . $mes_atual . "-01";
$data_inicio_ano = $ano_atual . "-01-01";

$data_ontem = date('Y-m-d', strtotime("-1 days", strtotime($data_atual)));
$data_amanha = date('Y-m-d', strtotime("+1 days", strtotime($data_atual)));


if ($mes_atual == '04' || $mes_atual == '06' || $mes_atual == '09' || $mes_atual == '11') {
	$data_final_mes = $ano_atual . '-' . $mes_atual . '-30';
} else if ($mes_atual == '02') {
	$bissexto = date('L', @mktime(0, 0, 0, 1, 1, $ano_atual));
	if ($bissexto == 1) {
		$data_final_mes = $ano_atual . '-' . $mes_atual . '-29';
	} else {
		$data_final_mes = $ano_atual . '-' . $mes_atual . '-28';
	}
} else {
	$data_final_mes = $ano_atual . '-' . $mes_atual . '-31';
}

@session_start();
$id_usuario = $_SESSION['id'];

$desconto = $_POST['desconto'];
$desconto = @str_replace(',', '.', $desconto);
$cliente = $_POST['cliente'];
$saida = $_POST['saida'];
$data = $_POST['data2'];
$frete = $_POST['frete'];

$tipo_desconto = $_POST['tipo_desconto'];
$subtotal_venda = $_POST['subtotal_venda'];
$valor_restante = $_POST['valor_restante'];
$valor_pago = $_POST['valor_pago'];

$data_restante = $_POST['data_restante'];

$forma_pgto2 = $_POST['forma_pgto2'];

if ($valor_restante > 0 and $forma_pgto2 == "" and $data_restante == $data_atual) {
	echo 'Você precisa selecionar uma forma de pagamento para o valor restante!';
	exit();
}

$dataF = implode('/', array_reverse(explode('-', $data)));

if ($desconto == "") {
	$desconto = 0;
}

if ($frete == "") {
	$frete = 0;
}



$total_v = 0;
//buscar o total da venda
$query = $pdo->query("SELECT * from itens_venda where funcionario = '$id_usuario' and id_venda = '0' order by id asc");
$res = $query->fetchAll(PDO::FETCH_ASSOC);
$linhas = @count($res);
if ($linhas > 0) {
	for ($i = 0; $i < $linhas; $i++) {
		$total_das_vendas = $res[$i]['total'];
		$total_v += $total_das_vendas;
	}
}

if ($tipo_desconto == '%') {
	if ($desconto > 0 and $total_v > 0) {
		$total_final = - ($total_v * $desconto / 100);
	} else {
		$total_final = 0;
	}
} else {
	$total_final = -$desconto;
}


$query = $pdo->query("SELECT * from itens_venda where funcionario = '$id_usuario' and id_venda = '0' order by id asc");
$res = $query->fetchAll(PDO::FETCH_ASSOC);
$linhas = @count($res);
if ($linhas > 0) {
	for ($i = 0; $i < $linhas; $i++) {
		$id = $res[$i]['id'];
		$material = $res[$i]['material'];
		$valor = $res[$i]['valor'];
		$quantidade = $res[$i]['quantidade'];
		$total = $res[$i]['total'];

		$total_final += $total;
		$total_finalF = number_format($total_final, 2, ',', '.');
		$valorF = number_format($valor, 2, ',', '.');
		$totalF = number_format($total, 2, ',', '.');
	}
}

if ($total_final <= 0) {
	echo 'O valor da Venda tem que ser maior que zero';
	exit();
}


if (strtotime($data) > strtotime($data_atual)) {
	$pago = 'Não';
	$data_pgto = '';
	$usuario_pgto = '';
} else {
	$pago = 'Sim';
	$data_pgto = $data;
	$usuario_pgto = $id_usuario;
}


//verificar caixa aberto
$query1 = $pdo->query("SELECT * from caixas where operador = '$id_usuario' and data_fechamento is null order by id desc limit 1");
$res1 = $query1->fetchAll(PDO::FETCH_ASSOC);
if (@count($res1) > 0) {
	$id_caixa = @$res1[0]['id'];
} else {
	$id_caixa = 0;
}
//  

if ($valor_restante > 0) {
	$pdo->query("INSERT INTO receber SET descricao = 'Nova Venda', valor = '$valor_pago', vencimento = '$data', data_lanc = curDate(), data_pgto = '$data_pgto', usuario_lanc = '$id_usuario', arquivo = 'sem-foto.png', pago = '$pago', usuario_pgto = '$usuario_pgto', cliente = '$cliente', referencia = 'Venda', hora = curTime(), forma_pgto = '$saida', desconto = '$desconto', frete = '$frete', tipo_desconto = '$tipo_desconto', subtotal = '$subtotal_venda', valor_restante = '$valor_restante', forma_pgto_restante = '$forma_pgto2', data_restante = '$data_restante', caixa = '$id_caixa'");
	$id_venda = $pdo->lastInsertId();

	if (strtotime($data_restante) > strtotime($data_atual)) {
		$pago2 = 'Não';
		$data_pgto2 = '';
		$usuario_pgto2 = '';
	} else {
		$pago2 = 'Sim';
		$data_pgto2 = $data_restante;
		$data_pgto2 = $data_pgto2 . "-01-01"; // Adicionando um mês e dia padrão (01-01)
		$usuario_pgto2 = $id_usuario;
	}

	echo $data_pgto2;
	echo "acima";
	$pdo->query("INSERT INTO receber SET descricao = 'Nova Venda (Restante)', valor = '$valor_restante', vencimento = '$data_restante', data_lanc = curDate(), data_pgto = '$data_pgto2', usuario_lanc = '$id_usuario', arquivo = 'sem-foto.png', pago = '$pago2', usuario_pgto = '$usuario_pgto2', cliente = '$cliente', referencia = 'Venda', hora = curTime(), forma_pgto = '$forma_pgto2', desconto = '$desconto', frete = '$frete', tipo_desconto = '$tipo_desconto', subtotal = '$subtotal_venda', valor_restante = '$valor_pago', forma_pgto_restante = '$saida', data_restante = '$data', id_ref = '$id_venda', caixa = '$id_caixa'");
} else {
	$pdo->query("INSERT INTO receber SET descricao = 'Nova Venda', valor = '$subtotal_venda', vencimento = '$data', data_lanc = curDate(), data_pgto = '$data_pgto', usuario_lanc = '$id_usuario', arquivo = 'sem-foto.png', pago = '$pago', usuario_pgto = '$usuario_pgto', cliente = '$cliente', referencia = 'Venda', hora = curTime(), forma_pgto = '$saida', desconto = '$desconto', frete = '$frete', tipo_desconto = '$tipo_desconto', subtotal = '$subtotal_venda', caixa = '$id_caixa'");
	$id_venda = $pdo->lastInsertId();
}



$pdo->query("UPDATE itens_venda SET id_venda = '$id_venda' WHERE id_venda = 0 and funcionario = '$id_usuario'");



//lançar a comissão do vendedor
$query1 = $pdo->query("SELECT * from usuarios where id = '$id_usuario'");
$res1 = $query1->fetchAll(PDO::FETCH_ASSOC);
$comissao = $res1[0]['comissao'];
if ($comissao > 0) {

	$valor_da_venda = $subtotal_venda - $frete;
	$valor_da_comissao = $valor_da_venda * $comissao / 100;

	$pdo->query("INSERT INTO pagar SET descricao = 'Comissão Venda', funcionario = '$id_usuario', valor = '$valor_da_comissao', vencimento = '$data_final_mes', data_lanc = curDate(), frequencia = '0', arquivo = 'sem-foto.png', subtotal = '$valor_da_comissao', usuario_lanc = '$id_usuario', pago = 'Não', referencia = 'Comissão', hora = curTime(), id_ref = '$id_venda' ");
}

//enviar para o whatsapp
if (strtotime($data) > strtotime($data_atual) and $api_whatsapp != 'Não') {
	$query = $pdo->query("SELECT * from clientes where id = '$cliente'");
	$res = $query->fetchAll(PDO::FETCH_ASSOC);
	$telefone = $res[0]['telefone'];
	$nome = $res[0]['nome'];

	$telefone_envio = '55' . preg_replace('/[ ()-]+/', '', $telefone);

	$mensagem_whatsapp = '_Nova Compra ' . $nome_sistema . '_ %0A';
	$mensagem_whatsapp .= 'Nome: *' . $nome . '* %0A';
	$mensagem_whatsapp .= 'Valor Compra: *' . $total_finalF . '* %0A';
	$mensagem_whatsapp .= 'Data de Pagamento: *' . $dataF . '*';

	require('../../apis/texto.php');
}

echo 'Salvo com Sucesso-' . $id_venda;
