<?php
$tabela = 'pagar';
require_once("../../../conexao.php");
@session_start();
$id_usuario = $_SESSION['id'];

$id = $_POST['id-baixar'];
$obs_baixar = $_POST['obs-baixar'] ?? "";
if (trim($obs_baixar) == "") $obs_baixar = "Baixa de Título";

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
$multa     = ($multa == "")     ? 0 : $multa;
$juros     = ($juros == "")     ? 0 : $juros;
$desconto  = ($desconto == "")  ? 0 : $desconto;

// Captura as múltiplas linhas de pagamento
$valores_pgto = $_POST['valor_baixar'] ?? [];
$datas_pgto   = $_POST['data_baixar']  ?? [];
$formas_pgto  = $_POST['saida_baixar'] ?? [];
$bancos_pgto  = $_POST['banco_baixar'] ?? [];
$operacoes    = $_POST['numero_operacao'] ?? [];

$total_pago = 0;
$pagamentos_validos = [];

for ($i = 0; $i < count($valores_pgto); $i++) {
    $v = str_replace(',', '.', str_replace('.', '', $valores_pgto[$i]));
    if ($v > 0) {
        $total_pago += $v;
        $pagamentos_validos[] = [
            'valor'   => $v,
            'data'    => $datas_pgto[$i],
            'forma'   => $formas_pgto[$i],
            'banco'   => empty($bancos_pgto[$i]) ? null : $bancos_pgto[$i],
            'operacao'=> $operacoes[$i]
        ];
    }
}

if (count($pagamentos_validos) === 0) {
    echo 'Informe ao menos um pagamento!';
    exit();
}

foreach ($pagamentos_validos as $pg) {
    if (empty($pg['banco'])) {
        echo 'Por favor selecione um banco para cada linha de pagamento!';
        exit();
    }
}

// Determina o status: Sim / Parcial / Não
$status_pago = 'Não';
if ($total_pago > 0) {
    if ($total_pago >= ($subtotal - 0.05)) {
        $status_pago = 'Sim';
    } else {
        $status_pago = 'Parcial';
    }
}

$valor_restante = $subtotal - $total_pago;
if ($valor_restante < 0) $valor_restante = 0;

// Buscar dados da conta para frequência/recorrência
$conta = $pdo->query("SELECT * FROM $tabela WHERE id = '$id'")->fetch(PDO::FETCH_ASSOC);
$descricao   = $conta['descricao'];
$fornecedor  = $conta['fornecedor']  ?: 0;
$funcionario = $conta['funcionario'] ?: 0;
$valor_antigo = $conta['valor'];
$data_venc   = $conta['vencimento'];
$frequencia  = $conta['frequencia'];
$saida_antiga = $conta['forma_pgto'];
$arquivo     = $conta['arquivo'];
$referencia  = $conta['referencia'];

// Caixa do operador
$query1 = $pdo->query("SELECT * FROM caixas WHERE operador = '$id_usuario' AND data_fechamento IS NULL ORDER BY id DESC LIMIT 1");
$res1 = $query1->fetchAll(PDO::FETCH_ASSOC);
$id_caixa = (@count($res1) > 0) ? $res1[0]['id'] : 0;

