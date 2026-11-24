<?php
require_once("verificar.php");
require_once(__DIR__ . "/../funcoes/financeiro.php");
$pag = 'fluxo_caixa';

if (@$fluxo_caixa == 'ocultar') {
    echo "<script>window.location='index'</script>";
    exit();
}
?>
<div class="d-flex justify-content-between align-items-center mt-2 mb-3 flex-wrap gap-2">
    <h4 class="mb-0">Fluxo de caixa</h4>
    <button class="btn btn-outline-dark btn-sm" onclick="window.print()"><i class="fa fa-print me-1"></i>Imprimir</button>
</div>

<div class="row g-2 mb-3 align-items-end">
    <div class="col-auto">
        <label class="small">Conta</label>
        <select id="fc-banco" class="form-select form-select-sm" onchange="buscar()"><?= fin_options($pdo, "SELECT id, banco FROM bancos ORDER BY banco", null, 'Todas as contas') ?></select>
    </div>
    <div class="col-auto">
        <label class="small">De</label>
        <input type="date" id="fc-ini" class="form-control form-control-sm" value="<?= date('Y-m-01') ?>" onchange="buscar()">
    </div>
    <div class="col-auto">
        <label class="small">Até</label>
        <input type="date" id="fc-fim" class="form-control form-control-sm" value="<?= date('Y-m-d', strtotime('+90 days')) ?>" onchange="buscar()">
    </div>
    <div class="col-auto">
        <label class="small">Agrupar por</label>
        <select id="fc-grupo" class="form-select form-select-sm" onchange="buscar()">
            <option value="dia">Dia</option>
            <option value="semana" selected>Semana</option>
            <option value="mes">Mês</option>
        </select>
    </div>
    <div class="col-auto form-check ms-2 mb-1">
        <input type="checkbox" class="form-check-input" id="fc-atraso" checked onchange="buscar()">
        <label class="form-check-label small" for="fc-atraso">Contar títulos vencidos como previstos para hoje</label>
    </div>
</div>

<div class="row g-2 mb-3" id="fc-cards"></div>

<div class="card custom-card">
    <div class="card-body">
        <div class="d-flex justify-content-between"><b>Saldo projetado ao fim de cada período</b><small class="text-muted">Linha tracejada: saldo zero</small></div>
        <div style="height: 280px"><canvas id="fc-grafico"></canvas></div>
    </div>
</div>

<div class="card custom-card">
    <div class="card-body p-0" id="fc-tabela"></div>
</div>
<small class="text-muted">Realizado = movimentos do extrato até hoje. Previsto = o que falta receber/pagar dos títulos em aberto, pelo vencimento.
    As previsões não têm conta definida: com uma conta selecionada, elas entram integralmente nela.</small>

