<?php
require_once("../../../conexao.php");
require_once(__DIR__ . "/../../seguranca.php");
seguranca_exigir_acesso(['contas_bancarias']);
require_once(__DIR__ . "/../../funcoes/financeiro.php");

$contas = fin_q($pdo, "SELECT b.*,
    COALESCE(SUM(COALESCE(l.credito,0) - COALESCE(l.debito,0)), 0) saldo_calc,
    COALESCE(SUM(CASE WHEN l.conciliado = 1 THEN COALESCE(l.credito,0) - COALESCE(l.debito,0) ELSE 0 END), 0) saldo_conciliado,
    SUM(CASE WHEN l.id IS NOT NULL AND l.conciliado = 0 THEN 1 ELSE 0 END) nao_conciliados,
    MAX(l.data) ultimo_mov,
    MAX(CASE WHEN l.conciliado = 1 THEN l.data END) ultima_conc
    FROM bancos b LEFT JOIN linha_bancos l ON l.id_banco = b.id
    GROUP BY b.id ORDER BY b.banco")->fetchAll(PDO::FETCH_ASSOC);
$total = array_sum(array_column($contas, 'saldo_calc'));
if (!$contas) {
    echo '<div class="alert alert-info">Nenhuma conta cadastrada. Clique em "Nova conta".</div>';
    exit();
}
?>
<div class="card custom-card mb-3">
    <div class="card-body d-flex justify-content-between align-items-center flex-wrap gap-2">
        <div><small class="text-muted text-uppercase fw-bold">Saldo consolidado</small><br>
            <span class="fs-3 fw-bold <?= $total < 0 ? 'text-danger' : 'text-success' ?>"><?= fin_moeda($total) ?></span></div>
        <small class="text-muted text-end">Soma de todas as contas pelo extrato do sistema.<br>Confira com os extratos dos bancos na Conciliação.</small>
    </div>
</div>
<div class="row">
<?php foreach ($contas as $c) { ?>
    <div class="col-md-6 col-xl-4">
        <div class="card custom-card">
            <div class="card-body">
                <div class="d-flex justify-content-between">
                    <div>
                        <h5 class="mb-0"><?= fin_e($c['banco']) ?></h5>
                        <small class="text-muted"><?= fin_e($c['correntista']) ?><br>Ag. <?= fin_e($c['agencia']) ?> · CC <?= fin_e($c['conta']) ?></small>
                    </div>
                    <div class="text-nowrap">
                        <a href="#" onclick="editarConta(<?= (int) $c['id'] ?>)" title="Editar"><i class="fa fa-edit text-primary"></i></a>
                        <a href="#" onclick="excluirConta(<?= (int) $c['id'] ?>)" title="Excluir" class="ms-2"><i class="fa fa-trash text-danger"></i></a>
                    </div>
                </div>
                <div class="mt-3">
                    <small class="text-muted text-uppercase fw-bold">Saldo no sistema</small><br>
                    <span class="fs-4 fw-bold <?= $c['saldo_calc'] < 0 ? 'text-danger' : 'text-success' ?>"><?= fin_moeda($c['saldo_calc']) ?></span>
                </div>
                <div class="d-flex justify-content-between mt-2 small">
                    <span>Saldo conciliado: <b><?= fin_moeda($c['saldo_conciliado']) ?></b></span>
                    <span class="<?= $c['nao_conciliados'] ? 'text-warning' : 'text-success' ?>"><?= (int) $c['nao_conciliados'] ?> a conciliar</span>
                </div>
                <div class="small text-muted mt-1">
                    Último movimento: <?= $c['ultimo_mov'] ? fin_data_br($c['ultimo_mov']) : '—' ?> ·
                    Conciliado até: <?= $c['ultima_conc'] ? fin_data_br($c['ultima_conc']) : '—' ?>
                </div>
                <div class="d-flex gap-2 mt-3">
                    <a class="btn btn-sm btn-outline-primary" href="detalhes_bancos?banco=<?= (int) $c['id'] ?>">Extrato</a>
                    <a class="btn btn-sm btn-outline-success" href="conciliacao?banco=<?= (int) $c['id'] ?>">Conciliar</a>
                </div>
            </div>
        </div>
    </div>
<?php } ?>
</div>
