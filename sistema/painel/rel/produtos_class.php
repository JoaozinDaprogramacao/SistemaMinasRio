<?php
require_once '../dompdf/autoload.inc.php';

use Dompdf\Dompdf;
use Dompdf\Options;

ob_start();
include('../../conexao.php');
require_once('../verificar.php');

// Mesmo filtro das abas de categoria da tela Produtos
$cat = intval($_POST['cat'] ?? ($_GET['cat'] ?? 0));

$sql = "SELECT p.*, c.nome AS nome_cat FROM produtos p LEFT JOIN categorias c ON c.id = p.categoria";
$params = [];
if ($cat > 0) {
    $sql .= " WHERE p.categoria = :cat";
    $params[':cat'] = $cat;
}
$sql .= " ORDER BY p.nome ASC, c.nome ASC";
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$produtos = $stmt->fetchAll(PDO::FETCH_ASSOC);

$nome_filtro = 'Todas as categorias';
if ($cat > 0) {
    $stmt = $pdo->prepare("SELECT nome FROM categorias WHERE id = :id");
    $stmt->execute([':id' => $cat]);
    $nome_filtro = $stmt->fetchColumn() ?: 'Categoria #' . $cat;
}
?>
<!DOCTYPE html>
<html>

<head>
    <meta charset="utf-8">
    <title>Relatório de Produtos</title>
    <style type="text/css">
        body { font-family: Arial, Helvetica, sans-serif; font-size: 10px; color: #222; }
        h2 { margin: 0 0 4px 0; font-size: 15px; }
        .sub { color: #666; margin-bottom: 12px; }
        table { width: 100%; border-collapse: collapse; }
        th { background: #eee; text-align: left; padding: 5px; border-bottom: 1px solid #999; }
        td { padding: 4px 5px; border-bottom: 1px solid #ddd; }
        .inativo td { color: #999; }
        .totais td { font-weight: bold; border-top: 2px solid #333; border-bottom: none; }
    </style>
</head>

<body>
    <h2><?php echo htmlspecialchars($nome_sistema) ?> - Relatório de Produtos</h2>
    <div class="sub"><?php echo htmlspecialchars($nome_filtro) ?> · Emitido em <?php echo date('d/m/Y H:i') ?></div>

    <table>
        <thead>
            <tr>
                <th>Produto</th>
                <th>Categoria</th>
                <th>Ativo</th>
                <th>Observações</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($produtos as $p) { ?>
                <tr class="<?php echo $p['ativo'] == 'Sim' ? '' : 'inativo' ?>">
                    <td><?php echo htmlspecialchars($p['nome'] ?? '') ?></td>
                    <td><?php echo htmlspecialchars($p['nome_cat'] ?? 'Sem Categoria') ?></td>
                    <td><?php echo $p['ativo'] == 'Sim' ? 'Sim' : 'Não' ?></td>
                    <td><?php echo htmlspecialchars($p['obs'] ?? '') ?></td>
                </tr>
            <?php } ?>
            <?php if (count($produtos) == 0) { ?>
                <tr><td colspan="4" style="text-align:center">Nenhum produto encontrado.</td></tr>
            <?php } ?>
            <tr class="totais">
                <td colspan="4"><?php echo count($produtos) ?> produtos</td>
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
$dompdf->stream('relatorio_produtos.pdf', array("Attachment" => 0));
exit(0);