// Reverter transações bancárias de baixa anterior (caso seja uma re-baixa)
$old_pgtos = $pdo->query("SELECT * FROM pagar_pagamentos WHERE id_pagar = '$id'")->fetchAll(PDO::FETCH_ASSOC);
foreach ($old_pgtos as $op) {
    if (!empty($op['banco'])) {
        $pdo->query("UPDATE bancos SET saldo = saldo + {$op['valor']} WHERE id = '{$op['banco']}'");
        $pdo->query("INSERT INTO linha_bancos SET
            descricao = 'Estorno de Baixa',
            id_banco  = '{$op['banco']}',
            data      = curDate(),
            remetente = '$id_usuario',
            n_fiscal  = '',
            classificacao = 2,
            mes_ref   = MONTH(curDate()),
            credito   = '{$op['valor']}',
            debito    = '0',
            saldo     = (SELECT saldo FROM bancos WHERE id = '{$op['banco']}'),
            status    = 'Estorno'
        ");
    }
}
$pdo->query("DELETE FROM pagar_pagamentos WHERE id_pagar = '$id'");

// Dados da primeira linha como referência principal na tabela pagar
$data_baixar = count($pagamentos_validos) > 0 ? $pagamentos_validos[0]['data']  : null;
$saida       = count($pagamentos_validos) > 0 ? $pagamentos_validos[0]['forma'] : null;

// Atualiza a conta principal
$sql_update = "UPDATE $tabela SET
    usuario_pgto  = '$id_usuario',
    pago          = '$status_pago',
    subtotal      = '$subtotal',
    taxa          = '$acrescimo',
    juros         = '$juros',
    multa         = '$multa',
    desconto      = '$desconto',
    valor_restante = '$valor_restante',
    obs           = '$obs_baixar',
    caixa         = '$id_caixa',
    hora          = curTime()";

if ($data_baixar) $sql_update .= ", data_pgto = '$data_baixar'";
if ($saida)       $sql_update .= ", forma_pgto = '$saida'";

$sql_update .= " WHERE id = '$id'";
$pdo->query($sql_update);

// Insere cada linha de pagamento + lança débito no banco
foreach ($pagamentos_validos as $pg) {
    $v_pag  = $pg['valor'];
    $d_pag  = $pg['data'];
    $f_pag  = $pg['forma'];
    $b_pag  = $pg['banco'];
    $op_pag = $pg['operacao'];

    $pdo->query("INSERT INTO pagar_pagamentos SET
        id_pagar       = '$id',
        valor          = '$v_pag',
        data_pgto      = '$d_pag',
        forma_pgto     = '$f_pag',
        banco          = " . (empty($b_pag) ? "NULL" : "'$b_pag'") . ",
        numero_operacao = '$op_pag'");

    if (!empty($b_pag)) {
        $pdo->query("INSERT INTO linha_bancos SET
            descricao     = '$descricao',
            id_banco      = '$b_pag',
            data          = '$d_pag',
            remetente     = '$id_usuario',
            n_fiscal      = '',
            classificacao = 2,
            mes_ref       = MONTH('$d_pag'),
            credito       = '0',
            debito        = '$v_pag',
            saldo         = (SELECT saldo FROM bancos WHERE id = '$b_pag') - '$v_pag',
            status        = 'Confirmado'
        ");
        $pdo->query("UPDATE bancos SET saldo = saldo - $v_pag WHERE id = '$b_pag'");
    }
}

// Gera próxima conta recorrente se pagamento total
if ($status_pago === 'Sim' && $frequencia > 0) {
    if (in_array($frequencia, [30, 31])) {
        $nova_data_vencimento = date('Y-m-d', strtotime("+1 month", strtotime($data_venc)));
    } elseif ($frequencia == 90) {
        $nova_data_vencimento = date('Y-m-d', strtotime("+3 months", strtotime($data_venc)));
    } elseif ($frequencia == 180) {
        $nova_data_vencimento = date('Y-m-d', strtotime("+6 months", strtotime($data_venc)));
    } elseif (in_array($frequencia, [360, 365])) {
        $nova_data_vencimento = date('Y-m-d', strtotime("+1 year", strtotime($data_venc)));
    } else {
        $nova_data_vencimento = date('Y-m-d', strtotime("+$frequencia days", strtotime($data_venc)));
    }

    $pdo->query("INSERT INTO $tabela SET
        descricao    = '$descricao',
        fornecedor   = '$fornecedor',
        funcionario  = '$funcionario',
        valor        = '$valor_antigo',
        data_lanc    = curDate(),
        vencimento   = '$nova_data_vencimento',
        frequencia   = '$frequencia',
        forma_pgto   = '$saida_antiga',
        arquivo      = '$arquivo',
        pago         = 'Não',
        referencia   = '$referencia',
        usuario_lanc = '$id_usuario',
        caixa        = '$id_caixa',
        hora         = curTime()
    ");
}

echo 'Baixado com Sucesso';
?>
