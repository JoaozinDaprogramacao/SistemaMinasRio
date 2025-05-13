<?php
// gerar_pdf_romaneio_compra.php
require_once("../../conexao.php");
require_once '../dompdf/autoload.inc.php';

use Dompdf\Dompdf;
use Dompdf\Options;

$id = $_GET['id'];

ob_start();
require 'romaneio_compra_pdf.php';
$html = ob_get_clean();

$options = new Options();
$options->set('isRemoteEnabled', true);
$options->set('isHtml5ParserEnabled', true);

$dompdf = new Dompdf($options);
$dompdf->loadHtml($html);
$dompdf->setPaper('A4', 'portrait');
$dompdf->render();

$dompdf->stream(
  "romaneio_compra_" . str_pad($id,6,'0',STR_PAD_LEFT) . ".pdf",
  ["Attachment" => false]
);
