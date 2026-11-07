<?php
// Relatório da tela Estoque: materiais com estoque controlado abaixo do mínimo
require_once '../dompdf/autoload.inc.php';

use Dompdf\Dompdf;
use Dompdf\Options;

ob_start();
include('../../conexao.php');
require_once(__DIR__ . "/../seguranca.php");
seguranca_exigir_login();

$materiais = $pdo->query("SELECT * FROM materiais WHERE tem_estoque = 'Sim' AND estoque < estoque_minimo ORDER BY (estoque - estoque_minimo) ASC")->fetchAll(PDO::FETCH_ASSOC);
$num = fn($v) => number_format((float) $v, 0, ',', '.');
?>
<!DOCTYPE html>
<html>

<head>
    <meta charset="utf-8">
    <title>Estoque Baixo</title>
    <style type="text/css">
        body { font-family: Arial, Helvetica, sans-serif; font-size: 10px; color: #222; }
        h2 { margin: 0 0 4px 0; font-size: 15px; }
        .sub { color: #666; margin-bottom: 12px; }
        table { width: 100%; border-collapse: collapse; }
        th { background: #eee; text-align: left; padding: 5px; border-bottom: 1px solid #999; }
        td { padding: 4px 5px; border-bottom: 1px solid #ddd; }
        .r { text-align: right; }
        .falta { color: #b00; font-weight: bold; }
        .totais td { font-weight: bold; border-top: 2px solid #333; border-bottom: none; }
    </style>
</head>

<body>
    <h2><?php echo htmlspecialchars($nome_sistema) ?> - Materiais com Estoque Baixo</h2>
    <div class="sub">Materiais com estoque controlado abaixo do mínimo · Emitido em <?php echo date('d/m/Y H:i') ?></div>

    <table>
        <thead>
            <tr>
                <th>Material</th>
                <th class="r">Estoque</th>
                <th class="r">Estoque Mínimo</th>
                <th class="r">Falta para o mínimo</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($materiais as $m) { ?>
                <tr>
                    <td><?php echo htmlspecialchars($m['nome']) ?></td>
                    <td class="r falta"><?php echo $num($m['estoque']) ?></td>
                    <td class="r"><?php echo $num($m['estoque_minimo']) ?></td>
                    <td class="r"><?php echo $num($m['estoque_minimo'] - $m['estoque']) ?></td>
                </tr>
            <?php } ?>
            <?php if (count($materiais) == 0) { ?>
                <tr><td colspan="4" style="text-align:center">Nenhum material abaixo do estoque mínimo.</td></tr>
            <?php } ?>
            <tr class="totais">
                <td colspan="4"><?php echo count($materiais) ?> materiais abaixo do mínimo</td>
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
$dompdf->stream('estoque_baixo.pdf', array("Attachment" => 0));
exit(0);
