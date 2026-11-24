<?php
require_once("../../../conexao.php");
require_once(__DIR__ . "/../../seguranca.php");
seguranca_exigir_acesso(['adiantamentos']);
require_once(__DIR__ . "/../../funcoes/financeiro.php");

$where = ['1=1'];
$p = [];
if (in_array($_POST['tipo'] ?? '', ['cliente', 'fornecedor'], true)) { $where[] = 'a.tipo = ?'; $p[] = $_POST['tipo']; }
if (($_POST['saldo'] ?? '') === '1') $where[] = 'a.saldo > 0';
$lista = fin_q($pdo, "SELECT a.*, b.banco nome_banco,
    CASE WHEN a.tipo = 'cliente' THEN c.nome ELSE f.nome_atacadista END pessoa_nome
    FROM fin_adiantamentos a LEFT JOIN bancos b ON b.id = a.id_banco
    LEFT JOIN clientes c ON a.tipo = 'cliente' AND c.id = a.pessoa
    LEFT JOIN fornecedores f ON a.tipo = 'fornecedor' AND f.id = a.pessoa
    WHERE " . implode(' AND ', $where) . " ORDER BY a.data DESC, a.id DESC", $p)->fetchAll(PDO::FETCH_ASSOC);
$tot_cli = array_sum(array_map(fn($a) => $a['tipo'] == 'cliente' ? $a['saldo'] : 0, $lista));
$tot_for = array_sum(array_map(fn($a) => $a['tipo'] == 'fornecedor' ? $a['saldo'] : 0, $lista));
?>
<div class="d-flex gap-4 mb-2 small">
    <span>Créditos de clientes disponíveis: <b><?= fin_moeda($tot_cli) ?></b> <span class="text-muted">(devemos em mercadoria/serviço)</span></span>
    <span>Adiantado a fornecedores: <b><?= fin_moeda($tot_for) ?></b> <span class="text-muted">(a abater em compras)</span></span>
</div>
<table class="table table-sm table-bordered mb-0" style="font-size: 13px">
    <thead class="table-light"><tr><th>#</th><th>Data</th><th>Tipo</th><th>Pessoa</th><th>Descrição</th><th>Conta</th><th class="text-end">Valor</th><th class="text-end">Saldo</th><th></th></tr></thead>
    <tbody>
    <?php foreach ($lista as $a) { ?>
        <tr>
            <td><?= (int) $a['id'] ?></td>
            <td><?= fin_data_br($a['data']) ?></td>
            <td><?= $a['tipo'] == 'cliente' ? '<span class="badge bg-success">Cliente</span>' : '<span class="badge bg-warning text-dark">Fornecedor</span>' ?></td>
            <td><?= fin_e($a['pessoa_nome']) ?></td>
            <td><?= fin_e($a['descricao']) ?></td>
            <td><?= fin_e($a['nome_banco']) ?></td>
            <td class="text-end"><?= fin_moeda($a['valor']) ?></td>
            <td class="text-end fw-bold"><?= fin_moeda($a['saldo']) ?></td>
            <td><?php if (round($a['saldo'], 2) == round($a['valor'], 2)) { ?><a href="#" onclick="excluirAdi(<?= (int) $a['id'] ?>)"><i class="fa fa-trash text-danger"></i></a><?php } ?></td>
        </tr>
    <?php } ?>
    <?php if (!$lista) { ?><tr><td colspan="9" class="text-center text-muted">Nenhum adiantamento.</td></tr><?php } ?>
    </tbody>
</table>
