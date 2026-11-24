<?php
require_once("verificar.php");
require_once(__DIR__ . "/../funcoes/financeiro.php");
$pag = 'detalhes_bancos';

if (@$contas_bancarias == 'ocultar') {
    echo "<script>window.location='index'</script>";
    exit();
}
$banco_sel = (int) ($_GET['banco'] ?? 0);
?>

<div class="d-flex justify-content-between align-items-center mt-2 mb-3 flex-wrap gap-2">
    <h4 class="mb-0">Extrato bancário</h4>
    <div class="d-flex gap-2 flex-wrap">
        <a class="btn btn-success btn-sm text-white" onclick="abrirLancamento('credito')"><i class="fa fa-plus me-1"></i>Entrada avulsa</a>
        <a class="btn btn-danger btn-sm text-white" onclick="abrirLancamento('debito')"><i class="fa fa-minus me-1"></i>Saída avulsa</a>
        <a class="btn btn-secondary btn-sm text-white" onclick="abrirTransferencia()"><i class="fa fa-exchange me-1"></i>Transferência</a>
        <a class="btn btn-outline-primary btn-sm" href="conciliacao"><i class="fa fa-check-square-o me-1"></i>Conciliação</a>
        <a class="btn btn-outline-dark btn-sm" href="#" onclick="exportar()"><i class="fa fa-file-excel-o me-1"></i>Exportar CSV</a>
    </div>
</div>

<div class="row g-2 mb-3 align-items-end">
    <div class="col-auto">
        <label class="small">Conta</label>
        <select id="f-banco" class="form-select form-select-sm" onchange="buscar()">
            <?= fin_options($pdo, "SELECT id, banco FROM bancos ORDER BY banco", $banco_sel ?: null, 'Todas as contas') ?>
        </select>
    </div>
    <div class="col-auto">
        <label class="small">De</label>
        <input type="date" id="f-ini" class="form-control form-control-sm" value="<?= date('Y-m-01') ?>" onchange="buscar()">
    </div>
    <div class="col-auto">
        <label class="small">Até</label>
        <input type="date" id="f-fim" class="form-control form-control-sm" value="<?= date('Y-m-t') ?>" onchange="buscar()">
    </div>
    <div class="col-auto">
        <label class="small">Tipo</label>
        <select id="f-tipo" class="form-select form-select-sm" onchange="buscar()">
            <option value="">Entradas e saídas</option>
            <option value="credito">Só entradas</option>
            <option value="debito">Só saídas</option>
        </select>
    </div>
    <div class="col-auto">
        <label class="small">Conciliação</label>
        <select id="f-conc" class="form-select form-select-sm" onchange="buscar()">
            <option value="">Todos</option>
            <option value="0">A conciliar</option>
            <option value="1">Conciliados</option>
        </select>
    </div>
    <div class="col-auto">
        <label class="small">Origem</label>
        <select id="f-origem" class="form-select form-select-sm" onchange="buscar()">
            <option value="">Todas</option>
            <option value="receber_pgto">Recebimentos</option>
            <option value="pagar_pgto">Pagamentos</option>
            <option value="manual">Lançamentos avulsos</option>
            <option value="transferencia">Transferências</option>
            <option value="adiantamento">Adiantamentos</option>
            <option value="legado">Antigos sem vínculo</option>
        </select>
    </div>
    <div class="col">
        <label class="small">Buscar</label>
        <input type="text" id="f-busca" class="form-control form-control-sm" placeholder="Descrição ou documento" onkeyup="if(event.key==='Enter')buscar()">
    </div>
</div>

<div class="card custom-card"><div class="card-body" id="listar">Carregando...</div></div>

