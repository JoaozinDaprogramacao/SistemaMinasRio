<?php
require_once("verificar.php");
require_once(__DIR__ . "/../funcoes/financeiro.php");
$pag = 'conciliacao';

if (@$conciliacao == 'ocultar') {
    echo "<script>window.location='index'</script>";
    exit();
}
$banco_sel = (int) ($_GET['banco'] ?? 0);
if (!$banco_sel) $banco_sel = (int) fin_q($pdo, "SELECT id FROM bancos ORDER BY banco LIMIT 1")->fetchColumn();
?>

<div class="d-flex justify-content-between align-items-center mt-2 mb-3 flex-wrap gap-2">
    <h4 class="mb-0">Conciliação bancária</h4>
    <a class="btn btn-outline-primary btn-sm" href="detalhes_bancos"><i class="fa fa-list me-1"></i>Extrato do sistema</a>
</div>

<div class="card custom-card mb-3">
    <div class="card-body">
        <form id="form-ofx" enctype="multipart/form-data" class="row g-2 align-items-end">
            <div class="col-md-3">
                <label class="small fw-bold">Conta</label>
                <select name="id_banco" id="c-banco" class="form-select form-select-sm" onchange="buscar()">
                    <?= fin_options($pdo, "SELECT id, banco FROM bancos ORDER BY banco", $banco_sel) ?>
                </select>
            </div>
            <div class="col-md-5">
                <label class="small fw-bold">Extrato do banco (arquivo OFX)</label>
                <input type="file" name="arquivo" accept=".ofx,.OFX" class="form-control form-control-sm" required>
            </div>
            <div class="col-md-2">
                <button type="submit" class="btn btn-primary btn-sm w-100"><i class="fa fa-upload me-1"></i>Importar</button>
            </div>
            <div class="col-md-2">
                <button type="button" class="btn btn-outline-success btn-sm w-100" onclick="autoConciliar()" title="Tenta casar de novo os pendentes">
                    <i class="fa fa-magic me-1"></i>Conciliar automático</button>
            </div>
            <div class="col-12">
                <small class="text-muted">No internet banking, exporte o extrato no formato <b>OFX</b> (Money/Quicken). Lançamentos já importados são ignorados,
                    então pode importar períodos sobrepostos. O sistema casa sozinho os lançamentos de mesmo valor com até 3 dias de diferença.</small>
            </div>
        </form>
        <div id="msg-ofx" class="mt-2"></div>
    </div>
</div>

<div class="row g-2 mb-2 align-items-end">
    <div class="col-auto">
        <label class="small">De</label>
        <input type="date" id="c-ini" class="form-control form-control-sm" value="<?= date('Y-m-01', strtotime('-1 month')) ?>" onchange="buscar()">
    </div>
    <div class="col-auto">
        <label class="small">Até</label>
        <input type="date" id="c-fim" class="form-control form-control-sm" value="<?= date('Y-m-t') ?>" onchange="buscar()">
    </div>
</div>

<div id="listar">Carregando...</div>

<!-- Modal criar lançamento a partir do extrato -->
<div class="modal fade" id="modalCriar" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header bg-primary text-white">
                <h5 class="modal-title">Lançar no sistema</h5>
                <button class="btn-close btn-close-white" data-bs-dismiss="modal" type="button" id="btn-fechar-criar"></button>
            </div>
            <form id="form-criar">
                <div class="modal-body">
                    <p id="criar-resumo" class="fw-bold"></p>
                    <div class="row g-2">
                        <div class="col-12">
                            <label>Descrição</label>
                            <input type="text" class="form-control" name="descricao" id="criar-desc">
                        </div>
                        <div class="col-6 criar-credito">
                            <label>Categoria de receita</label>
                            <select class="form-select" name="categoria_receber"><?= fin_options($pdo, "SELECT id, nome FROM categorias_receber ORDER BY nome", null, 'Sem categoria') ?></select>
                        </div>
                        <div class="col-6 criar-debito">
                            <label>Categoria de despesa</label>
                            <select class="form-select" name="categoria_pagar"><?= fin_options($pdo, "SELECT id, nome FROM categorias_pagar ORDER BY nome", null, 'Sem categoria') ?></select>
                        </div>
                        <div class="col-6">
                            <label>Centro de custo</label>
                            <select class="form-select" name="centro_custo"><?= fin_options($pdo, "SELECT id, nome FROM centros_custo WHERE ativo = 1 ORDER BY nome", null, 'Nenhum') ?></select>
                        </div>
                    </div>
                    <small class="text-muted d-block mt-2">Para tarifas, rendimentos e outros movimentos que só aparecem no banco. Recebimentos e pagamentos de títulos devem ser baixados em Contas a Receber/Pagar — depois volte aqui e vincule.</small>
                    <input type="hidden" name="id" id="criar-id">
                    <div id="msg-criar" class="text-danger small mt-2"></div>
                </div>
                <div class="modal-footer"><button type="submit" class="btn btn-success">Lançar e conciliar</button></div>
            </form>
        </div>
    </div>
