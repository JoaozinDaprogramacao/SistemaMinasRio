<?php
require_once '../dompdf/autoload.inc.php';

use Dompdf\Dompdf;
use Dompdf\Options;

ob_start();
include('../../conexao.php');
require_once('../verificar.php');

$materiais = $pdo->query("SELECT * FROM materiais ORDER BY nome ASC")->fetchAll(PDO::FETCH_ASSOC);
$num = fn($v) => number_format((float) $v, 0, ',', '.');
$abaixo = 0;
?>
<!DOCTYPE html>
<html>

<head>
    <meta charset="utf-8">
    <title>Relatório de Materiais</title>
    <style type="text/css">
        body { font-family: Arial, Helvetica, sans-serif; font-size: 10px; color: #222; }
        h2 { margin: 0 0 4px 0; font-size: 15px; }
        .sub { color: #666; margin-bottom: 12px; }
        table { width: 100%; border-collapse: collapse; }
        th { background: #eee; text-align: left; padding: 5px; border-bottom: 1px solid #999; }
        td { padding: 4px 5px; border-bottom: 1px solid #ddd; }
        .r { text-align: right; }
        .alerta td { color: #b00; font-weight: bold; }
        .totais td { font-weight: bold; border-top: 2px solid #333; border-bottom: none; }
    </style>
</head>

<body>
    <h2><?php echo htmlspecialchars($nome_sistema) ?> - Relatório de Materiais</h2>
    <div class="sub">Emitido em <?php echo date('d/m/Y H:i') ?></div>

    <table>
        <thead>
            <tr>
                <th>Material</th>
                <th class="r">Estoque</th>
                <th class="r">Estoque Mínimo</th>
                <th>Controla Estoque</th>
                <th class="r">Vendidos</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($materiais as $m) {
                $alerta = $m['tem_estoque'] == 'Sim' && $m['estoque'] < $m['estoque_minimo'];
                if ($alerta) {
                    $abaixo++;
                }
            ?>
                <tr class="<?php echo $alerta ? 'alerta' : '' ?>">
                    <td><?php echo htmlspecialchars($m['nome']) ?></td>
                    <td class="r"><?php echo $num($m['estoque']) ?></td>
                    <td class="r"><?php echo $num($m['estoque_minimo']) ?></td>
                    <td><?php echo $m['tem_estoque'] == 'Sim' ? 'Sim' : 'Não' ?></td>
                    <td class="r"><?php echo $num($m['vendas']) ?></td>
                </tr>
            <?php } ?>
            <?php if (count($materiais) == 0) { ?>
                <tr><td colspan="5" style="text-align:center">Nenhum material cadastrado.</td></tr>
            <?php } ?>
            <tr class="totais">
                <td colspan="5"><?php echo count($materiais) ?> materiais · <?php echo $abaixo ?> abaixo do estoque mínimo (em vermelho)</td>
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
$dompdf->setPaper('A4', 'portrait');
$dompdf->loadHtml($html);
$dompdf->render();
$dompdf->stream('relatorio_materiais.pdf', array("Attachment" => 0));
exit(0);
