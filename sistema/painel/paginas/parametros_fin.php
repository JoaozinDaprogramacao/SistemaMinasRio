<?php
require_once("verificar.php");
require_once(__DIR__ . "/../funcoes/financeiro.php");
$pag = 'parametros_fin';

if (@$parametros_fin == 'ocultar') {
    echo "<script>window.location='index'</script>";
    exit();
}
$cfg = fin_q($pdo, "SELECT * FROM config LIMIT 1")->fetch(PDO::FETCH_ASSOC) ?: [];
$formas = fin_q($pdo, "SELECT * FROM formas_pgto ORDER BY nome")->fetchAll(PDO::FETCH_ASSOC);
$bancos = fin_q($pdo, "SELECT id, banco FROM bancos ORDER BY banco")->fetchAll(PDO::FETCH_ASSOC);
?>
<div class="mt-2 mb-3"><h4 class="mb-0">Parâmetros financeiros</h4></div>

<form id="form-param">
<div class="row">
    <div class="col-lg-6">
        <div class="card custom-card">
            <div class="card-header"><b><i class="fa fa-lock me-1"></i>Fechamento de período</b></div>
            <div class="card-body">
                <label>Bloquear movimentos até (inclusive)</label>
                <input type="date" class="form-control" name="data_bloqueio_fin" value="<?= fin_e($cfg['data_bloqueio_fin'] ?? '') ?>">
                <small class="text-muted">Depois de conferir e fechar um mês, informe o último dia dele. Ninguém consegue lançar, baixar, estornar ou excluir movimentos com data até esse dia.
                    Deixe em branco para não bloquear. Somente o administrador altera.</small>
            </div>
        </div>
        <div class="card custom-card">
            <div class="card-header"><b><i class="fa fa-qrcode me-1"></i>PIX para cobrança</b></div>
            <div class="card-body">
                <div class="row g-2">
                    <div class="col-12">
                        <label>Chave PIX</label>
                        <input type="text" class="form-control" name="pix_chave" value="<?= fin_e($cfg['pix_chave'] ?? '') ?>" placeholder="CNPJ, e-mail, telefone (+55...) ou chave aleatória">
                    </div>
                    <div class="col-7">
                        <label>Nome do recebedor</label>
                        <input type="text" class="form-control" name="pix_nome" maxlength="25" value="<?= fin_e($cfg['pix_nome'] ?? '') ?>">
                    </div>
                    <div class="col-5">
                        <label>Cidade</label>
                        <input type="text" class="form-control" name="pix_cidade" maxlength="15" value="<?= fin_e($cfg['pix_cidade'] ?? '') ?>">
                    </div>
                </div>
                <small class="text-muted">Com a chave cadastrada, Contas a Receber passa a gerar QR Code e "copia e cola" com o valor de cada título, e a cobrança por WhatsApp leva o código.</small>
            </div>
        </div>
    </div>
    <div class="col-lg-6">
        <div class="card custom-card">
            <div class="card-header"><b><i class="fa fa-credit-card me-1"></i>Formas de pagamento</b></div>
            <div class="card-body p-0">
                <table class="table table-sm mb-0">
                    <thead class="table-light"><tr><th>Forma</th><th>Conta padrão</th><th title="Dias até o dinheiro cair na conta (cartão: 1, 30...)">Cai em (dias)</th><th>Taxa %</th></tr></thead>
                    <tbody>
                    <?php foreach ($formas as $f) { ?>
                        <tr>
                            <td class="align-middle"><?= fin_e($f['nome']) ?></td>
                            <td><select class="form-select form-select-sm" name="formas[<?= (int) $f['id'] ?>][banco]">
                                <option value="">—</option>
                                <?php foreach ($bancos as $b) echo '<option value="' . $b['id'] . '"' . ($f['banco_padrao'] == $b['id'] ? ' selected' : '') . '>' . fin_e($b['banco']) . '</option>'; ?>
                            </select></td>
                            <td style="width: 90px"><input type="number" min="0" max="365" class="form-control form-control-sm" name="formas[<?= (int) $f['id'] ?>][prazo]" value="<?= (int) $f['prazo_recebimento'] ?>"></td>
                            <td style="width: 90px"><input type="text" class="form-control form-control-sm" name="formas[<?= (int) $f['id'] ?>][taxa]" value="<?= number_format((float) $f['taxa'], 2, ',', '') ?>"></td>
                        </tr>
                    <?php } ?>
                    </tbody>
                </table>
            </div>
            <div class="card-footer small text-muted">
                <b>Conta padrão</b>: sugerida na baixa e usada quando uma venda/compra é paga no ato pelo PDV. Sem conta padrão, a entrada da venda fica em aberto para ser baixada manualmente.<br>
                <b>Cai em</b>: para cartões, o recebimento entra no extrato N dias depois da data da baixa.
            </div>
        </div>
    </div>
</div>
<div class="d-flex align-items-center gap-3">
    <button type="submit" class="btn btn-success"><i class="fa fa-save me-1"></i>Salvar parâmetros</button>
    <span id="msg-param" class="small"></span>
</div>
</form>

<script>
    $('#form-param').on('submit', function (e) {
        e.preventDefault();
        $.post('paginas/parametros_fin/salvar.php', $(this).serialize(), function (m) {
            var ok = m.trim() == 'Salvo com Sucesso';
            $('#msg-param').removeClass('text-success text-danger').addClass(ok ? 'text-success' : 'text-danger').text(ok ? 'Parâmetros salvos.' : m);
        });
    });
</script>
