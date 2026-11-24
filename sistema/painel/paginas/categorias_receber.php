<?php
require_once("verificar.php");
$pag = 'categorias_receber';
if (@$categorias_receber == 'ocultar') {
	echo "<script>window.location='index'</script>";
	exit();
}
$cadfin_entidade = 'categorias_receber';
include __DIR__ . "/cadastro_fin/tela.php";
