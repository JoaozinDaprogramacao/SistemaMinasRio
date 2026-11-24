<?php
require_once("verificar.php");
require_once(__DIR__ . "/../funcoes/financeiro.php");
$pag = 'bancos';

if (@$contas_bancarias == 'ocultar') {
    echo "<script>window.location='index'</script>";
    exit();
}
$pendentes = fin_recebimentos_fora_do_extrato($pdo);
$total_pendentes = array_sum(array_column($pendentes, 'valor'));
$eh_admin = @$_SESSION['nivel'] == 'Administrador';
?>

<div class="d-flex justify-content-between align-items-center mt-2 mb-3 flex-wrap gap-2">
    <div class="d-flex gap-2">
        <a class="btn btn-primary text-white" onclick="novaConta()"><i class="fe fe-plus me-2"></i>Nova conta</a>
        <a class="btn btn-outline-primary" href="detalhes_bancos"><i class="fa fa-list me-1"></i>Extrato</a>
        <a class="btn btn-outline-primary" href="conciliacao"><i class="fa fa-check-square-o me-1"></i>Conciliação</a>
        <a class="btn btn-outline-secondary" href="#" onclick="abrirTransferencia()"><i class="fa fa-exchange me-1"></i>Transferir entre contas</a>
    </div>
</div>

<?php if ($pendentes) { ?>
    <div class="alert alert-warning d-flex justify-content-between align-items-center flex-wrap gap-2">
        <div>
            <b><i class="fa fa-exclamation-triangle me-1"></i><?= count($pendentes) ?> recebimentos antigos (<?= fin_moeda($total_pendentes) ?>) não estão no extrato.</b><br>
            <small>Antes desta atualização, as baixas de Contas a Receber não lançavam no banco, por isso os saldos aparecem mais baixos que o real.
                O Saneamento Financeiro corrige isso e outros dados antigos.</small>
        </div>
        <?php if ($eh_admin) { ?>
            <a class="btn btn-warning" href="saneamento_fin"><i class="fa fa-medkit me-1"></i>Abrir saneamento</a>
        <?php } else { ?>
            <small class="text-muted">Peça ao administrador para executar o Saneamento Financeiro.</small>
        <?php } ?>
    </div>
<?php } ?>

<div id="listar"></div>

<!-- Modal conta -->
<div class="modal fade" id="modalConta" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header bg-primary text-white">
                <h5 class="modal-title" id="titulo-conta">Nova conta</h5>
                <button class="btn-close btn-close-white" data-bs-dismiss="modal" type="button" id="btn-fechar-conta"></button>
            </div>
            <form id="form-conta">
                <div class="modal-body">
                    <div class="row g-2">
                        <div class="col-12">
                            <label>Nome da conta (como aparece nas telas)</label>
                            <input type="text" class="form-control" name="banco" id="c-banco" required placeholder="Ex.: BRADESCO MINAS RIO">
                        </div>
                        <div class="col-12">
                            <label>Titular / Correntista</label>
                            <input type="text" class="form-control" name="correntista" id="c-correntista">
                        </div>
                        <div class="col-6">
                            <label>Agência</label>
                            <input type="text" class="form-control" name="agencia" id="c-agencia">
                        </div>
                        <div class="col-6">
                            <label>Conta</label>
                            <input type="text" class="form-control" name="conta" id="c-conta">
                        </div>
                        <div class="col-12"><hr class="my-2"></div>
                        <div class="col-6">
                            <label>Saldo inicial</label>
                            <input type="text" class="form-control" name="saldo_inicial" id="c-saldo" placeholder="0,00">
                            <small class="text-muted">Negativo se a conta começou devedora (ex.: -1.500,00)</small>
                        </div>
                        <div class="col-6">
                            <label>Data do saldo inicial</label>
                            <input type="date" class="form-control" name="data_saldo_inicial" id="c-data-saldo">
                        </div>
                        <div class="col-12">
                            <small class="text-muted">O saldo atual da conta é sempre calculado pelo extrato: saldo inicial + entradas − saídas. Ele não é digitado.</small>
                        </div>
                    </div>
                    <input type="hidden" name="id" id="c-id">
                    <div id="msg-conta" class="text-danger small mt-2"></div>
                </div>
                <div class="modal-footer"><button type="submit" class="btn btn-success">Salvar</button></div>
            </form>
        </div>
    </div>
</div>

<?php include __DIR__ . '/detalhes_bancos/modal_transferencia.php'; ?>

<script>
    var pag = 'bancos';

    function buscar() {
        $.post('paginas/bancos/listar.php', {}, function (html) { $('#listar').html(html); });
    }

    function novaConta() {
        $('#form-conta')[0].reset();
        $('#c-id').val('');
        $('#titulo-conta').text('Nova conta');
        $('#c-data-saldo').val(new Date().toISOString().slice(0, 10));
        $('#msg-conta').text('');
        $('#modalConta').modal('show');
    }

    function editarConta(id) {
        $.post('paginas/bancos/buscar.php', { id: id }, function (c) {
            $('#c-id').val(c.id);
            $('#c-banco').val(c.banco);
            $('#c-correntista').val(c.correntista);
            $('#c-agencia').val(c.agencia);
            $('#c-conta').val(c.conta);
            $('#c-saldo').val(c.saldo_inicial);
            $('#c-data-saldo').val(c.data_saldo_inicial);
            $('#titulo-conta').text('Editar conta');
            $('#msg-conta').text('');
            $('#modalConta').modal('show');
        }, 'json');
    }

    $('#form-conta').on('submit', function (e) {
        e.preventDefault();
        $.post('paginas/bancos/salvar.php', $(this).serialize(), function (m) {
            if (m.trim() == 'Salvo com Sucesso') { $('#btn-fechar-conta').click(); buscar(); }
            else $('#msg-conta').text(m);
        });
    });

    function excluirConta(id) {
        Swal.fire({ title: 'Excluir conta?', text: 'Só é possível excluir contas sem movimentação.', icon: 'warning', showCancelButton: true, confirmButtonText: 'Excluir', cancelButtonText: 'Cancelar', confirmButtonColor: '#d33' })
            .then(function (r) {
                if (!r.isConfirmed) return;
                $.post('paginas/bancos/excluir.php', { id: id }, function (m) {
                    if (m.trim() == 'Excluído com Sucesso') buscar(); else Swal.fire({ icon: 'warning', text: m });
                });
            });
    }

    $(function () { buscar(); });
</script>
