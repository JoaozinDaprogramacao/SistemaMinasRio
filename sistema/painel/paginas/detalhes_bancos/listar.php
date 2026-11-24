<?php
require_once("../../../conexao.php");
require_once(__DIR__ . "/../../seguranca.php");
seguranca_exigir_acesso(['contas_bancarias']);
require_once(__DIR__ . "/../../funcoes/financeiro.php");
require_once(__DIR__ . "/consulta.php");

$r = extrato_consulta($pdo, $_POST);
$saldo = $r['anterior'];
$entradas = 0;
$saidas = 0;
?>
<div class="table-responsive">
<table class="table table-sm table-bordered table-hover align-middle mb-0" style="font-size: 13px">
    <thead class="table-light">
        <tr>
            <th title="Conciliado com o extrato do banco" class="text-center"><i class="fa fa-check"></i></th>
            <th>Data</th>
            <?php if (!$r['banco']) { ?><th>Conta</th><?php } ?>
            <th>Descrição</th>
            <th>Origem</th>
            <th>Categoria</th>
            <th class="text-end">Entrada</th>
            <th class="text-end">Saída</th>
            <?php if ($saldo !== null) { ?><th class="text-end">Saldo</th><?php } ?>
            <th></th>
        </tr>
    </thead>
    <tbody>
    <?php if ($saldo !== null) { ?>
        <tr class="table-light">
            <td></td>
            <td><?= fin_data_br($r['ini']) ?></td>
            <?php if (!$r['banco']) { ?><td></td><?php } ?>
            <td colspan="5"><b>Saldo anterior</b></td>
            <td class="text-end fw-bold <?= $saldo < 0 ? 'text-danger' : '' ?>"><?= fin_moeda($saldo) ?></td>
            <td></td>
        </tr>
    <?php } ?>
    <?php foreach ($r['linhas'] as $l) {
        $c = (float) $l['credito'];
        $d = (float) $l['debito'];
        $entradas += $c;
        $saidas += $d;
        if ($saldo !== null) $saldo += $c - $d;
        [$rot, $cor, $dica] = extrato_origem_rotulo($l);
        $excluivel = in_array($l['origem'], [null, '', 'manual', 'transferencia', 'saldo_inicial', 'legado_estorno', 'estorno_legado'], true);
        $categoria = $l['cat_rec'] ?: ($l['cat_pag'] ?: '');
    ?>
        <tr class="<?= $l['conciliado'] ? '' : '' ?>">
            <td class="text-center">
                <input type="checkbox" <?= $l['conciliado'] ? 'checked' : '' ?> onchange="conciliar(<?= (int) $l['id'] ?>, this.checked)"
                    title="<?= $l['conciliado'] ? 'Conciliado em ' . fin_data_br($l['data_conciliacao']) : 'Marcar como conferido no extrato do banco' ?>">
            </td>
            <td class="text-nowrap"><?= fin_data_br($l['data']) ?></td>
            <?php if (!$r['banco']) { ?><td class="text-nowrap"><?= fin_e($l['nome_banco']) ?></td><?php } ?>
            <td><?= fin_e(fin_descricao_linha($pdo, $l['descricao'])) ?>
                <?php if ($l['documento'] || $l['n_fiscal']) { ?><br><small class="text-muted">Doc: <?= fin_e($l['documento'] ?: $l['n_fiscal']) ?></small><?php } ?>
                <?php if ($l['obs']) { ?><br><small class="text-muted"><?= fin_e($l['obs']) ?></small><?php } ?>
            </td>
            <td><span class="badge bg-<?= $cor ?> <?= $cor == 'light' ? 'text-dark border' : '' ?>" title="<?= fin_e($dica) ?>"><?= $rot ?></span></td>
            <td><small><?= fin_e($categoria) ?><?= $l['centro'] ? '<br><span class="text-muted">' . fin_e($l['centro']) . '</span>' : '' ?></small></td>
            <td class="text-end text-success text-nowrap"><?= $c > 0 ? fin_moeda($c) : '' ?></td>
            <td class="text-end text-danger text-nowrap"><?= $d > 0 ? fin_moeda($d) : '' ?></td>
            <?php if ($saldo !== null) { ?><td class="text-end text-nowrap <?= $saldo < 0 ? 'text-danger' : '' ?>"><?= fin_moeda($saldo) ?></td><?php } ?>
            <td class="text-center">
                <?php if ($excluivel && !$l['conciliado']) { ?>
                    <a href="#" onclick="excluirLancamento(<?= (int) $l['id'] ?>)" title="Excluir lançamento"><i class="fa fa-trash text-danger"></i></a>
                <?php } ?>
            </td>
        </tr>
    <?php } ?>
    <?php if (!$r['linhas']) { ?>
        <tr><td colspan="10" class="text-center text-muted py-3">Nenhum movimento no período.</td></tr>
    <?php } ?>
    </tbody>
    <tfoot class="table-light fw-bold">
        <tr>
            <td colspan="<?= $r['banco'] ? 5 : 6 ?>" class="text-end">Totais do período</td>
            <td class="text-end text-success"><?= fin_moeda($entradas) ?></td>
            <td class="text-end text-danger"><?= fin_moeda($saidas) ?></td>
            <?php if ($saldo !== null) { ?><td class="text-end <?= $saldo < 0 ? 'text-danger' : '' ?>"><?= fin_moeda($saldo) ?></td><?php } ?>
            <td></td>
        </tr>
    </tfoot>
</table>
</div>
<?php if ($r['anterior'] === null) { ?>
    <small class="text-muted">Com filtro de tipo, origem, conciliação ou busca o saldo acumulado não é exibido, pois parte dos movimentos fica de fora.</small>
<?php } ?>
