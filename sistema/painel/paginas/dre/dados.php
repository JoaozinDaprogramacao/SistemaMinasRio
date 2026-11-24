<?php
require_once("../../../conexao.php");
require_once(__DIR__ . "/../../seguranca.php");
seguranca_exigir_acesso(['dre']);
require_once(__DIR__ . "/../../funcoes/financeiro.php");

$ini = fin_data_valida($_REQUEST['ini'] ?? '') ? $_REQUEST['ini'] : date('Y-01-01');
$fim = fin_data_valida($_REQUEST['fim'] ?? '') ? $_REQUEST['fim'] : date('Y-12-31');
$regime = ($_REQUEST['regime'] ?? 'competencia') === 'caixa' ? 'caixa' : 'competencia';
$centro = (int) ($_REQUEST['centro'] ?? 0);

// Meses do período (colunas)
$meses = [];
for ($m = substr($ini, 0, 7); $m <= substr($fim, 0, 7); $m = date('Y-m', strtotime($m . '-01 +1 month'))) $meses[] = $m;
if (count($meses) > 24) $meses = ['total'];

// $v[grupo][linha][mes] = valor (positivo; o sinal vem do grupo)
$v = [];
$soma = function ($grupo, $linha, $data, $valor) use (&$v, $meses) {
    $valor = round((float) $valor, 2);
    if ($valor == 0) return;
    $mes = $meses === ['total'] ? 'total' : substr($data, 0, 7);
    $v[$grupo][$linha][$mes] = ($v[$grupo][$linha][$mes] ?? 0) + $valor;
};
$cc_t = $centro ? " AND t.centro_custo = $centro" : '';
$cc_l = $centro ? " AND l.centro_custo = $centro" : '';
$grupo_rec = "COALESCE(cr.grupo_dre, 'receita')";
$grupo_pag = "COALESCE(cp.grupo_dre, 'operacional')";
$nome_rec = "COALESCE(cr.nome, 'Receitas sem categoria')";
$nome_pag = "COALESCE(cp.nome, 'Despesas sem categoria')";

