<?php 
@session_start();
$mostrar_registros = @$_SESSION['registros'];
$id_usuario = @$_SESSION['id'];
require_once("../verificar.php");
require_once("../../conexao.php");



$dataInicial = $_POST['dataInicial'];
$dataFinal = $_POST['dataFinal'];
$tipo_data = $_POST['tipo_data'];
$atacadista = $_POST['atacadista'];
$formaPGTO = $_POST['formaPGTO'];
$filtro = $_POST['pago'];


$url_pdf = $url_sistema . "painel/rel/pagar.php?dataInicial=$dataInicial&dataFinal=$dataFinal&tipo_data=$tipo_data&atacadista=$atacadista&formaPGTO=$formaPGTO&pago=$filtro&mostrar_registros=$mostrar_registros&id_usuario=$id_usuario&token=A5030";
$html = file_get_contents($url_pdf);

//CARREGAR DOMPDF
require_once '../dompdf/autoload.inc.php';
use Dompdf\Dompdf;
use Dompdf\Options;

header("Content-Transfer-Encoding: binary");
header("Content-Type: image/png");

//INICIALIZAR A CLASSE DO DOMPDF
$options = new Options();
$options->set('isRemoteEnabled', TRUE);
$pdf = new DOMPDF($options);


//Definir o tamanho do papel e orientação da página
$pdf->set_paper('A4', 'portrait');

//CARREGAR O CONTEÚDO HTML
$pdf->load_html($html);

//RENDERIZAR O PDF
$pdf->render();
//NOMEAR O PDF GERADO


$pdf->stream(
	'contas_pagar.pdf',
	array("Attachment" => false)
);

$query = $pdo->query("SELECT rv.*, c.nome as nome_cliente, p.nome as nome_plano 
    FROM romaneio_venda rv 
    LEFT JOIN clientes c ON rv.atacadista = c.id 
    LEFT JOIN planos_pgto p ON rv.plano_pgto = p.id 
    ORDER BY rv.data DESC");
$res = $query->fetchAll(PDO::FETCH_ASSOC);

$html = '
<style>
    table { border-collapse: collapse; width: 100%; }
    td, th { border: 1px solid #000; padding: 4px; font-size: 10px; }
    .titulo { background-color: #c5e0b3; }
    .total { background-color: #ffeb9c; }
</style>

<table>
    <tr class="titulo">
        <th>Nº</th>
        <th>Data</th>
        <th>Cliente</th>
        <th>Plano</th>
        <th>Nota Fiscal</th>
        <th>Vencimento</th>
        <th>Valor Total</th>
    </tr>';

foreach($res as $row) {
    $html .= '<tr>
        <td>'.str_pad($row['id'], 6, '0', STR_PAD_LEFT).'</td>
        <td>'.date('d/m/Y', strtotime($row['data'])).'</td>
        <td>'.$row['nome_cliente'].'</td>
        <td>'.$row['nome_plano'].'</td>
        <td>'.$row['nota_fiscal'].'</td>
        <td>'.date('d/m/Y', strtotime($row['vencimento'])).'</td>
        <td>R$ '.number_format($row['total_liquido'], 2, ',', '.').'</td>
    </tr>';
}

$html .= '</table>';

echo $html;
?>