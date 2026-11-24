<?php
require_once("../../../conexao.php");
require_once(__DIR__ . "/../../seguranca.php");
seguranca_exigir_acesso(['conciliacao']);
require_once(__DIR__ . "/../../funcoes/financeiro.php");

$banco = (int) ($_POST['banco'] ?? 0);
$ini = fin_data_valida($_POST['ini'] ?? '') ? $_POST['ini'] : date('Y-m-01');
$fim = fin_data_valida($_POST['fim'] ?? '') ? $_POST['fim'] : date('Y-m-t');
if (!$banco) { echo '<div class="alert alert-info">Cadastre uma conta bancária.</div>'; exit(); }

$importados = fin_q($pdo, "SELECT e.*, l.descricao desc_sistema, DATE(l.data) data_sistema
    FROM extrato_importado e LEFT JOIN linha_bancos l ON l.id = e.id_linha_banco
    WHERE e.id_banco = ? AND e.data BETWEEN ? AND ? ORDER BY e.status = 'pendente' DESC, e.data, e.id", [$banco, $ini, $fim])->fetchAll(PDO::FETCH_ASSOC);
$sistema_pend = fin_q($pdo, "SELECT * FROM linha_bancos WHERE id_banco = ? AND conciliado = 0 AND DATE(data) BETWEEN ? AND ? ORDER BY data, id", [$banco, $ini, $fim])->fetchAll(PDO::FETCH_ASSOC);

$pend = array_filter($importados, fn($e) => $e['status'] === 'pendente');
$conc = array_filter($importados, fn($e) => $e['status'] === 'conciliado');
$ign = array_filter($importados, fn($e) => $e['status'] === 'ignorado');

// Sugestões: linhas do sistema sem conciliação, mesmo valor e sentido, até 10 dias de diferença
$sugestoes = function ($e) use ($sistema_pend) {
    $lista = [];
    foreach ($sistema_pend as $l) {
        $v = round($l['credito'] - $l['debito'], 2);
        if ($v != round($e['valor'], 2)) continue;
        $dias = abs((strtotime(substr($l['data'], 0, 10)) - strtotime($e['data'])) / 86400);
        if ($dias <= 10) $lista[] = $l + ['dias' => $dias];
    }
    usort($lista, fn($a, $b) => $a['dias'] <=> $b['dias']);
    return array_slice($lista, 0, 3);
};
$saldo_sistema = fin_saldo($pdo, $banco, $fim);
$saldo_conc = (float) fin_q($pdo, "SELECT COALESCE(SUM(credito - debito), 0) FROM linha_bancos WHERE id_banco = ? AND conciliado = 1 AND DATE(data) <= ?", [$banco, $fim])->fetchColumn();
?>
<div class="row g-2 mb-3">
    <div class="col-md-3"><div class="card custom-card mb-0"><div class="card-body py-2"><small class="text-muted">Do banco a tratar</small><div class="fs-4 fw-bold text-warning"><?= count($pend) ?></div></div></div></div>
    <div class="col-md-3"><div class="card custom-card mb-0"><div class="card-body py-2"><small class="text-muted">Do sistema sem conciliar</small><div class="fs-4 fw-bold text-warning"><?= count($sistema_pend) ?></div></div></div></div>
    <div class="col-md-3"><div class="card custom-card mb-0"><div class="card-body py-2"><small class="text-muted">Saldo no sistema em <?= fin_data_br($fim) ?></small><div class="fs-5 fw-bold"><?= fin_moeda($saldo_sistema) ?></div></div></div></div>
    <div class="col-md-3"><div class="card custom-card mb-0"><div class="card-body py-2"><small class="text-muted">Saldo só do que foi conciliado</small><div class="fs-5 fw-bold"><?= fin_moeda($saldo_conc) ?></div></div></div></div>
</div>

<div class="row">
    <div class="col-lg-7">
        <div class="card custom-card">
            <div class="card-header"><b>Lançamentos do banco (OFX) pendentes</b></div>
            <div class="card-body p-0">
                <table class="table table-sm mb-0" style="font-size: 13px">
                    <thead class="table-light"><tr><th>Data</th><th>Descrição no banco</th><th class="text-end">Valor</th><th>Ação</th></tr></thead>
                    <tbody>
                    <?php foreach ($pend as $e) { $sug = $sugestoes($e); ?>
                        <tr>
                            <td class="text-nowrap"><?= fin_data_br($e['data']) ?></td>
                            <td><?= fin_e($e['descricao']) ?><?= $e['documento'] ? '<br><small class="text-muted">Doc ' . fin_e($e['documento']) . '</small>' : '' ?>
                                <?php foreach ($sug as $s) { ?>
                                    <div class="mt-1 p-1 border rounded bg-light small">
                                        <i class="fa fa-lightbulb-o text-warning"></i> <?= fin_data_br($s['data']) ?> · <?= fin_e(fin_descricao_linha($pdo, $s['descricao'])) ?>
                                        <a href="#" class="ms-1 fw-bold" onclick="vincular(<?= (int) $e['id'] ?>, <?= (int) $s['id'] ?>)">Vincular</a>
                                    </div>
                                <?php } ?>
                            </td>
                            <td class="text-end text-nowrap fw-bold <?= $e['valor'] < 0 ? 'text-danger' : 'text-success' ?>"><?= fin_moeda($e['valor']) ?></td>
                            <td class="text-nowrap">
                                <a href="#" class="btn btn-sm btn-outline-primary py-0" onclick="criar(<?= (int) $e['id'] ?>, <?= (float) $e['valor'] ?>, '<?= fin_js($e['descricao']) ?>')" title="Criar no sistema">Lançar</a>
                                <a href="#" class="btn btn-sm btn-outline-secondary py-0" onclick="ignorar(<?= (int) $e['id'] ?>)" title="Ignorar">Ignorar</a>
                            </td>
                        </tr>
                    <?php } ?>
                    <?php if (!$pend) { ?><tr><td colspan="4" class="text-center text-muted py-3">Nada pendente do banco neste período<?= $importados ? '' : ' (importe o OFX acima)' ?>.</td></tr><?php } ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    <div class="col-lg-5">
        <div class="card custom-card">
            <div class="card-header"><b>No sistema e ainda não conciliados</b></div>
            <div class="card-body p-0" style="max-height: 480px; overflow:auto">
                <table class="table table-sm mb-0" style="font-size: 13px">
                    <thead class="table-light"><tr><th>Data</th><th>Descrição</th><th class="text-end">Valor</th></tr></thead>
                    <tbody>
                    <?php foreach ($sistema_pend as $l) { $v = $l['credito'] - $l['debito']; ?>
                        <tr>
                            <td class="text-nowrap"><?= fin_data_br($l['data']) ?></td>
                            <td><?= fin_e(fin_descricao_linha($pdo, $l['descricao'])) ?></td>
                            <td class="text-end text-nowrap <?= $v < 0 ? 'text-danger' : 'text-success' ?>"><?= fin_moeda($v) ?></td>
                        </tr>
                    <?php } ?>
                    <?php if (!$sistema_pend) { ?><tr><td colspan="3" class="text-center text-muted py-3">Tudo conciliado neste período.</td></tr><?php } ?>
                    </tbody>
                </table>
            </div>
            <div class="card-footer small text-muted">Sem OFX? Marque a conferência linha a linha no Extrato.</div>
        </div>
    </div>
</div>

<?php if ($conc || $ign) { ?>
<div class="card custom-card">
    <div class="card-header"><b>Já tratados no período</b> <small class="text-muted">(<?= count($conc) ?> conciliados, <?= count($ign) ?> ignorados)</small></div>
    <div class="card-body p-0" style="max-height: 360px; overflow:auto">
        <table class="table table-sm mb-0" style="font-size: 13px">
            <thead class="table-light"><tr><th>Data banco</th><th>Descrição no banco</th><th class="text-end">Valor</th><th>No sistema</th><th></th></tr></thead>
            <tbody>
            <?php foreach (array_merge($conc, $ign) as $e) { ?>
                <tr class="<?= $e['status'] == 'ignorado' ? 'text-muted' : '' ?>">
                    <td><?= fin_data_br($e['data']) ?></td>
                    <td><?= fin_e($e['descricao']) ?></td>
                    <td class="text-end"><?= fin_moeda($e['valor']) ?></td>
                    <td><?= $e['status'] == 'ignorado' ? '<i>ignorado</i>' : fin_data_br($e['data_sistema']) . ' · ' . fin_e(fin_descricao_linha($pdo, $e['desc_sistema'])) ?></td>
                    <td><a href="#" onclick="desfazer(<?= (int) $e['id'] ?>)" title="Desfazer"><i class="fa fa-undo text-secondary"></i></a></td>
                </tr>
            <?php } ?>
            </tbody>
        </table>
    </div>
</div>
<?php } ?>
