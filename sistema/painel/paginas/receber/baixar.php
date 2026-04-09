<?php
$tabela = 'receber';
require_once("../../../conexao.php");
@session_start();
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
$id_usuario = $_SESSION['id'];

$id = $_POST['id-baixar'];
$banco = $_POST['banco'];
$data_baixar = $_POST['data-baixar'];

// Novos Campos
$numero_operacao = $_POST['numero_operacao'] ?? "";
$obs_baixar = $_POST['obs-baixar'] ?? "Baixa de Título"; // Se vazio, coloca um texto padrão

$valor = $_POST['valor-baixar'];
$valor = str_replace(',', '.', $valor);

// Trocado taxa por acrescimo
$acrescimo = $_POST['valor-acrescimo'] ?? 0;
$acrescimo = str_replace(',', '.', $acrescimo);

$multa = $_POST['valor-multa'] ?? 0;
$multa = str_replace(',', '.', $multa);

$desconto = $_POST['valor-desconto'] ?? 0;
$desconto = str_replace(',', '.', $desconto);

$juros = $_POST['valor-juros'] ?? 0;
$juros = str_replace(',', '.', $juros);

$valor_padrao = $valor;

$subtotal = $_POST['subtotal'];
$subtotal = str_replace(',', '.', $subtotal);

$saida = $_POST['saida-baixar'];

if (empty($banco)) {
    echo 'Por favor selecione um banco para o depósito!';
    exit();
}

$juros = ($juros == "") ? 0 : $juros;
$multa = ($multa == "") ? 0 : $multa;
$acrescimo = ($acrescimo == "") ? 0 : $acrescimo;
$desconto = ($desconto == "") ? 0 : $desconto;

$query = $pdo->query("SELECT * FROM $tabela where id = '$id'");
$res = $query->fetchAll(PDO::FETCH_ASSOC);

$descricao = $res[0]['descricao'];
$cliente = $res[0]['cliente'];
$valor_antigo = $res[0]['valor'];
$data_venc = $res[0]['vencimento'];
$frequencia = $res[0]['frequencia'];
$saida_antiga = $res[0]['forma_pgto'];
$arquivo = $res[0]['arquivo'];
$referencia = $res[0]['referencia'];
$data_lanc_antiga = $res[0]['data_lanc'];

if ($cliente == "") {
    $cliente = 0;
}

$query2 = $pdo->query("SELECT * FROM clientes where id = '$cliente'");
$res2 = $query2->fetchAll(PDO::FETCH_ASSOC);
if (@count($res2) > 0) {
    $nome_cliente = $res2[0]['nome'];
    $telefone_cliente = $res2[0]['contato'];
} else {
    $nome_cliente = 'Sem Registro';
    $telefone_cliente = "";
}

if ($valor > $valor_antigo) {
    echo 'O valor a ser pago não pode ser superior ao valor da conta! O valor da conta é de R$ ' . $valor_antigo;
    exit();
}

if ($valor <= 0) {
    echo 'O valor precisa ser maior que 0 ';
    exit();
}

$query1 = $pdo->query("SELECT * from caixas where operador = '$id_usuario' and data_fechamento is null order by id desc limit 1");
$res1 = $query1->fetchAll(PDO::FETCH_ASSOC);
$id_caixa = (@count($res1) > 0) ? $res1[0]['id'] : 0;

