<?php
require_once("../../../conexao.php");
require_once(__DIR__ . "/../../seguranca.php");
seguranca_exigir_login();
require_once(__DIR__ . "/../../funcoes/financeiro_saneamento.php");
if (@$_SESSION['nivel'] != 'Administrador') exit('Somente o administrador.');

$d = san_diagnostico($pdo);
$sem_reg = array_sum(array_column($d['sem_registro'], 'n'));
$sem_reg_v = array_sum(array_column($d['sem_registro'], 'v'));
$auto = $sem_reg + $d['residuos']['n'] + $d['fora_extrato']['n'] + count($d['status']);
$bancos = fin_q($pdo, "SELECT id, banco FROM bancos ORDER BY banco")->fetchAll(PDO::FETCH_ASSOC);
$executado = fin_q($pdo, "SELECT valor FROM fin_meta WHERE chave = 'saneamento_executado'")->fetchColumn();
$ok = fn($n) => $n ? '<span class="badge bg-warning text-dark">' . $n . ' pendente(s)</span>' : '<span class="badge bg-success">ok</span>';
?>
<div class="card custom-card">
    <div class="card-header d-flex justify-content-between align-items-center flex-wrap gap-2">
        <b>1. Correções automáticas</b>
        <?php if ($auto) { ?>
            <button class="btn btn-primary btn-sm" onclick="executarAutomatico()"><i class="fa fa-magic me-1"></i>Executar correções automáticas</button>
        <?php } else { ?>
            <span class="text-success small"><i class="fa fa-check"></i> Nada pendente<?= $executado ? ' (executado em ' . date('d/m/Y H:i', strtotime($executado)) . ')' : '' ?></span>
        <?php } ?>
    </div>
    <div class="card-body p-0">
        <table class="table table-sm mb-0">
            <tbody>
            <tr>
                <td><b>Baixas antigas sem registro de pagamento</b> — <?= fin_moeda($sem_reg_v) ?><br>
                    <small class="text-muted">Títulos marcados como pagos/recebidos por versões antigas, sem o registro de cada pagamento. O valor já está no extrato:
                        o saneamento cria o registro e liga à linha do banco de mesma data e valor. Sem isso, estornar ou excluir esses títulos não teria como desfazer o banco.</small></td>
                <td class="text-end align-middle"><?= $ok($sem_reg) ?></td>
            </tr>
            <tr>
                <td><b>Resíduos de baixa parcial</b> — <?= fin_moeda($d['residuos']['v']) ?><br>
                    <small class="text-muted">A tela antiga de baixa parcial criava um título "(Resíduo)" para cada parte paga e reduzia o título original.
                        Eles viram pagamentos do título original, que volta ao valor cheio.</small></td>
                <td class="text-end align-middle"><?= $ok($d['residuos']['n']) ?></td>
            </tr>
            <tr>
                <td><b>Recebimentos com conta informada fora do extrato</b> — <?= fin_moeda($d['fora_extrato']['v']) ?><br>
                    <small class="text-muted">Baixas de Contas a Receber que nunca entraram no banco. São lançadas na conta e data registradas; se já existir uma linha idêntica no extrato, ela é aproveitada (não duplica).</small></td>
                <td class="text-end align-middle"><?= $ok($d['fora_extrato']['n']) ?></td>
            </tr>
            <tr>
                <td><b>Situação do título diferente dos pagamentos</b><br>
                    <small class="text-muted">Títulos "pagos" com pagamento menor que o devido, ou "em aberto" com pagamento registrado (efeito do antigo salvar de romaneio).</small></td>
                <td class="text-end align-middle"><?= $ok(count($d['status'])) ?></td>
            </tr>
            </tbody>
        </table>
    </div>
</div>

