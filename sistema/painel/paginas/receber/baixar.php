<?php
$tabela = 'receber';
require_once("../../../conexao.php");
@session_start();
$id_usuario = $_SESSION['id'];

$id = $_POST['id-baixar'];
$obs_baixar = $_POST['obs-baixar'] ?? "";
if (trim($obs_baixar) == "") {
    $obs_baixar = "Baixa de Título";
}

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

$acrescimo = $_POST['valor-acrescimo'] ?? 0;
$acrescimo = str_replace(',', '.', str_replace('.', '', $acrescimo));

$multa = $_POST['valor-multa'] ?? 0;
$multa = str_replace(',', '.', str_replace('.', '', $multa));

$desconto = $_POST['valor-desconto'] ?? 0;
$desconto = str_replace(',', '.', str_replace('.', '', $desconto));

$juros = $_POST['valor-juros'] ?? 0;
$juros = str_replace(',', '.', str_replace('.', '', $juros));

$subtotal = $_POST['subtotal'] ?? 0;
$subtotal = str_replace(',', '.', str_replace('.', '', $subtotal));

$acrescimo = ($acrescimo == "") ? 0 : $acrescimo;
$multa = ($multa == "") ? 0 : $multa;
$juros = ($juros == "") ? 0 : $juros;
$desconto = ($desconto == "") ? 0 : $desconto;

// Captura as múltiplas linhas
$valores_pgto = $_POST['valor_baixar'] ?? [];
$datas_pgto = $_POST['data_baixar'] ?? [];
$formas_pgto = $_POST['saida_baixar'] ?? [];
$bancos_pgto = $_POST['banco_baixar'] ?? [];
$operacoes = $_POST['numero_operacao'] ?? [];

$total_recebido = 0;
$pagamentos_validos = [];

// Calcula o total recebido nesta requisição
for ($i = 0; $i < count($valores_pgto); $i++) {
    $v = str_replace(',', '.', str_replace('.', '', $valores_pgto[$i]));
    if ($v > 0) {
        $total_recebido += $v;
        $pagamentos_validos[] = [
            'valor' => $v,
            'data' => $datas_pgto[$i],
            'forma' => $formas_pgto[$i],
            'banco' => empty($bancos_pgto[$i]) ? null : $bancos_pgto[$i],
            'operacao' => $operacoes[$i]
        ];
    }
}

// Verifica e define se foi Pago Totalmente ou Parcialmente
$status_pago = 'Não';
if ($total_recebido > 0) {
    // Usamos - 0.05 para evitar erro de centavos de dízima no PHP
    if ($total_recebido >= ($subtotal - 0.05)) {
        $status_pago = 'Sim'; // Totalmente Pago
    } else {
        $status_pago = 'Parcial'; // Pagamento Parcial
    }
}

$valor_restante = $subtotal - $total_recebido;
if ($valor_restante < 0) $valor_restante = 0;

$query1 = $pdo->query("SELECT * from caixas where operador = '$id_usuario' and data_fechamento is null order by id desc limit 1");
$res1 = $query1->fetchAll(PDO::FETCH_ASSOC);
$id_caixa = (@count($res1) > 0) ? $res1[0]['id'] : 0;

// Extrai dados da primeira linha para ser a referência principal na tabela "receber"
$data_baixar = count($pagamentos_validos) > 0 ? $pagamentos_validos[0]['data'] : null;
$saida = count($pagamentos_validos) > 0 ? $pagamentos_validos[0]['forma'] : null;
$banco_principal = count($pagamentos_validos) > 0 ? $pagamentos_validos[0]['banco'] : null;

// Remove pagamentos antigos desta conta (para não duplicar em caso de edição)
$pdo->query("DELETE FROM receber_pagamentos WHERE id_receber = '$id'");

// Atualiza a conta mãe (Mantém o ID e o Valor Original intactos!)
$sql_update = "UPDATE $tabela SET 
    usuario_pgto = '$id_usuario', 
    pago = '$status_pago', 
    subtotal = '$subtotal', 
    taxa = '$acrescimo', 
    juros = '$juros', 
    multa = '$multa', 
    desconto = '$desconto', 
    valor_restante = '$valor_restante',
    obs = '$obs_baixar',
    caixa = '$id_caixa',
    hora = curTime()";

if ($data_baixar) { $sql_update .= ", data_pgto = '$data_baixar'"; }
if ($saida) { $sql_update .= ", forma_pgto = '$saida'"; }
if ($banco_principal) { $sql_update .= ", banco = '$banco_principal'"; } else { $sql_update .= ", banco = NULL"; }

$sql_update .= " WHERE id = '$id'";
$pdo->query($sql_update);

// Insere cada linha de pagamento na tabela auxiliar
foreach ($pagamentos_validos as $pg) {
    $v_pag = $pg['valor'];
    $d_pag = $pg['data'];
    $f_pag = $pg['forma'];
    $b_pag = $pg['banco'];
    $op_pag = $pg['operacao'];

    $pdo->query("INSERT INTO receber_pagamentos SET 
        id_receber = '$id', 
        valor = '$v_pag', 
        data_pgto = '$d_pag', 
        forma_pgto = '$f_pag', 
        banco = " . (empty($b_pag) ? "NULL" : "'$b_pag'") . ", 
        numero_operacao = '$op_pag'");
}

echo 'Baixado com Sucesso';
?>