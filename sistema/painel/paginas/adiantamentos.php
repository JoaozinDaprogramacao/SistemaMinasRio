<?php
require_once("verificar.php");
require_once(__DIR__ . "/../funcoes/financeiro.php");
$pag = 'adiantamentos';

if (@$adiantamentos == 'ocultar') {
    echo "<script>window.location='index'</script>";
    exit();
}
?>
<div class="d-flex justify-content-between align-items-center mt-2 mb-3 flex-wrap gap-2">
    <div>
        <h4 class="mb-0">Adiantamentos e créditos</h4>
        <small class="text-muted">Dinheiro recebido de cliente antes da venda, ou pago a fornecedor antes da compra. O saldo é usado depois na baixa dos títulos (forma "Usar adiantamento").</small>
    </div>
    <a class="btn btn-primary text-white" onclick="novoAdi()"><i class="fe fe-plus me-2"></i>Novo adiantamento</a>
</div>

<div class="row g-2 mb-3 align-items-end">
    <div class="col-auto">
        <select id="a-tipo" class="form-select form-select-sm" onchange="buscar()">
            <option value="">Clientes e fornecedores</option>
            <option value="cliente">De clientes (recebidos)</option>
            <option value="fornecedor">A fornecedores (pagos)</option>
        </select>
    </div>
    <div class="col-auto">
        <select id="a-saldo" class="form-select form-select-sm" onchange="buscar()">
            <option value="1">Com saldo disponível</option>
            <option value="">Todos</option>
        </select>
    </div>
</div>
<div class="card custom-card"><div class="card-body" id="listar">Carregando...</div></div>

<div class="modal fade" id="modalAdi" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header bg-primary text-white">
                <h5 class="modal-title">Novo adiantamento</h5>
                <button class="btn-close btn-close-white" data-bs-dismiss="modal" type="button" id="btn-fechar-adi"></button>
            </div>
            <form id="form-adi">
                <div class="modal-body">
                    <div class="row g-2">
                        <div class="col-12">
                            <label>Tipo</label>
                            <select class="form-select" name="tipo" id="adi-tipo" onchange="trocarTipo()">
                                <option value="cliente">Recebido de cliente</option>
                                <option value="fornecedor">Pago a fornecedor</option>
                            </select>
                        </div>
                        <div class="col-12 adi-cliente">
                            <label>Cliente</label>
                            <select class="form-select" name="cliente"><?= fin_options($pdo, "SELECT id, nome FROM clientes ORDER BY nome", null, 'Selecione...') ?></select>
                        </div>
                        <div class="col-12 adi-fornecedor" style="display:none">
                            <label>Fornecedor</label>
                            <select class="form-select" name="fornecedor"><?= fin_options($pdo, "SELECT id, nome_atacadista FROM fornecedores ORDER BY nome_atacadista", null, 'Selecione...') ?></select>
                        </div>
                        <div class="col-6">
                            <label>Valor</label>
                            <input type="text" class="form-control" name="valor" required placeholder="0,00">
                        </div>
                        <div class="col-6">
                            <label>Data</label>
                            <input type="date" class="form-control" name="data" value="<?= date('Y-m-d') ?>" required>
                        </div>
                        <div class="col-6">
                            <label>Conta bancária</label>
                            <select class="form-select" name="banco" required><?= fin_options($pdo, "SELECT id, banco FROM bancos ORDER BY banco", null, 'Selecione...') ?></select>
                        </div>
                        <div class="col-6">
                            <label>Forma</label>
                            <select class="form-select" name="forma"><?= fin_options($pdo, "SELECT id, nome FROM formas_pgto ORDER BY nome", null, '—') ?></select>
                        </div>
                        <div class="col-12">
                            <label>Descrição</label>
                            <input type="text" class="form-control" name="descricao" maxlength="150">
                        </div>
                    </div>
                    <div id="msg-adi" class="text-danger small mt-2"></div>
                </div>
                <div class="modal-footer"><button type="submit" class="btn btn-success">Salvar</button></div>
            </form>
        </div>
    </div>
</div>

<script>
    function buscar() { $.post('paginas/adiantamentos/listar.php', { tipo: $('#a-tipo').val(), saldo: $('#a-saldo').val() }, function (h) { $('#listar').html(h); }); }
    function trocarTipo() { var c = $('#adi-tipo').val() === 'cliente'; $('.adi-cliente').toggle(c); $('.adi-fornecedor').toggle(!c); }
    function novoAdi() { $('#form-adi')[0].reset(); trocarTipo(); $('#msg-adi').text(''); $('#modalAdi').modal('show'); }
    $('#form-adi').on('submit', function (e) {
        e.preventDefault();
        $.post('paginas/adiantamentos/salvar.php', $(this).serialize(), function (m) {
            if (m.trim() == 'Salvo com Sucesso') { $('#btn-fechar-adi').click(); buscar(); } else $('#msg-adi').text(m);
        });
    });
    function excluirAdi(id) {
        Swal.fire({ title: 'Excluir adiantamento?', text: 'O lançamento no extrato também será removido.', icon: 'warning', showCancelButton: true,
            confirmButtonText: 'Excluir', cancelButtonText: 'Cancelar', confirmButtonColor: '#d33' }).then(function (r) {
            if (!r.isConfirmed) return;
            $.post('paginas/adiantamentos/excluir.php', { id: id }, function (m) {
                if (m.trim() == 'Excluído com Sucesso') buscar(); else Swal.fire({ icon: 'warning', text: m });
            });
        });
    }
    $(function () { buscar(); });
</script>
