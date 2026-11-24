<?php
require_once("verificar.php");
require_once("../conexao.php");
require_once(__DIR__ . "/../funcoes/financeiro.php");

$pag = 'receber';
$data_hoje = date('Y-m-d');
$data_inicio_mes = date('Y-m-01');

if (@$receber == 'ocultar') {
	echo "<script>window.location='index'</script>";
	exit();
}
?>

<script src="../painel/paginas/receber/receber/receber_scripts.js"></script>

<div class="d-flex justify-content-between align-items-center mt-2 mb-3 flex-wrap gap-2">
	<div>
		<a class="btn btn-primary text-white" onclick="inserir()" type="button">
			<i class="fe fe-plus me-2"></i>Adicionar Conta
		</a>
	</div>

	<div class="d-flex align-items-center gap-2">
		<div id="btn-deletar" style="display:none" class="dropdown">
			<a href="#" class="btn btn-danger btn-sm dropdown-toggle" data-bs-toggle="dropdown">
				<i class="fe fe-trash-2"></i> Deletar
			</a>
			<div class="dropdown-menu p-3 text-muted" style="width: 220px;">
				<p>Excluir selecionados? <a href="#" onclick="deletarSel()" class="text-danger fw-bold">Sim</a></p>
				<small>Títulos de romaneio/venda não são excluídos por aqui.</small>
			</div>
		</div>

		<div id="btn-baixar" style="display:none">
			<a href="#" class="btn btn-success btn-sm" onclick="abrirModalBaixaMassa()">
				<i class="fa fa-check-square"></i> Receber selecionados
			</a>
		</div>

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
<input type="hidden" id="pago">
<input type="hidden" id="tipo_data_filtro">
<div id="mensagem-excluir" class="text-danger small"></div>

<?php include_once("../painel/paginas/receber/receber/modais.php"); ?>

<script>
	var pag = "<?= $pag ?>";
	window.onload = function() {
		if (typeof buscar === 'function') buscar();
	};
</script>
<script src="js/ajax.js"></script>
<script src="paginas/js/financeiro_comum.js?v=1"></script>
<script>
	// ajax.js redefine listar(); a tela de receber tem filtros próprios
	listar = function () { buscar(); };
</script>