<!-- Modal lançamento avulso -->
<div class="modal fade" id="modalLancamento" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header text-white" id="lanc-header">
                <h5 class="modal-title" id="lanc-titulo">Lançamento</h5>
                <button class="btn-close btn-close-white" data-bs-dismiss="modal" type="button" id="btn-fechar-lanc"></button>
            </div>
            <form id="form-lancamento">
                <div class="modal-body">
                    <div class="row g-2">
                        <div class="col-6">
                            <label>Conta</label>
                            <select class="form-select" name="id_banco" required><?= fin_options($pdo, "SELECT id, banco FROM bancos ORDER BY banco", $banco_sel ?: null, 'Selecione...') ?></select>
                        </div>
                        <div class="col-6">
                            <label>Data</label>
                            <input type="date" class="form-control" name="data" value="<?= date('Y-m-d') ?>" required>
                        </div>
                        <div class="col-8">
                            <label>Descrição</label>
                            <input type="text" class="form-control" name="descricao" required placeholder="Ex.: Tarifa pacote de serviços">
                        </div>
                        <div class="col-4">
                            <label>Valor</label>
                            <input type="text" class="form-control" name="valor" required placeholder="0,00">
                        </div>
                        <div class="col-6 lanc-credito">
                            <label>Categoria de receita</label>
                            <select class="form-select" name="categoria_receber"><?= fin_options($pdo, "SELECT id, nome FROM categorias_receber ORDER BY nome", null, 'Sem categoria') ?></select>
                        </div>
                        <div class="col-6 lanc-debito">
                            <label>Categoria de despesa</label>
                            <select class="form-select" name="categoria_pagar"><?= fin_options($pdo, "SELECT id, nome FROM categorias_pagar ORDER BY nome", null, 'Sem categoria') ?></select>
                        </div>
                        <div class="col-6">
                            <label>Centro de custo</label>
                            <select class="form-select" name="centro_custo"><?= fin_options($pdo, "SELECT id, nome FROM centros_custo WHERE ativo = 1 ORDER BY nome", null, 'Nenhum') ?></select>
                        </div>
                        <div class="col-6">
                            <label>Documento</label>
                            <input type="text" class="form-control" name="documento">
                        </div>
                        <div class="col-12">
                            <label>Observação</label>
                            <input type="text" class="form-control" name="obs">
                        </div>
                    </div>
                    <small class="text-muted d-block mt-2">Use para o que não passa por Contas a Pagar/Receber: tarifas, rendimentos, IOF, depósitos não identificados...</small>
                    <input type="hidden" name="tipo" id="lanc-tipo">
                    <div id="msg-lanc" class="text-danger small mt-2"></div>
                </div>
                <div class="modal-footer"><button type="submit" class="btn btn-success">Lançar</button></div>
            </form>
        </div>
    </div>
</div>

<?php include __DIR__ . '/detalhes_bancos/modal_transferencia.php'; ?>

<script>
    var pag = 'detalhes_bancos';

    function filtros() {
        return {
            banco: $('#f-banco').val(), ini: $('#f-ini').val(), fim: $('#f-fim').val(), tipo: $('#f-tipo').val(),
            conciliado: $('#f-conc').val(), origem: $('#f-origem').val(), busca: $('#f-busca').val()
        };
    }

    function buscar() {
        $.post('paginas/detalhes_bancos/listar.php', filtros(), function (html) { $('#listar').html(html); });
    }

    function exportar() {
        window.open('paginas/detalhes_bancos/exportar.php?' + $.param(filtros()), '_blank');
    }

    function abrirLancamento(tipo) {
        $('#form-lancamento')[0].reset();
        $('#lanc-tipo').val(tipo);
        $('#msg-lanc').text('');
        $('#lanc-titulo').text(tipo === 'credito' ? 'Entrada avulsa' : 'Saída avulsa');
        $('#lanc-header').removeClass('bg-success bg-danger').addClass(tipo === 'credito' ? 'bg-success' : 'bg-danger');
        $('.lanc-credito').toggle(tipo === 'credito');
        $('.lanc-debito').toggle(tipo === 'debito');
        if ($('#f-banco').val()) $('#form-lancamento [name=id_banco]').val($('#f-banco').val());
        $('#modalLancamento').modal('show');
    }

    $('#form-lancamento').on('submit', function (e) {
        e.preventDefault();
        $.post('paginas/detalhes_bancos/lancar.php', $(this).serialize(), function (m) {
            if (m.trim() == 'Lançado com Sucesso') { $('#btn-fechar-lanc').click(); buscar(); }
            else $('#msg-lanc').text(m);
        });
    });

    function conciliar(id, marcado) {
        $.post('paginas/detalhes_bancos/conciliar.php', { id: id, conciliado: marcado ? 1 : 0 }, function (m) {
            if (m.trim() != 'Salvo com Sucesso') { Swal.fire({ icon: 'warning', text: m }); }
            buscar();
        });
    }

    function excluirLancamento(id) {
        Swal.fire({ title: 'Excluir lançamento?', text: 'Transferências são excluídas nas duas contas.', icon: 'warning', showCancelButton: true,
            confirmButtonText: 'Excluir', cancelButtonText: 'Cancelar', confirmButtonColor: '#d33' }).then(function (r) {
            if (!r.isConfirmed) return;
            $.post('paginas/detalhes_bancos/excluir.php', { id: id }, function (m) {
                if (m.trim() == 'Excluído com Sucesso') buscar(); else Swal.fire({ icon: 'warning', text: m });
            });
        });
    }

    $(function () { buscar(); });
</script>