</div>

<script>
    var pag = 'conciliacao';

    function buscar() {
        $.post('paginas/conciliacao/listar.php', { banco: $('#c-banco').val(), ini: $('#c-ini').val(), fim: $('#c-fim').val() },
            function (html) { $('#listar').html(html); });
    }

    $('#form-ofx').on('submit', function (e) {
        e.preventDefault();
        $('#msg-ofx').html('<span class="text-muted">Importando...</span>');
        $.ajax({
            url: 'paginas/conciliacao/importar.php', type: 'POST', data: new FormData(this), cache: false, contentType: false, processData: false,
            success: function (m) {
                var ok = m.indexOf('Importado') === 0;
                $('#msg-ofx').html('<div class="alert alert-' + (ok ? 'success' : 'warning') + ' py-2 mb-0">' + $('<div>').text(m).html() + '</div>');
                if (ok) { $('#form-ofx [name=arquivo]').val(''); buscar(); }
            }
        });
    });

    function acao(url, dados, confirmar) {
        var enviar = function () {
            $.post('paginas/conciliacao/' + url, dados, function (m) {
                if (m.trim() == 'Salvo com Sucesso') buscar(); else Swal.fire({ icon: 'warning', text: m });
            });
        };
        if (!confirmar) return enviar();
        Swal.fire({ text: confirmar, icon: 'question', showCancelButton: true, confirmButtonText: 'Sim', cancelButtonText: 'Não' })
            .then(function (r) { if (r.isConfirmed) enviar(); });
    }

    function vincular(idExtrato, idLinha) { acao('vincular.php', { extrato: idExtrato, linha: idLinha }); }
    function ignorar(idExtrato) { acao('ignorar.php', { extrato: idExtrato }, 'Ignorar este lançamento do banco? Ele não será considerado na conciliação.'); }
    function desfazer(idExtrato) { acao('desfazer.php', { extrato: idExtrato }, 'Desfazer a conciliação deste lançamento?'); }
    function autoConciliar() {
        $.post('paginas/conciliacao/automatico.php', { banco: $('#c-banco').val() }, function (m) {
            Swal.fire({ icon: 'info', text: m }); buscar();
        });
    }

    function criar(id, valor, descricao) {
        $('#form-criar')[0].reset();
        $('#criar-id').val(id);
        $('#criar-desc').val(descricao);
        $('#criar-resumo').text((valor >= 0 ? 'Entrada de ' : 'Saída de ') + Math.abs(valor).toLocaleString('pt-BR', { style: 'currency', currency: 'BRL' }));
        $('.criar-credito').toggle(valor >= 0);
        $('.criar-debito').toggle(valor < 0);
        $('#msg-criar').text('');
        $('#modalCriar').modal('show');
    }

    $('#form-criar').on('submit', function (e) {
        e.preventDefault();
        $.post('paginas/conciliacao/criar.php', $(this).serialize(), function (m) {
            if (m.trim() == 'Salvo com Sucesso') { $('#btn-fechar-criar').click(); buscar(); }
            else $('#msg-criar').text(m);
        });
    });

    $(function () { buscar(); });
</script>
