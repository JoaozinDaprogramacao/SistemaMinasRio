<?php
$pag = 'romaneio_venda_detalhes';

//verificar se ele tem a permissão de estar nessa página
if (@$produtos == 'ocultar') {
	echo "<script>window.location='../index.php'</script>";
	exit();
}
?>

<script src="js/ajax.js"></script>

<div class="justify-content-between">
	<div class="left-content mt-2 mb-3">
		<div class="row g-2 mb-3 mt-1 align-items-center">
			<!-- Filtro de Atacadista -->
			<div class="col-auto">
				<select name="cliente" id="cliente" class="form-select form-select-sm" onchange="buscar()">
					<option value="">Cliente</option>
					<?php
					$query = $pdo->query("SELECT * FROM clientes ORDER BY id DESC");
					$res = $query->fetchAll(PDO::FETCH_ASSOC);
					for ($i = 0; $i < @count($res); $i++) {
						echo '<option value="' . $res[$i]['id'] . '">' . $res[$i]['nome'] . ' - ' . $res[$i]['cpf'] . '</option>';
					}
					?>
				</select>

			</div>
			<!-- Filtro de Data Inicial -->
			<div class="col-auto">
				<input type="date" name="dataInicial" id="dataInicial" class="form-control form-control-sm" onchange="buscar()">
			</div>

			<!-- Filtro de Data Final -->
			<div class="col-auto">
				<input type="date" name="dataFinal" id="dataFinal" class="form-control form-control-sm" onchange="buscar()">
			</div>
		</div>



	</div>


	<form id="relatorio" action="rel/romaneio_venda_recibo.php" target="_blank" method="POST">
		<input type="hidden" name="dataInicial" id="dataInicialRel">
		<input type="hidden" name="dataFinal" id="dataFinalRel">
		<input type="hidden" name="cliente" id="clienteRel">
		<div style="position:absolute; right:10px; margin-bottom: 10px; top:70px">
			<button style="width:40px" type="submit" class="btn btn-danger ocultar_mobile_app" title="Gerar Relatório">
				<i class="fa fa-file-pdf-o"></i>
			</button>
		</div>
	</form>

</div>

<?php include_once("../painel/paginas/romaneio_venda_detalhes/romaneio_venda_detalhes/cards.php")?>


<div class="row row-sm">
	<div class="col-lg-12">
		<div class="card custom-card">
			<div class="card-body" id="listar">

			</div>
		</div>
	</div>
</div>

<?php include_once("../painel/paginas/romaneio_venda_detalhes/romaneio_venda_detalhes/modais.php"); ?>

<input type="hidden" id="ids">

<script>
	var pag = "<?= $pag ?>";
	// Inicializa a lista ao carregar a página
	window.onload = function() {
		if (typeof buscar === 'function') buscar();
	};
</script>

<script src="../painel/paginas/romaneio_venda_detalhes/romaneio_venda_detalhes/romaneio_venda_detalhes_scripts.js"></script>

<script type="text/javascript">
	var pag = "<?= $pag ?>"
</script>

<script type="text/javascript">
	$(document).ready(function() {
		$('.select2').select2({
			placeholder: "Selecione os romaneios",
			allowClear: true,
			width: 'resolve'
		});
	});
</script>


<script type="text/javascript">
	function carregarImg() {
		var target = document.getElementById('target');
		var file = document.querySelector("#foto").files[0];

		var reader = new FileReader();

		reader.onloadend = function() {
			target.src = reader.result;
		};

		if (file) {
			reader.readAsDataURL(file);

		} else {
			target.src = "";
		}
	}
</script>


<script type="text/javascript">
	function buscarCat(id) {
		$('#cat').val(id);
		listar(id)
	}
</script>