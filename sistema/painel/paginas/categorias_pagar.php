<?php
require_once("verificar.php");
$pag = 'categorias_pagar';
if (@$pagar == 'ocultar') {
	echo "<script>window.location='index'</script>";
	exit();
}
$cadfin_entidade = 'categorias_pagar';
include __DIR__ . "/cadastro_fin/tela.php";