<div class="card custom-card">
    <div class="card-header d-flex justify-content-between align-items-center flex-wrap gap-2">
        <b>2. Pagamentos sem conta bancária</b> <?= $ok(count($d['sem_conta'])) ?>
        <?php if ($d['sem_conta']) { ?><button class="btn btn-success btn-sm" onclick="contasPadrao()">Lançar todos com a conta escolhida</button><?php } ?>
    </div>
    <div class="card-body p-0">
        <?php if ($d['sem_conta']) { ?>
        <div class="p-2 small text-muted">O sistema não sabe em que conta estes valores entraram/saíram. Escolha a conta de cada um (já vem sugerida a conta padrão da forma de pagamento, quando definida). Confira no extrato do banco.</div>
        <div style="max-height: 420px; overflow: auto">
        <table class="table table-sm mb-0" style="font-size: 13px">
            <thead class="table-light"><tr><th>Tipo</th><th>Data</th><th>Título</th><th>Forma</th><th class="text-end">Valor</th><th>Conta</th><th></th></tr></thead>
            <tbody>
            <?php foreach ($d['sem_conta'] as $p) { ?>
                <tr>
                    <td><?= $p['tipo'] == 'receber' ? '<span class="badge bg-success">Recebimento</span>' : '<span class="badge bg-danger">Pagamento</span>' ?></td>
                    <td><?= fin_data_br($p['data_pgto']) ?></td>
                    <td><?= fin_e($p['descricao']) ?> <small class="text-muted">#<?= (int) $p['id_titulo'] ?></small></td>
                    <td><?= fin_e($p['nome_forma'] ?: '—') ?></td>
                    <td class="text-end"><?= fin_moeda($p['valor']) ?></td>
                    <td><select class="form-select form-select-sm sel-conta" id="conta-<?= $p['tipo'] ?>-<?= (int) $p['id'] ?>" data-tipo="<?= $p['tipo'] ?>" data-id="<?= (int) $p['id'] ?>">
                        <option value="">Escolha...</option>
                        <?php foreach ($bancos as $b) echo '<option value="' . $b['id'] . '"' . ($p['banco_padrao'] == $b['id'] ? ' selected' : '') . '>' . fin_e($b['banco']) . '</option>'; ?>
                    </select></td>
                    <td><a href="#" class="btn btn-sm btn-outline-success py-0" onclick="definirConta('<?= $p['tipo'] ?>', <?= (int) $p['id'] ?>)">Lançar</a></td>
                </tr>
            <?php } ?>
            </tbody>
        </table>
        </div>
        <?php } else { ?><div class="p-3 text-muted small">Nenhum pagamento sem conta.</div><?php } ?>
    </div>
</div>

<div class="card custom-card">
    <div class="card-header"><b>3. Contas a pagar possivelmente pagas em dobro</b> <?= $ok(count($d['dobro'])) ?></div>
    <div class="card-body p-0">
        <?php if ($d['dobro']) { ?>
        <div class="p-2 small text-muted">A versão antiga, ao salvar de novo um romaneio de compra, apagava o título já pago e criava outro em aberto, que foi pago de novo.
            O extrato do sistema ficou com duas saídas. <b>Confira no extrato do banco</b>: se o valor saiu uma vez só, remova o pagamento antigo; se saiu duas vezes, mantenha.</div>
        <table class="table table-sm mb-0" style="font-size: 13px">
            <thead class="table-light"><tr><th>Romaneio / descrição</th><th class="text-end">Valor</th><th>1º pagamento (título apagado)</th><th>2º pagamento (título atual)</th><th></th></tr></thead>
            <tbody>
            <?php foreach ($d['dobro'] as $o) { ?>
                <tr>
                    <td><?= fin_e($o['desc_linha'] ?: 'Título #' . $o['id_pagar'] . ' (apagado)') ?></td>
                    <td class="text-end"><?= fin_moeda($o['valor']) ?></td>
                    <td><?= fin_data_br($o['data_pgto']) ?> · <?= fin_e(fin_nome_banco($pdo, $o['banco'])) ?><?= $o['conciliado'] ? ' <span class="badge bg-info">conciliado</span>' : '' ?></td>
                    <td><?= $o['atual'] ? fin_data_br($o['atual']['data_pgto']) . ' · título #' . (int) $o['atual']['id'] : '<i class="text-muted">não encontrado</i>' ?></td>
                    <td class="text-nowrap">
                        <a href="#" class="btn btn-sm btn-outline-danger py-0" onclick="removerDobro(<?= (int) $o['id'] ?>)">Saiu 1 vez: remover</a>
                        <a href="#" class="btn btn-sm btn-outline-secondary py-0" onclick="manterDobro(<?= (int) $o['id'] ?>)">Saiu 2 vezes: manter</a>
                    </td>
                </tr>
            <?php } ?>
            </tbody>
        </table>
        <?php } else { ?><div class="p-3 text-muted small">Nenhum caso.</div><?php } ?>
    </div>
</div>

