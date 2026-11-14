<?php
$pag = 'compras';

if (@$compras == 'ocultar') {
	echo "<script>window.location='index.php'</script>";
	exit();
}

$pdv = [
	'tipo' => 'compra',
	'pag' => $pag,
	'data' => date('Y-m-d'),
];

require __DIR__ . '/pdv_diversos/tela.php';