<script>
    var fcGrafico = null;
    var brl = function (v) { return (v || 0).toLocaleString('pt-BR', { style: 'currency', currency: 'BRL' }); };

    function card(titulo, valor, cls, sub) {
        return '<div class="col-md-3"><div class="card custom-card mb-0"><div class="card-body py-2"><small class="text-muted">' + titulo + '</small>' +
            '<div class="fs-5 fw-bold ' + (cls || '') + '">' + valor + '</div>' + (sub ? '<small class="text-muted">' + sub + '</small>' : '') + '</div></div></div>';
    }

    function buscar() {
        $.post('paginas/fluxo_caixa/dados.php', {
            banco: $('#fc-banco').val(), ini: $('#fc-ini').val(), fim: $('#fc-fim').val(), grupo: $('#fc-grupo').val(),
            atraso: $('#fc-atraso').is(':checked') ? '1' : '0'
        }, desenhar, 'json');
    }

    function desenhar(d) {
        var t = d.totais;
        $('#fc-cards').html(
            card('Saldo no início', brl(d.saldo_inicial), d.saldo_inicial < 0 ? 'text-danger' : '') +
            card('Entradas (realizado + previsto)', brl(t.real_ent + t.prev_ent), 'text-success', 'previsto: ' + brl(t.prev_ent)) +
            card('Saídas (realizado + previsto)', brl(t.real_sai + t.prev_sai), 'text-danger', 'previsto: ' + brl(t.prev_sai)) +
            card('Saldo projetado no fim', brl(d.saldo_final), d.saldo_final < 0 ? 'text-danger' : 'text-success',
                d.menor ? 'menor saldo: ' + brl(d.menor.saldo) + ' (' + d.menor.rotulo + ')' : '')
        );

        var html = '<table class="table table-sm table-bordered mb-0" style="font-size:13px"><thead class="table-light"><tr>' +
            '<th>Período</th><th class="text-end">Entradas realizadas</th><th class="text-end">Saídas realizadas</th>' +
            '<th class="text-end">A receber</th><th class="text-end">A pagar</th><th class="text-end">Saldo</th></tr></thead><tbody>' +
            '<tr class="table-light"><td><b>Saldo inicial</b></td><td colspan="4"></td><td class="text-end fw-bold">' + brl(d.saldo_inicial) + '</td></tr>';
        d.linhas.forEach(function (l) {
            var z = function (v, cls) { return '<td class="text-end ' + (v ? cls : 'text-muted') + '">' + (v ? brl(v) : '—') + '</td>'; };
            html += '<tr><td>' + l.rotulo + '</td>' + z(l.real_ent, 'text-success') + z(l.real_sai, 'text-danger') +
                z(l.prev_ent, 'text-success') + z(l.prev_sai, 'text-danger') +
                '<td class="text-end fw-bold ' + (l.saldo < 0 ? 'text-danger' : '') + '">' + brl(l.saldo) + '</td></tr>';
        });
        html += '</tbody></table>';
        if (d.atraso_rec || d.atraso_pag) {
            html += '<div class="p-2 small text-muted">Em atraso hoje: a receber ' + brl(d.atraso_rec) + ' · a pagar ' + brl(d.atraso_pag) +
                ($('#fc-atraso').is(':checked') ? ' (incluídos no período atual)' : ' (fora da projeção)') + '</div>';
        }
        $('#fc-tabela').html(html);

        var ctx = document.getElementById('fc-grafico').getContext('2d');
        if (fcGrafico) fcGrafico.destroy();
        fcGrafico = new Chart(ctx, {
            type: 'line',
            data: {
                labels: d.linhas.map(function (l) { return l.rotulo; }),
                datasets: [
                    { label: 'Saldo projetado', data: d.linhas.map(function (l) { return l.saldo; }), borderColor: '#2a6fdb', backgroundColor: 'rgba(42,111,219,0.08)',
                      borderWidth: 2, pointRadius: 3, pointHoverRadius: 5, pointBackgroundColor: '#2a6fdb', fill: true, lineTension: 0 },
                    { label: 'Zero', data: d.linhas.map(function () { return 0; }), borderColor: '#9aa0a6', borderDash: [4, 4], borderWidth: 1,
                      pointRadius: 0, pointHitRadius: 0, fill: false }
                ]
            },
            options: {
                maintainAspectRatio: false,
                legend: { display: false },
                tooltips: { mode: 'index', intersect: false, filter: function (i) { return i.datasetIndex === 0; },
                    callbacks: { label: function (i) { return 'Saldo: ' + brl(i.yLabel); } } },
                hover: { mode: 'index', intersect: false },
                scales: {
                    xAxes: [{ gridLines: { display: false }, ticks: { maxRotation: 0, autoSkipPadding: 12, fontColor: '#6c757d' } }],
                    yAxes: [{ gridLines: { color: 'rgba(0,0,0,0.06)', zeroLineColor: 'rgba(0,0,0,0.06)' },
                        ticks: { fontColor: '#6c757d', callback: function (v) { return brl(v).replace(',00', ''); } } }]
                }
            }
        });
    }

    $(function () { buscar(); });
</script>