if ($valor == $valor_antigo) {
    // Adicionado $obs_baixar e $numero_operacao
    $pdo->query("INSERT INTO linha_bancos SET 
    descricao = '$obs_baixar',
    id_banco = '$banco',
    data = '$data_baixar',
    remetente = '$id_usuario',
    n_fiscal = '$numero_operacao', 
    classificacao = 1,
    mes_ref = MONTH('$data_baixar'),
    credito = '$subtotal',
    debito = '0',
    saldo = (SELECT saldo FROM bancos WHERE id = '$banco') + '$subtotal',
    status = 'Confirmado'");

    $pdo->query("UPDATE bancos SET saldo = saldo + $subtotal WHERE id = '$banco'");

    // Aqui mapeamos a variável $acrescimo para a coluna taxa (se a coluna mudou no DB, mude a palavra 'taxa' abaixo)
    $pdo->query("UPDATE $tabela set forma_pgto = '$saida', 
    usuario_pgto = '$id_usuario', 
    pago = 'Sim', 
    subtotal = '$subtotal', 
    taxa = '$acrescimo', 
    juros = '$juros', 
    multa = '$multa', 
    desconto = '$desconto', 
    data_pgto = '$data_baixar', 
    banco = '$banco',
    caixa = '$id_caixa', 
    hora = curTime() 
    where id = '$id'");

    $dias_frequencia = $frequencia;
    if ($dias_frequencia == 30 || $dias_frequencia == 31) {
        $nova_data_vencimento = date('Y-m-d', strtotime("+1 month", strtotime($data_venc)));
    } else if ($dias_frequencia == 90) {
        $nova_data_vencimento = date('Y-m-d', strtotime("+3 month", strtotime($data_venc)));
    } else if ($dias_frequencia == 180) {
        $nova_data_vencimento = date('Y-m-d', strtotime("+6 month", strtotime($data_venc)));
    } else if ($dias_frequencia == 360 || $dias_frequencia == 365) {
        $nova_data_vencimento = date('Y-m-d', strtotime("+1 year", strtotime($data_venc)));
    } else {
        $nova_data_vencimento = date('Y-m-d', strtotime("+$dias_frequencia days", strtotime($data_venc)));
    }

    if (@$dias_frequencia > 0) {
        $pdo->query("INSERT INTO $tabela set descricao = '$descricao', cliente = '$cliente', valor = '$valor_antigo', data_lanc = '$data_lanc_antiga', vencimento = '$nova_data_vencimento', frequencia = '$frequencia', forma_pgto = '$saida_antiga', arquivo = '$arquivo', pago = 'Não', referencia = '$referencia', usuario_lanc = '$id_usuario', caixa = '$id_caixa', hora = curTime()");
    }
} else {
    $descricao = '(Resíduo) ' . $descricao;

    // Adicionado classificacao e garantido valores numéricos
    $pdo->query("INSERT INTO linha_bancos SET 
    descricao = '$obs_baixar',
    id_banco = '$banco',
    data = '$data_baixar', 
    credito = '$valor_padrao',
    debito = '0',
    remetente = '$id_usuario',
    n_fiscal = '$numero_operacao',
    classificacao = 1, 
    mes_ref = MONTH('$data_baixar'),
    status = 'Confirmado',
    saldo = (SELECT saldo FROM bancos WHERE id = '$banco') + '$valor_padrao'");

    $pdo->query("UPDATE bancos SET saldo = saldo + $valor_padrao WHERE id = '$banco'");

    // Cálculo original adaptado com Acréscimo
    $valor_antigo = $valor_antigo - ($subtotal - $acrescimo - $multa - $juros + $desconto);

    $pdo->query("INSERT INTO receber set 
    id_ref = '$id', 
    referencia = '$referencia', 
    valor = '$valor_padrao', 
    data_pgto = '$data_baixar', 
    vencimento = '$data_baixar', 
    data_lanc = '$data_lanc_antiga',
    descricao = '$descricao', 
    usuario_lanc = '$id_usuario', 
    usuario_pgto = '$id_usuario', 
    cliente = '$cliente', 
    forma_pgto = '$saida', 
    frequencia = '$frequencia', 
    arquivo = '$arquivo', 
    subtotal = '$subtotal', 
    pago = 'Sim', 
    taxa = '$acrescimo', 
    multa = '$multa', 
    juros = '$juros', 
    desconto = '$desconto', 
    residuo = 'Sim', 
    caixa = '$id_caixa', 
    hora = curTime()");

    $pdo->query("UPDATE $tabela set forma_pgto = '$saida', usuario_pgto = '$id_usuario', valor = '$valor_antigo' where id = '$id'");
}

echo 'Baixado com Sucesso';
