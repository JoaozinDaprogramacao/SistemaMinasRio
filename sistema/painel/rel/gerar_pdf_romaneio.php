<?php
require_once("../../conexao.php");
require_once '../dompdf/autoload.inc.php';
use Dompdf\Dompdf;
use Dompdf\Options;

// Recebe o ID do romaneio
$id = $_GET['id'];

// Carrega o conteúdo HTML do template
ob_start();
require 'romaneio_venda_pdf.php';
$html = ob_get_clean();

// Configurações do DOMPDF
$options = new Options();
$options->set('isRemoteEnabled', true);
$options->set('isHtml5ParserEnabled', true);

// Inicializa o DOMPDF
$dompdf = new Dompdf($options);

// Carrega o HTML
$dompdf->loadHtml($html);

// Configura o papel
$dompdf->setPaper('A4', 'portrait');

// Renderiza o PDF
$dompdf->render();

// Gera o PDF
$dompdf->stream(
    "romaneio_" . str_pad($id, 6, '0', STR_PAD_LEFT) . ".pdf",
    array(
        "Attachment" => false
    )
); 