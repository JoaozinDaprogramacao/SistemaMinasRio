<?php 
@session_start();
require_once("../../conexao.php");

$dataInicial = $_POST['dataInicial'];
$dataFinal = $_POST['dataFinal']; 
$cliente = $_POST['cliente'];

// Query principal simplificada
$query = $pdo->prepare("SELECT 
    r.*, 
    c.nome as nome_atacadista,
    (SELECT COUNT(*) FROM linha_produto WHERE id_romaneio = r.id) as total_caixas
    FROM romaneio_venda r
    LEFT JOIN clientes c ON c.id = r.atacadista 
    WHERE r.data >= ? AND r.data <= ?
    AND (r.atacadista = ? OR ? = '')
    ORDER BY r.data DESC");

$query->execute([$dataInicial, $dataFinal, $cliente, $cliente]);
$res = $query->fetchAll(PDO::FETCH_ASSOC);

// Query para totais de caixas e produtos
$query_totais = $pdo->prepare("SELECT 
    COUNT(*) as total_geral_caixas,
    SUM(lp.quant) as total_quantidade
    FROM linha_produto lp
    JOIN romaneio_venda r ON r.id = lp.id_romaneio
    WHERE r.data >= ? AND r.data <= ?
    AND (r.atacadista = ? OR ? = '')");

$query_totais->execute([$dataInicial, $dataFinal, $cliente, $cliente]);
$totais = $query_totais->fetch(PDO::FETCH_ASSOC);

// Query para tipos de caixas
$query_tipos = $pdo->prepare("SELECT 
    CONCAT(tc.tipo, ' ', CASE tc.unidade_medida 
        WHEN 1 THEN 'KG'
        WHEN 2 THEN 'G'
        WHEN 3 THEN 'UN'
        ELSE ''
    END) as tipo,
    COUNT(lp.id) as quantidade
    FROM linha_produto lp 
    JOIN tipo_caixa tc ON tc.id = lp.tipo_caixa
    JOIN romaneio_venda r ON r.id = lp.id_romaneio
    WHERE r.data >= ? AND r.data <= ?
    AND (r.atacadista = ? OR ? = '')
    GROUP BY tc.id, tc.tipo, tc.unidade_medida");

$query_tipos->execute([$dataInicial, $dataFinal, $cliente, $cliente]);
$tipos = $query_tipos->fetchAll(PDO::FETCH_ASSOC);

// Query para produtos
$query_produtos = $pdo->prepare("SELECT 
    p.nome,
    SUM(lp.quant) as quantidade
    FROM linha_produto lp
    JOIN produtos p ON p.id = lp.variedade
    JOIN romaneio_venda r ON r.id = lp.id_romaneio
    WHERE r.data >= ? AND r.data <= ?
    AND (r.atacadista = ? OR ? = '')
    GROUP BY p.nome");

$query_produtos->execute([$dataInicial, $dataFinal, $cliente, $cliente]);
$produtos = $query_produtos->fetchAll(PDO::FETCH_ASSOC);

// Formatando os resultados para exibição
$tipos_texto = '';
foreach($tipos as $tipo) {
    $tipos_texto .= $tipo['tipo'] . ' (' . $tipo['quantidade'] . '), ';
}
$tipos_texto = rtrim($tipos_texto, ', ');

$produtos_texto = '';
foreach($produtos as $produto) {
    $produtos_texto .= $produto['nome'] . ' (' . $produto['quantidade'] . '), ';
}
$produtos_texto = rtrim($produtos_texto, ', ');

// Gerar HTML do relatório
$html = '
<!DOCTYPE html>
<html>
<head>
<meta charset="utf-8">
<style>
    @page { margin: 145px 20px 25px 20px; }
    body { font-family: Arial, sans-serif; }
    table { width: 100%; border-collapse: collapse; margin-bottom: 20px; }
    td, th { border: 1px solid #000; padding: 5px; }
    th { background-color: #f2f2f2; }
    .totais { background-color: #f9f9f9; padding: 10px; margin-bottom: 20px; }
</style>
</head>
<body>
    <h2 style="text-align: center">Relatório de Romaneio de Vendas</h2>
    
    <div class="totais">
        <h3>Resumo Geral do Período</h3>
        <p><strong>Total de Caixas Vendidas:</strong> '.$totais['total_geral_caixas'].'</p>
        <p><strong>Tipos de Caixas:</strong> '.$tipos_texto.'</p>
        <p><strong>Produtos Vendidos:</strong> '.$produtos_texto.'</p>
    </div>

    <table>
        <thead>
            <tr>
                <th>Data</th>
                <th>Atacadista</th>
                <th>Total Líquido</th>
                <th>Nota Fiscal</th>
                <th>Vencimento</th>
                <th>Qtd Caixas</th>
            </tr>
        </thead>
        <tbody>';

foreach($res as $row) {
    $data = date('d/m/Y', strtotime($row['data']));
    $vencimento = date('d/m/Y', strtotime($row['vencimento']));
    
    $html .= '<tr>
        <td>'.$data.'</td>
        <td>'.$row['nome_atacadista'].'</td>
        <td>R$ '.number_format($row['total_liquido'],2,',','.').'</td>
        <td>'.$row['nota_fiscal'].'</td>
        <td>'.$vencimento.'</td>
        <td>'.$row['total_caixas'].'</td>
    </tr>';
}

$html .= '</tbody></table></body></html>';

//CARREGAR DOMPDF
require_once '../dompdf/autoload.inc.php';
use Dompdf\Dompdf;
use Dompdf\Options;

$options = new Options();
$options->set('isRemoteEnabled', TRUE);
$pdf = new DOMPDF($options);

$pdf->set_paper('A4', 'portrait');
$pdf->load_html($html);
$pdf->render();

$pdf->stream(
    'romaneio_vendas.pdf',
    array("Attachment" => false)
);
?>