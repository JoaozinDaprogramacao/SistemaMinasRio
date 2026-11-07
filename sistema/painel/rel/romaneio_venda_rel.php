<?php 
require_once("../../conexao.php");
require_once(__DIR__ . "/../seguranca.php");
seguranca_exigir_login();
require_once '../dompdf/autoload.inc.php';
use Dompdf\Dompdf;
use Dompdf\Options;

$options = new Options();
$options->set('isRemoteEnabled', true);

$dompdf = new Dompdf($options);

$html = file_get_contents($url_sistema . "painel/rel/romaneio_venda_class.php?token=" . seguranca_token_rel());

$dompdf->loadHtml($html);
$dompdf->setPaper('A4', 'portrait');
$dompdf->render();
$dompdf->stream(
    "romaneios_vendas.pdf",
    array(
        "Attachment" => false
    )
);
?> 