if ($regime === 'competencia') {
    // Receitas pelo faturamento (data de lançamento), sem as canceladas
    foreach (fin_q($pdo, "SELECT t.data_lanc d, t.valor, t.desconto, t.juros, t.multa, t.taxa, $grupo_rec g, $nome_rec n
        FROM receber t LEFT JOIN categorias_receber cr ON cr.id = t.categoria_receber
        WHERE t.data_lanc BETWEEN ? AND ? AND COALESCE(t.cancelada, '') <> 'Sim' $cc_t", [$ini, $fim])->fetchAll(PDO::FETCH_ASSOC) as $r) {
        $soma($r['g'], $r['n'], $r['d'], $r['valor']);
        $soma('deducoes', 'Descontos concedidos', $r['d'], $r['desconto']);
        $soma('fin_receita', 'Juros, multas e acréscimos recebidos', $r['d'], (float) $r['juros'] + (float) $r['multa'] + (float) $r['taxa']);
    }
    foreach (fin_q($pdo, "SELECT t.data_lanc d, t.valor, t.desconto, t.juros, t.multa, t.taxa, $grupo_pag g, $nome_pag n
        FROM pagar t LEFT JOIN categorias_pagar cp ON cp.id = t.categoria_pagar
        WHERE t.data_lanc BETWEEN ? AND ? AND COALESCE(t.referencia, '') <> 'Cancelamento' $cc_t", [$ini, $fim])->fetchAll(PDO::FETCH_ASSOC) as $r) {
        $soma($r['g'], $r['n'], $r['d'], $r['valor']);
        $soma('fin_receita', 'Descontos obtidos', $r['d'], $r['desconto']);
        $soma('fin_despesa', 'Juros, multas e acréscimos pagos', $r['d'], (float) $r['juros'] + (float) $r['multa'] + (float) $r['taxa']);
    }
} else {
    // Caixa: o que efetivamente entrou/saiu, pela data do pagamento
    foreach (fin_q($pdo, "SELECT rp.data_pgto d, rp.valor, $grupo_rec g, $nome_rec n
        FROM receber_pagamentos rp JOIN receber t ON t.id = rp.id_receber LEFT JOIN categorias_receber cr ON cr.id = t.categoria_receber
        WHERE rp.data_pgto BETWEEN ? AND ? $cc_t", [$ini, $fim])->fetchAll(PDO::FETCH_ASSOC) as $r) {
        $soma($r['g'], $r['n'], $r['d'], $r['valor']);
    }
    // Títulos antigos marcados como pagos sem registro de pagamento
    foreach (fin_q($pdo, "SELECT t.data_pgto d, COALESCE(t.subtotal, t.valor) valor, $grupo_rec g, $nome_rec n
        FROM receber t LEFT JOIN categorias_receber cr ON cr.id = t.categoria_receber
        WHERE t.pago = 'Sim' AND t.data_pgto BETWEEN ? AND ? $cc_t AND NOT EXISTS (SELECT 1 FROM receber_pagamentos rp WHERE rp.id_receber = t.id)", [$ini, $fim])->fetchAll(PDO::FETCH_ASSOC) as $r) {
        $soma($r['g'], $r['n'], $r['d'], $r['valor']);
    }
    foreach (fin_q($pdo, "SELECT pp.data_pgto d, pp.valor, t.referencia, $grupo_pag g, $nome_pag n
        FROM pagar_pagamentos pp JOIN pagar t ON t.id = pp.id_pagar LEFT JOIN categorias_pagar cp ON cp.id = t.categoria_pagar
        WHERE pp.data_pgto BETWEEN ? AND ? $cc_t", [$ini, $fim])->fetchAll(PDO::FETCH_ASSOC) as $r) {
        if ($r['referencia'] === 'Cancelamento') $soma('deducoes', 'Devoluções de vendas', $r['d'], $r['valor']);
        else $soma($r['g'], $r['n'], $r['d'], $r['valor']);
    }
    foreach (fin_q($pdo, "SELECT t.data_pgto d, COALESCE(t.subtotal, t.valor) valor, t.referencia, $grupo_pag g, $nome_pag n
        FROM pagar t LEFT JOIN categorias_pagar cp ON cp.id = t.categoria_pagar
        WHERE t.pago = 'Sim' AND t.data_pgto BETWEEN ? AND ? $cc_t AND NOT EXISTS (SELECT 1 FROM pagar_pagamentos pp WHERE pp.id_pagar = t.id)", [$ini, $fim])->fetchAll(PDO::FETCH_ASSOC) as $r) {
        if ($r['referencia'] === 'Cancelamento') $soma('deducoes', 'Devoluções de vendas', $r['d'], $r['valor']);
        else $soma($r['g'], $r['n'], $r['d'], $r['valor']);
    }
}

// Lançamentos avulsos do extrato (tarifas, rendimentos...) entram nos dois regimes pela data
foreach (fin_q($pdo, "SELECT DATE(l.data) d, l.credito, l.debito, $grupo_rec gr, cr.nome nr, $grupo_pag gp, cp.nome np
    FROM linha_bancos l LEFT JOIN categorias_receber cr ON cr.id = l.categoria_receber LEFT JOIN categorias_pagar cp ON cp.id = l.categoria_pagar
    WHERE l.origem = 'manual' AND DATE(l.data) BETWEEN ? AND ? $cc_l", [$ini, $fim])->fetchAll(PDO::FETCH_ASSOC) as $r) {
    if ($r['credito'] > 0) $soma($r['nr'] ? $r['gr'] : 'outras', $r['nr'] ?: 'Entradas avulsas sem categoria', $r['d'], $r['credito']);
    if ($r['debito'] > 0) $soma($r['np'] ? $r['gp'] : 'operacional', $r['np'] ?: 'Saídas avulsas sem categoria', $r['d'], $r['debito']);
}
// As categorias de receita e de despesa usam o mesmo código 'financeira'/'outras': reclassifica pelo lado
$rec_fin = [];
$desp_fin = [];
foreach (fin_q($pdo, "SELECT nome FROM categorias_receber WHERE grupo_dre IN ('financeira', 'outras')")->fetchAll(PDO::FETCH_COLUMN) as $n) $rec_fin[$n] = true;
foreach (['financeira' => ['fin_receita', 'fin_despesa'], 'outras' => ['outras_receitas', 'outras_despesas']] as $g => [$gr, $gd]) {
    foreach ($v[$g] ?? [] as $linha => $vals) {
        $destino = (isset($rec_fin[$linha]) || $linha === 'Entradas avulsas sem categoria') ? $gr : $gd;
        foreach ($vals as $mes => $x) $v[$destino][$linha][$mes] = ($v[$destino][$linha][$mes] ?? 0) + $x;
    }
    unset($v[$g]);
}

$total_grupo = function ($g) use ($v, $meses) {
    $t = array_fill_keys($meses, 0);
    foreach ($v[$g] ?? [] as $vals) foreach ($vals as $m => $x) $t[$m] += $x;
    return $t;
};
$op = function ($a, $b, $sinal = 1) {
    $r = [];
    foreach ($a as $m => $x) $r[$m] = $x + $sinal * ($b[$m] ?? 0);
    return $r;
};

$receita = $total_grupo('receita');
$deducoes = $total_grupo('deducoes');
$liquida = $op($receita, $deducoes, -1);
$custo = $total_grupo('custo');
$bruto = $op($liquida, $custo, -1);
$pessoal = $total_grupo('pessoal');
$operacional = $total_grupo('operacional');
$impostos = $total_grupo('impostos');
$resultado_op = $op($op($op($bruto, $pessoal, -1), $operacional, -1), $impostos, -1);
$fin_r = $total_grupo('fin_receita');
$fin_d = $total_grupo('fin_despesa');
$out_r = $total_grupo('outras_receitas');
$out_d = $total_grupo('outras_despesas');
$liquido = $op($op($op($op($resultado_op, $fin_r), $fin_d, -1), $out_r), $out_d, -1);

$fmt = fn($x) => $x == 0 ? '<span class="text-muted">—</span>' : number_format($x, 2, ',', '.');
$rot_mes = fn($m) => $m === 'total' ? 'Período' : ['Jan', 'Fev', 'Mar', 'Abr', 'Mai', 'Jun', 'Jul', 'Ago', 'Set', 'Out', 'Nov', 'Dez'][(int) substr($m, 5, 2) - 1] . '/' . substr($m, 2, 2);
$mostrar_total = count($meses) > 1;

$linha_total = function ($titulo, $vals, $classe = 'table-light fw-bold', $pct_base = null) use ($fmt, $mostrar_total, $receita) {
    $tot = array_sum($vals);
    $pct = $pct_base !== null && array_sum($receita) != 0 ? number_format($tot / array_sum($receita) * 100, 1, ',', '.') . '%' : '';
    echo "<tr class='$classe'><td>$titulo</td>";
    foreach ($vals as $x) echo "<td class='text-end" . ($x < 0 ? ' text-danger' : '') . "'>" . $fmt($x) . "</td>";
    if ($mostrar_total) echo "<td class='text-end" . ($tot < 0 ? ' text-danger' : '') . "'>" . $fmt($tot) . "</td>";
    echo "<td class='text-end small text-muted'>$pct</td></tr>";
};
$grupo_html = function ($titulo, $g, $sinal) use ($v, $meses, $fmt, $mostrar_total, $total_grupo, $linha_total) {
    $linha_total("$sinal $titulo", $total_grupo($g), 'fw-semibold');
    $linhas = $v[$g] ?? [];
    ksort($linhas);
    foreach ($linhas as $nome => $vals) {
        echo "<tr class='small'><td class='ps-4 text-muted'>" . fin_e($nome) . "</td>";
        $tot = 0;
        foreach ($meses as $m) { $x = $vals[$m] ?? 0; $tot += $x; echo "<td class='text-end text-muted'>" . $fmt($x) . "</td>"; }
        if ($mostrar_total) echo "<td class='text-end text-muted'>" . $fmt($tot) . "</td>";
        echo "<td></td></tr>";
    }
};
?>
<div class="table-responsive">
<table class="table table-sm table-bordered mb-0" style="font-size: 13px">
    <thead class="table-dark">
        <tr><th style="min-width: 260px">DRE gerencial — regime de <?= $regime === 'caixa' ? 'caixa' : 'competência' ?></th>
            <?php foreach ($meses as $m) echo '<th class="text-end">' . $rot_mes($m) . '</th>'; ?>
            <?php if ($mostrar_total) echo '<th class="text-end">Total</th>'; ?>
            <th class="text-end">% receita</th></tr>
    </thead>
    <tbody>
        <?php
        $grupo_html('Receita bruta', 'receita', '');
        $grupo_html($regime === 'caixa' ? 'Devoluções' : 'Deduções (descontos concedidos)', 'deducoes', '(−)');
        $linha_total('(=) Receita líquida', $liquida, 'table-light fw-bold', 1);
        $grupo_html('Custos (mercadoria / produtor)', 'custo', '(−)');
        $linha_total('(=) Lucro bruto', $bruto, 'table-light fw-bold', 1);
        $grupo_html('Despesas com pessoal', 'pessoal', '(−)');
        $grupo_html('Despesas operacionais', 'operacional', '(−)');
        $grupo_html('Impostos e taxas', 'impostos', '(−)');
        $linha_total('(=) Resultado operacional', $resultado_op, 'table-light fw-bold', 1);
        $grupo_html('Receitas financeiras', 'fin_receita', '(+)');
        $grupo_html('Despesas financeiras', 'fin_despesa', '(−)');
        $grupo_html('Outras receitas', 'outras_receitas', '(+)');
        $grupo_html('Outras despesas', 'outras_despesas', '(−)');
        $linha_total('(=) Resultado líquido', $liquido, 'table-primary fw-bold', 1);
        ?>
    </tbody>
</table>
</div>
