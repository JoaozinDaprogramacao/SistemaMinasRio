<?php
require_once("verificar.php");
$pag = 'centros_custo';
if (@$centros_custo == 'ocultar') {
	echo "<script>window.location='index'</script>";
	exit();
}
$cadfin_entidade = 'centros_custo';
include __DIR__ . "/cadastro_fin/tela.php";
