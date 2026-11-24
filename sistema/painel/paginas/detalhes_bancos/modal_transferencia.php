<!-- Modal Transferência entre contas (incluído em Bancos e no Extrato) -->
<div class="modal fade" id="modalTransferencia" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header bg-secondary text-white">
                <h5 class="modal-title"><i class="fa fa-exchange me-1"></i> Transferência entre contas</h5>
                <button class="btn-close btn-close-white" data-bs-dismiss="modal" type="button" id="btn-fechar-transf"></button>
            </div>
            <form id="form-transferencia">
                <div class="modal-body">
                    <div class="row g-2">
                        <div class="col-6">
                            <label>Sai de</label>
                            <select class="form-select" name="origem" required><?= fin_options($pdo, "SELECT id, banco FROM bancos ORDER BY banco", null, 'Selecione...') ?></select>
                        </div>
                        <div class="col-6">
                            <label>Entra em</label>
                            <select class="form-select" name="destino" required><?= fin_options($pdo, "SELECT id, banco FROM bancos ORDER BY banco", null, 'Selecione...') ?></select>
                        </div>
                        <div class="col-6">
                            <label>Valor</label>
                            <input type="text" class="form-control" name="valor" required placeholder="0,00">
                        </div>
                        <div class="col-6">
                            <label>Data</label>
                            <input type="date" class="form-control" name="data" value="<?= date('Y-m-d') ?>" required>
                        </div>
                        <div class="col-8">
                            <label>Descrição</label>
                            <input type="text" class="form-control" name="descricao" placeholder="Opcional">
                        </div>
                        <div class="col-4">
                            <label>Documento</label>
                            <input type="text" class="form-control" name="documento" placeholder="Opcional">
                        </div>
                    </div>
                    <div id="msg-transf" class="text-danger small mt-2"></div>
                </div>
                <div class="modal-footer"><button type="submit" class="btn btn-success">Transferir</button></div>
            </form>
        </div>
    </div>
</div>
<script>
    function abrirTransferencia() {
        $('#form-transferencia')[0].reset();
        $('#msg-transf').text('');
        $('#modalTransferencia').modal('show');
    }
    $(document).on('submit', '#form-transferencia', function (e) {
        e.preventDefault();
        $.post('paginas/detalhes_bancos/transferir.php', $(this).serialize(), function (m) {
            if (m.trim() == 'Transferido com Sucesso') {
                $('#btn-fechar-transf').click();
                if (typeof buscar === 'function') buscar();
            } else {
                $('#msg-transf').text(m);
            }
        });
    });
</script>
