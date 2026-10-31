<?php
require_once '../dompdf/autoload.inc.php';

use Dompdf\Dompdf;
use Dompdf\Options;

ob_start();
include('../../conexao.php');

// Mesmo filtro das abas de cliente da tela Vendas Detalhes
$cliente_filtro = intval($_POST['cliente_filtro'] ?? ($_GET['cliente'] ?? 0));

$sql = "SELECT v.*, c.nome as nome_cliente, u.nome as nome_vendedor
        FROM vendas v
        LEFT JOIN clientes c ON v.cliente_id = c.id
        LEFT JOIN usuarios u ON v.vendedor_id = u.id";
$params = [];
if ($cliente_filtro > 0) {
    $sql .= " WHERE v.cliente_id = :cliente";
    $params[':cliente'] = $cliente_filtro;
}
$sql .= " ORDER BY v.data_venda DESC";
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$vendas = $stmt->fetchAll(PDO::FETCH_ASSOC);

$nome_filtro = 'Todos os clientes';
if ($cliente_filtro > 0) {
    $stmt = $pdo->prepare("SELECT nome FROM clientes WHERE id = :id");
    $stmt->execute([':id' => $cliente_filtro]);
    $nome_filtro = $stmt->fetchColumn() ?: 'Cliente #' . $cliente_filtro;
}

$fmt = fn($v) => 'R$ ' . number_format((float) $v, 2, ',', '.');
$total_vendido = $total_pago = $total_restante = 0;
?>
<!DOCTYPE html>
<html>

<head>
    <meta charset="utf-8">
    <title>Relatório de Vendas</title>
    <style type="text/css">
        body { font-family: Arial, Helvetica, sans-serif; font-size: 10px; color: #222; }
        h2 { margin: 0 0 4px 0; font-size: 15px; }
        .sub { color: #666; margin-bottom: 12px; }
        table { width: 100%; border-collapse: collapse; }
        th { background: #eee; text-align: left; padding: 5px; border-bottom: 1px solid #999; }
        td { padding: 4px 5px; border-bottom: 1px solid #ddd; }
        .r { text-align: right; }
        .cancelada td { color: #999; text-decoration: line-through; }
        .totais td { font-weight: bold; border-top: 2px solid #333; border-bottom: none; }
    </style>
</head>

<body>
    <h2><?php echo htmlspecialchars($nome_sistema) ?> - Relatório de Vendas</h2>
    <div class="sub"><?php echo htmlspecialchars($nome_filtro) ?> · Emitido em <?php echo date('d/m/Y H:i') ?></div>

    <table>
        <thead>
            <tr>
                <th>ID</th>
                <th>Data</th>
                <th>Cliente</th>
                <th>Vendedor</th>
                <th class="r">Total</th>
                <th class="r">Pago</th>
                <th class="r">Restante</th>
                <th>Status</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($vendas as $v) {
                $cancelada = $v['status_pagamento'] == 'Cancelada';
                if (!$cancelada) {
                    $total_vendido += $v['valor_total'];
                    $total_pago += $v['valor_pago'];
                    $total_restante += $v['valor_restante'];
                }
            ?>
                <tr class="<?php echo $cancelada ? 'cancelada' : '' ?>">
                    <td><?php echo $v['id'] ?></td>
                    <td><?php echo date('d/m/Y', strtotime($v['data_venda'])) ?></td>
                    <td><?php echo htmlspecialchars($v['nome_cliente'] ?? '-') ?></td>
                    <td><?php echo htmlspecialchars($v['nome_vendedor'] ?? '-') ?></td>
                    <td class="r"><?php echo $fmt($v['valor_total']) ?></td>
                    <td class="r"><?php echo $fmt($v['valor_pago']) ?></td>
                    <td class="r"><?php echo $fmt($v['valor_restante']) ?></td>
                    <td><?php echo $v['status_pagamento'] ?></td>
                </tr>
            <?php } ?>
            <?php if (count($vendas) == 0) { ?>
                <tr><td colspan="8" style="text-align:center">Nenhuma venda encontrada.</td></tr>
            <?php } ?>
            <tr class="totais">
                <td colspan="4">Totais (sem canceladas)</td>
                <td class="r"><?php echo $fmt($total_vendido) ?></td>
                <td class="r"><?php echo $fmt($total_pago) ?></td>
                <td class="r"><?php echo $fmt($total_restante) ?></td>
                <td></td>
            </tr>
        </tbody>
    </table>
</body>

</html>
<?php
$html = ob_get_clean();

$options = new Options();
$options->set('isHtml5ParserEnabled', true);
$dompdf = new Dompdf($options);
$dompdf->setPaper('A4', 'landscape');
$dompdf->loadHtml($html);
$dompdf->render();
$dompdf->stream('relatorio_vendas.pdf', array("Attachment" => 0));
exit(0);