<div class="card custom-card">
    <div class="card-header"><b>4. Romaneios com valor diferente do financeiro</b> <?= $ok(count($d['romaneios'])) ?></div>
    <div class="card-body p-0">
        <?php if ($d['romaneios']) { ?>
        <div class="p-2 small text-muted">O total do romaneio não bate com o título no financeiro. Pode ser desconto/acerto combinado ou edição feita depois da baixa.
            Abra o romaneio e salve de novo para alinhar (o sistema agora preserva os pagamentos), ou ajuste no financeiro. Rode as correções automáticas antes: os resíduos explicam parte das diferenças.</div>
        <div style="max-height: 360px; overflow: auto">
        <table class="table table-sm mb-0" style="font-size: 13px">
            <thead class="table-light"><tr><th>Romaneio</th><th class="text-end">Total do romaneio</th><th class="text-end">No financeiro</th><th class="text-end">Diferença</th><th>Títulos</th></tr></thead>
            <tbody>
            <?php foreach ($d['romaneios'] as $r) { ?>
                <tr>
                    <td><?= $r['tipo'] == 'venda' ? 'Venda' : 'Compra' ?> nº <?= (int) $r['id'] ?></td>
                    <td class="text-end"><?= fin_moeda($r['total']) ?></td>
                    <td class="text-end"><?= fin_moeda($r['financeiro']) ?></td>
                    <td class="text-end <?= $r['total'] - $r['financeiro'] < 0 ? 'text-danger' : 'text-warning' ?>"><?= fin_moeda($r['total'] - $r['financeiro']) ?></td>
                    <td><?= (int) $r['titulos'] ?></td>
                </tr>
            <?php } ?>
            </tbody>
        </table>
        </div>
        <?php } else { ?><div class="p-3 text-muted small">Todos os romaneios batem com o financeiro.</div><?php } ?>
    </div>
</div>
<div class="card custom-card">
    <div class="card-header"><b>5. Linhas antigas do extrato sem título correspondente</b> <?= $ok($auto ? 0 : count($d['linhas_soltas'])) ?></div>
    <div class="card-body p-0">
        <?php if ($auto) { ?>
            <div class="p-3 text-muted small">Execute primeiro as correções automáticas: muitas destas linhas são ligadas aos seus títulos por elas.</div>
        <?php } elseif ($d['linhas_soltas']) { ?>
        <div class="p-2 small text-muted">Movimentos lançados por versões antigas que não batem com nenhum título. Os marcados como <b>repetidos</b> (mesma conta, dia e valor) costumam ser
            duplicatas do antigo erro em que editar uma conta lançava de novo no banco. <b>Confira no extrato do banco</b> antes de remover.</div>
        <div style="max-height: 420px; overflow: auto">
        <table class="table table-sm mb-0" style="font-size: 13px">
            <thead class="table-light"><tr><th>Data</th><th>Conta</th><th>Descrição</th><th class="text-end">Entrada</th><th class="text-end">Saída</th><th></th><th></th></tr></thead>
            <tbody>
            <?php foreach ($d['linhas_soltas'] as $l) { ?>
                <tr>
                    <td><?= fin_data_br($l['data']) ?></td>
                    <td><?= fin_e($l['nome_banco']) ?></td>
                    <td><?= fin_e(fin_descricao_linha($pdo, $l['descricao'])) ?></td>
                    <td class="text-end text-success"><?= $l['credito'] > 0 ? fin_moeda($l['credito']) : '' ?></td>
                    <td class="text-end text-danger"><?= $l['debito'] > 0 ? fin_moeda($l['debito']) : '' ?></td>
                    <td><?= $l['iguais'] ? '<span class="badge bg-warning text-dark">repetido</span>' : '' ?></td>
                    <td class="text-nowrap">
                        <a href="#" class="btn btn-sm btn-outline-danger py-0" onclick="sanAcao({acao:'excluir_linha', id:<?= (int) $l['id'] ?>}, 'Remover esta linha do extrato? Confirme no extrato do banco que ela não existe.')">Não existe no banco: remover</a>
                        <a href="#" class="btn btn-sm btn-outline-secondary py-0" onclick="sanAcao({acao:'manter_linha', id:<?= (int) $l['id'] ?>})">Existe: manter</a>
                    </td>
                </tr>
            <?php } ?>
            </tbody>
        </table>
        </div>
        <?php } else { ?><div class="p-3 text-muted small">Nenhuma.</div><?php } ?>
    </div>
</div>
<p class="small text-muted">Depois do saneamento, importe os extratos OFX em Conciliação Bancária para conferir os saldos com os do banco.</p>
