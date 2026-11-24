<?php
require_once("verificar.php");
require_once(__DIR__ . "/../funcoes/financeiro.php");
$pag = 'dre';

if (@$dre == 'ocultar') {
    echo "<script>window.location='index'</script>";
    exit();
}
?>
<div class="d-flex justify-content-between align-items-center mt-2 mb-3 flex-wrap gap-2">
    <h4 class="mb-0">DRE gerencial</h4>
    <div class="d-flex gap-2">
        <a class="btn btn-outline-secondary btn-sm" href="categorias_pagar">Categorias de despesa</a>
        <a class="btn btn-outline-secondary btn-sm" href="categorias_receber">Categorias de receita</a>
        <button class="btn btn-outline-dark btn-sm" onclick="window.print()"><i class="fa fa-print me-1"></i>Imprimir</button>
    </div>
</div>

<div class="row g-2 mb-3 align-items-end">
    <div class="col-auto">
        <label class="small">De</label>
        <input type="date" id="d-ini" class="form-control form-control-sm" value="<?= date('Y-01-01') ?>" onchange="buscar()">
    </div>
    <div class="col-auto">
        <label class="small">Até</label>
        <input type="date" id="d-fim" class="form-control form-control-sm" value="<?= date('Y-m-t') ?>" onchange="buscar()">
    </div>
    <div class="col-auto">
        <label class="small">Regime</label>
        <select id="d-regime" class="form-select form-select-sm" onchange="buscar()">
            <option value="competencia">Competência (pelo faturamento)</option>
            <option value="caixa">Caixa (pelo pagamento)</option>
        </select>
    </div>
    <div class="col-auto">
        <label class="small">Centro de custo</label>
        <select id="d-centro" class="form-select form-select-sm" onchange="buscar()"><?= fin_options($pdo, "SELECT id, nome FROM centros_custo ORDER BY nome", null, 'Todos') ?></select>
    </div>
</div>

<div class="card custom-card"><div class="card-body p-0" id="listar">Carregando...</div></div>
<small class="text-muted">Cada categoria de despesa/receita pertence a um grupo da DRE (custo, pessoal, operacional, impostos, financeira, outras), definido no cadastro de categorias.
    Competência: receitas e despesas pelo mês em que foram lançadas/faturadas. Caixa: pelo mês em que foram efetivamente pagas.</small>

<script>
    function buscar() {
        $.post('paginas/dre/dados.php', { ini: $('#d-ini').val(), fim: $('#d-fim').val(), regime: $('#d-regime').val(), centro: $('#d-centro').val() },
            function (html) { $('#listar').html(html); });
    }
    $(function () { buscar(); });
</script>
