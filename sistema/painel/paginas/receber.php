<?php
require_once("verificar.php");
require_once("../conexao.php");

$pag = 'receber';
$data_hoje = date('Y-m-d');
$data_inicio_mes = date('Y-m-01');

if (@$receber == 'ocultar') {
	echo "<script>window.location='index'</script>";
	exit();
}
?>

<div class="d-flex justify-content-between align-items-center mt-2 mb-3 flex-wrap gap-2">
	<div>
		<a class="btn btn-primary text-white" onclick="inserir()" type="button">
			<i class="fe fe-plus me-2"></i>Adicionar Conta
		</a>
	</div>

	<div class="d-flex align-items-center gap-2">
		<button type="button" id="relatorio" class="btn btn-danger btn-sm">
			<i class="fa fa-file-pdf"></i> PDF
		</button>
		<input type="hidden" id="tipo_data" value="vencimento">
	</div>
</div>

<div class="justify-content-between">
	<?php include_once("../painel/paginas/receber/receber/filtros.php"); ?>
	<?php include_once("../painel/paginas/receber/receber/cards.php"); ?>
</div>

<div class="row row-sm">
	<div class="col-lg-12">
		<div class="card custom-card">
			<div class="card-body" id="listar"></div>
		</div>
	</div>
</div>

<input type="hidden" id="ids">
<div id="mensagem-baixar"></div>

<?php include_once("../painel/paginas/receber/receber/modais.php"); ?>

<script>
	var pag = "<?= $pag ?>";
	// Inicializa a lista ao carregar a página
	window.onload = function() {
		if (typeof buscar === 'function') buscar();
	};
</script>

<script src="../painel/paginas/receber/receber/receber_scripts.js"></script>
<script src="js/ajax.js"></script>