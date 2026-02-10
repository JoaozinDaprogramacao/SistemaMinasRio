<?php
require_once("verificar.php");
require_once("../conexao.php"); // Garanta que a conexão esteja aqui

$pag = 'pagar';
$data_hoje = date('Y-m-d');
$data_inicio_mes = date('Y-m-01');
$data_final_mes = date('Y-m-t'); // t traz o último dia do mês

if (@$pagar == 'ocultar') {
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
		<div id="btn-deletar" style="display:none" class="dropdown">
			<a href="#" class="btn btn-danger btn-sm dropdown-toggle" data-bs-toggle="dropdown">
				<i class="fe fe-trash-2"></i> Deletar
			</a>
			<div class="dropdown-menu p-3 text-muted" style="width: 200px;">
				<p>Excluir selecionados? <a href="#" onclick="deletarSel()" class="text-danger fw-bold">Sim</a></p>
			</div>
		</div>

		<div id="btn-baixar" style="display:none" class="dropdown">
			<a href="#" class="btn btn-success btn-sm dropdown-toggle" data-bs-toggle="dropdown" onclick="valorBaixar()">
				<i class="fa fa-check-square"></i> Baixar
			</a>
			<div class="dropdown-menu p-3 text-muted" style="width: 220px;">
				<p>Baixar selecionadas?</p>
				<p>Total: <span class="text-success fw-bold" id="total_contas"></span></p>
				<a href="#" onclick="deletarSelBaixar()" class="btn btn-success btn-sm w-100">Confirmar</a>
			</div>
		</div>

		<button type="button" id="relatorio" class="btn btn-danger btn-sm">
			<i class="fa fa-file-pdf"></i> PDF
		</button>
	</div>
</div>

<div class="justify-content-between">
	<?php include_once("../painel/paginas/pagar/pagar/filtros.php"); ?>
	<?php include_once("../painel/paginas/pagar/pagar/cards.php"); ?>
</div>

<div class="row row-sm">
	<div class="col-lg-12">
		<div class="card custom-card">
			<div class="card-body" id="listar"></div>
		</div>
	</div>
</div>

<input type="hidden" id="ids">
<input type="hidden" id="tipo_data" value="vencimento">
<input type="hidden" id="pago">
<input type="hidden" id="tipo_data_filtro">

<div id="mensagem-baixar"></div>

<?php include_once("../painel/paginas/pagar/pagar/modais.php"); ?>

<script>
	var pag = "<?= $pag ?>";
	window.onload = function() {
		if (typeof buscar === 'function') buscar();
	};
</script>

<script src="../painel/paginas/pagar/pagar/pagar_scripts.js"></script>
<script src="js/ajax.js"></script>