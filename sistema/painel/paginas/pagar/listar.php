<?php
@session_start();
$mostrar_registros = @$_SESSION['registros'];
$id_usuario = @$_SESSION['id'];
$tabela = 'pagar';
require_once("../../../conexao.php");
require_once("../../verificar.php");

$sql_usuario_lanc = ($mostrar_registros == 'Não') ? " AND t.usuario_lanc = '$id_usuario'" : " ";

$data_hoje = date('Y-m-d');
$mes_atual = date('m');
$ano_atual = date('Y');
$data_inicio_mes = $ano_atual . "-" . $mes_atual . "-01";

if (in_array($mes_atual, ['04', '06', '09', '11'])) {
    $data_final_mes = $ano_atual . '-' . $mes_atual . '-30';
} else if ($mes_atual == '02') {
    $data_final_mes = (date('L', mktime(0, 0, 0, 1, 1, $ano_atual)) == 1) ? $ano_atual . '-02-29' : $ano_atual . '-02-28';
} else {
    $data_final_mes = $ano_atual . '-' . $mes_atual . '-31';
}

$filtro      = @$_POST['p1'];
$dataInicial = @$_POST['p2'] ?: $data_inicio_mes;
$dataFinal   = @$_POST['p3'] ?: $data_final_mes;
$tipo_data   = @$_POST['p4'] ?: 'vencimento';
$atacadista  = @$_POST['p5'];
$forma_pgto  = @$_POST['p6'];
$funcionario = @$_POST['p7'];

$sql_fornecedor  = !empty($atacadista)  ? " AND t.fornecedor = '$atacadista'"   : "";
$sql_pgto        = !empty($forma_pgto)  ? " AND t.forma_pgto = '$forma_pgto'"   : "";
$sql_funcionario = !empty($funcionario) ? " AND t.funcionario = '$funcionario'" : "";

$base_from  = " FROM $tabela t ";
$base_where = " WHERE t.$tipo_data >= '$dataInicial' AND t.$tipo_data <= '$dataFinal'
                $sql_usuario_lanc $sql_fornecedor $sql_pgto $sql_funcionario ";

