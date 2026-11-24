<?php
require_once("../../../conexao.php");
require_once(__DIR__ . "/../../seguranca.php");
seguranca_exigir_acesso(['auditoria_fin']);
require_once(__DIR__ . "/../../funcoes/financeiro.php");

$ini = fin_data_valida($_POST['ini'] ?? '') ? $_POST['ini'] : date('Y-m-d', strtotime('-30 days'));
$fim = fin_data_valida($_POST['fim'] ?? '') ? $_POST['fim'] : date('Y-m-d');
$where = ['a.data BETWEEN ? AND ?'];
$p = [$ini . ' 00:00:00', $fim . ' 23:59:59'];
if ((int) ($_POST['usuario'] ?? 0)) { $where[] = 'a.usuario = ?'; $p[] = (int) $_POST['usuario']; }
if (($_POST['acao'] ?? '') !== '') { $where[] = 'a.acao = ?'; $p[] = $_POST['acao']; }
if (trim($_POST['busca'] ?? '') !== '') { $where[] = 'a.descricao LIKE ?'; $p[] = '%' . trim($_POST['busca']) . '%'; }
$lista = fin_q($pdo, "SELECT a.*, u.nome nome_usuario FROM fin_auditoria a LEFT JOIN usuarios u ON u.id = a.usuario
    WHERE " . implode(' AND ', $where) . " ORDER BY a.id DESC LIMIT 1000", $p)->fetchAll(PDO::FETCH_ASSOC);
?>
<table class="table table-sm table-striped mb-0" style="font-size: 13px">
    <thead class="table-light"><tr><th>Quando</th><th>Usuário</th><th>Ação</th><th>Registro</th><th>Descrição</th></tr></thead>
    <tbody>
    <?php foreach ($lista as $a) { ?>
        <tr>
            <td class="text-nowrap"><?= date('d/m/Y H:i', strtotime($a['data'])) ?></td>
            <td><?= fin_e($a['nome_usuario'] ?: '—') ?></td>
            <td><span class="badge bg-light text-dark border"><?= fin_e($a['acao']) ?></span></td>
            <td class="text-nowrap small text-muted"><?= fin_e($a['entidade']) ?><?= $a['id_entidade'] ? ' #' . (int) $a['id_entidade'] : '' ?></td>
            <td><?= fin_e($a['descricao']) ?></td>
        </tr>
    <?php } ?>
    <?php if (!$lista) { ?><tr><td colspan="5" class="text-center text-muted py-3">Nada registrado no período.</td></tr><?php } ?>
    </tbody>
</table>
<?php if (count($lista) == 1000) { ?><small class="text-muted p-2 d-block">Mostrando os 1000 registros mais recentes. Refine o filtro.</small><?php } ?>
