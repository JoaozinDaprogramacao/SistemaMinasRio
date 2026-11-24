<?php
require_once("../../../conexao.php");
require_once(__DIR__ . "/../../seguranca.php");
require_once(__DIR__ . "/../../funcoes/financeiro.php");
require_once(__DIR__ . "/config.php");

$c = cadfin_config($_POST['e'] ?? '');
seguranca_exigir_acesso([$c['permissao']]);
$tem_ativo = fin_col_existe($pdo, $c['tabela'], 'ativo');
$tem_prot = fin_col_existe($pdo, $c['tabela'], 'protegida');
$itens = fin_q($pdo, "SELECT * FROM {$c['tabela']} ORDER BY nome")->fetchAll(PDO::FETCH_ASSOC);
?>
<table class="table table-sm table-bordered mb-0">
    <thead class="table-light"><tr><th>Nome</th><?php if ($c['grupos']) { ?><th>Grupo na DRE</th><?php } ?><?php if ($tem_ativo) { ?><th>Situação</th><?php } ?><th>Uso</th><th width="90"></th></tr></thead>
    <tbody>
    <?php foreach ($itens as $i) {
        $uso = 0;
        foreach ($c['uso'] as [$t, $col]) $uso += fin_q($pdo, "SELECT COUNT(*) FROM $t WHERE $col = ?", [$i['id']])->fetchColumn();
        $prot = $tem_prot && $i['protegida'];
    ?>
        <tr>
            <td><?= fin_e($i['nome']) ?> <?= $prot ? '<i class="fa fa-lock text-muted" title="Usada pelo sistema: não pode ser renomeada nem excluída"></i>' : '' ?></td>
            <?php if ($c['grupos']) { ?><td><?= fin_e($c['grupos'][$i['grupo_dre']] ?? $i['grupo_dre']) ?></td><?php } ?>
            <?php if ($tem_ativo) { ?><td><?= $i['ativo'] ? '<span class="badge bg-success">Ativo</span>' : '<span class="badge bg-secondary">Inativo</span>' ?></td><?php } ?>
            <td><small class="text-muted"><?= $uso ?> lançamento(s)</small></td>
            <td class="text-nowrap">
                <a href="#" onclick='cadEditar(<?= json_encode(['id' => $i['id'], 'nome' => $i['nome'], 'grupo' => $i['grupo_dre'] ?? '', 'ativo' => $i['ativo'] ?? 1, 'prot' => $prot], JSON_HEX_APOS | JSON_HEX_QUOT) ?>)' title="Editar"><i class="fa fa-edit text-primary"></i></a>
                <?php if (!$prot) { ?>
                    <a href="#" class="ms-2" onclick="cadExcluir(<?= (int) $i['id'] ?>, <?= $uso ?>)" title="Excluir"><i class="fa fa-trash text-danger"></i></a>
                <?php } ?>
            </td>
        </tr>
    <?php } ?>
    <?php if (!$itens) { ?><tr><td colspan="5" class="text-center text-muted">Nenhum cadastro.</td></tr><?php } ?>
    </tbody>
</table>
