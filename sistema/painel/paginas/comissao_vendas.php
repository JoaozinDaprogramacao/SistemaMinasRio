<?php
$pag = 'comissao_vendas';

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

			<div class="col-auto">
				<input type="date" name="dataInicial" id="dataInicial" class="date form-control form-control-sm" onchange="buscar()">
			</div>

			<div class="col-auto">
				<input type="date" name="dataFinal" id="dataFinal" class="date form-control form-control-sm" onchange="buscar()">
			</div>

			<div class="col-auto">
				<button type="button" class="btn btn-outline-secondary btn-sm" onclick="limparFiltros()" title="Limpar Filtros">
					<i class="fa fa-eraser"></i> Limpar
				</button>
			</div>
		</div>
	</div>

	<div class="card shadow mb-4" style="border-top: 4px solid #2b7a00;">
		<div class="card-body" id="listar-resumo">
		</div>
	</div>

	<div class="row row-sm">
		<div class="col-lg-12">
			<div class="card custom-card">
				<div class="card-body" id="listar">

				</div>
			</div>
		</div>
	</div>


	<input type="hidden" id="ids">

	<script>
		var pag = "<?= $pag ?>";
		// Inicializa a lista ao carregar a página
		window.onload = function() {
			if (typeof buscar === 'function') buscar();
		};
	</script>

	<script src="../painel/paginas/comissao_vendas/comissao_vendas/comissao_vendas_scripts.js"></script>

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