// Totais para os cards (query única)
$res_totais = $pdo->query("SELECT
    SUM(CASE WHEN t.vencimento < curDate() AND t.pago = 'Não' THEN t.valor ELSE 0 END) as vencidas,
    SUM(CASE WHEN t.pago = 'Sim' THEN t.subtotal ELSE 0 END) as pagas,
    SUM(CASE WHEN t.vencimento >= curDate() AND t.pago = 'Não' THEN t.valor ELSE 0 END) as a_vencer,
    SUM(t.valor) as total_bruto,
    SUM(COALESCE(t.desconto, 0)) as desc_total,
    SUM(COALESCE(t.juros, 0) + COALESCE(t.multa, 0) + COALESCE(t.taxa, 0)) as acres_total
    $base_from $base_where")->fetch(PDO::FETCH_ASSOC);

$total_vencidasF  = number_format($res_totais['vencidas']    ?? 0, 2, ',', '.');
$total_pagasF     = number_format($res_totais['pagas']       ?? 0, 2, ',', '.');
$total_a_vencerF  = number_format($res_totais['a_vencer']    ?? 0, 2, ',', '.');
$total_totalF     = number_format($res_totais['total_bruto'] ?? 0, 2, ',', '.');
$total_descontoF  = number_format($res_totais['desc_total']  ?? 0, 2, ',', '.');
$total_acrescimoF = number_format($res_totais['acres_total'] ?? 0, 2, ',', '.');

$total_liquido  = (($res_totais['total_bruto'] ?? 0) - ($res_totais['desc_total'] ?? 0)) + ($res_totais['acres_total'] ?? 0);
$total_liquidoF = number_format($total_liquido, 2, ',', '.');

// Ordenação inteligente: vencidas primeiro, depois a vencer, depois pagas
$ordem = "ORDER BY CASE
            WHEN t.pago = 'Não' AND t.vencimento < curDate() THEN 1
            WHEN t.pago = 'Não' AND t.vencimento >= curDate() THEN 2
            ELSE 3 END ASC, t.vencimento ASC";

$where_filtro = "";
if ($filtro == 'Vencidas') {
    $where_filtro = " AND t.vencimento < curDate() AND t.pago = 'Não'";
    $ordem = "ORDER BY t.vencimento ASC";
} else if ($filtro == 'Pagas') {
    $where_filtro = " AND t.pago = 'Sim'";
    $ordem = "ORDER BY t.data_pgto DESC";
} else if ($filtro == 'AVencer') {
    $where_filtro = " AND t.vencimento >= curDate() AND t.pago = 'Não'";
}

$query = $pdo->query("SELECT t.* $base_from $base_where $where_filtro $ordem");
$res = $query->fetchAll(PDO::FETCH_ASSOC);
$linhas = @count($res);

$total_pago = 0;
$total_pendentes = 0;

if ($linhas > 0) {
    echo <<<HTML
<small>
    <table class="table table-bordered text-nowrap border-bottom dt-responsive" id="tabela">
    <thead>
        <tr>
            <th align="center" width="5%" class="text-center">Selecionar</th>
            <th class="esc">Data Lançamento</th>
            <th>Descrição</th>
            <th>Valor</th>
            <th class="esc">Pessoa</th>
            <th class="esc">Vencimento</th>
            <th class="esc">Pagamento</th>
            <th class="esc">Arquivo</th>
            <th>Ações</th>
        </tr>
    </thead>
    <tbody>
HTML;

    for ($i = 0; $i < $linhas; $i++) {
        $id             = $res[$i]['id'];
        $descricao      = $res[$i]['descricao'];
        $fornecedor_row = $res[$i]['fornecedor'];
        $funcionario_row = $res[$i]['funcionario'];
        $valor          = $res[$i]['valor'];
        $vencimento     = $res[$i]['vencimento'];
        $data_pgto      = $res[$i]['data_pgto'];
        $data_lanc      = $res[$i]['data_lanc'];
        $arquivo        = $res[$i]['arquivo'] ?? '';
        $id_ref         = $res[$i]['id_ref'];
        $pago           = $res[$i]['pago'] ?: 'Não';
        $subtotal       = $res[$i]['subtotal'];
        $forma_pgto_row = $res[$i]['forma_pgto'];
        $frequencia     = $res[$i]['frequencia'];
        $obs            = $res[$i]['obs'];
        $valor_restante = $res[$i]['valor_restante'] ?? $valor;
        $id_romaneio    = $res[$i]['id_romaneio'] ?? 0;
        $referencia     = $res[$i]['referencia'] ?? '';

        $data_lancF  = date('d/m/Y', strtotime($data_lanc));
        $vencimentoF = date('d/m/Y', strtotime($vencimento));
        $data_pgtoF  = ($data_pgto && $data_pgto != '0000-00-00') ? date('d/m/Y', strtotime($data_pgto)) : "";

        if ($pago == 'Sim') {
            $classe_pago  = 'verde';
            $texto_badge  = 'Pago';
            $total_pago  += $subtotal;
            $valor_finalF = number_format($subtotal, 2, ',', '.');
        } else if ($pago == 'Parcial') {
            $classe_pago = 'text-warning';
            $texto_badge = 'Pag. Parcial';
            $ja_pago = $subtotal - $valor_restante;
            if ($ja_pago > 0) $total_pago += $ja_pago;
            $total_pendentes += $valor_restante;
            $valor_finalF = number_format($valor, 2, ',', '.');
        } else {
            $classe_pago      = 'text-danger';
            $texto_badge      = 'Pendente';
            $total_pendentes += $valor;
            $valor_finalF     = number_format($valor, 2, ',', '.');
        }

        // Resolve nome da pessoa
        $nome_pessoa = 'Sem Registro';
        $pgto_padrao = '';
        if ($fornecedor_row != 0) {
            $sql_forn = $pdo->query("SELECT nome_atacadista FROM fornecedores WHERE id = '$fornecedor_row'")->fetch(PDO::FETCH_ASSOC);
            $nome_pessoa = $sql_forn['nome_atacadista'] ?? 'Fornecedor Excluído';
        } elseif ($funcionario_row != 0) {
            $sql_func = $pdo->query("SELECT nome FROM funcionarios WHERE id = '$funcionario_row'")->fetch(PDO::FETCH_ASSOC);
            $nome_pessoa = $sql_func['nome'] ?? 'Funcionário Excluído';
        }

        $ext = pathinfo($arquivo, PATHINFO_EXTENSION);
        $tumb_arquivo = (in_array(strtolower($ext), ['jpg', 'jpeg', 'png', 'gif'])) ? $arquivo : ($ext ? strtolower($ext) . '.png' : 'sem-foto.png');
        $classe_venc = (strtotime($vencimento) < strtotime($data_hoje) && $pago != 'Sim') ? 'text-danger' : '';

        echo <<<HTML
<tr>
    <td align="center">
        <div class="custom-checkbox custom-control">
            <input type="checkbox" class="custom-control-input" id="seletor-{$id}" onchange="selecionar('{$id}')">
            <label for="seletor-{$id}" class="custom-control-label mt-1 text-dark"></label>
        </div>
    </td>
    <td class="esc">{$data_lancF}</td>
    <td><i class="fa fa-square {$classe_pago} mr-1"></i> {$descricao}</td>
    <td>R$ {$valor_finalF}</td>
    <td class="esc">{$nome_pessoa}</td>
    <td class="esc {$classe_venc}">{$vencimentoF}</td>
    <td class="esc">{$data_pgtoF}</td>
    <td class="esc"><a href="images/contas/{$arquivo}" target="_blank"><img src="images/contas/{$tumb_arquivo}" width="25px"></a></td>
    <td>
    <big>
        <a href="#" onclick="baixar('{$id}', '{$descricao}', '{$valor}', '{$vencimento}', '{$nome_pessoa}', '{$pgto_padrao}', '{$pago}', '{$id_romaneio}', '{$referencia}')" title="Baixar / Pagar Conta">
            <i class="fa fa-check-square text-success"></i>
        </a>
    </big>
    <div style="display: inline-block;" class="dropdown">
        <a href="#" data-bs-toggle="dropdown"><i class="fa fa-trash text-danger"></i></a>
        <div class="dropdown-menu">
            <div class="dropdown-item-text">Confirmar? <a href="#" onclick="excluir('{$id}')"><span class="text-danger">Sim</span></a></div>
        </div>
    </div>
    </td>
</tr>
HTML;
    }

    $total_pagoF      = number_format($total_pago,      2, ',', '.');
    $total_pendentesF = number_format($total_pendentes, 2, ',', '.');

    echo <<<HTML
    </tbody>
    </table>
    <div class="d-flex justify-content-between mt-3">
        <span class="ocultar_mobile">Filtro: <b>{$tipo_data}</b></span>
        <p>Pendentes: <span class="text-danger">R$ {$total_pendentesF}</span> | Pago: <span class="text-success">R$ {$total_pagoF}</span></p>
    </div>
</small>
HTML;
} else {
    echo 'Nenhum Registro Encontrado!';
}
?>

<script>
    $(document).ready(function () {
        if ($.fn.DataTable.isDataTable('#tabela')) {
            $('#tabela').DataTable().destroy();
        }
        $('#tabela').DataTable({
            "ordering": false,
            "stateSave": true,
            "language": {
                "url": "//cdn.datatables.net/plug-ins/1.13.2/i18n/pt-BR.json"
            }
        });

        $('#total_vencidas').text('R$ <?= $total_vencidasF ?>');
        $('#total_a_vencer').text('R$ <?= $total_a_vencerF ?>');
        $('#total_pagas').text('R$ <?= $total_pagasF ?>');
        $('#total_total').text('R$ <?= $total_totalF ?>');
        $('#total_desconto').text('R$ <?= $total_descontoF ?>');
        $('#total_acrescimo').text('R$ <?= $total_acrescimoF ?>');
        $('#total_liquido').text('R$ <?= $total_liquidoF ?>');
    });
</script>
