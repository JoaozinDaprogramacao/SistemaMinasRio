<?php
$pag = 'comissao_vendas';

// verificar se ele tem a permissão de estar nessa página
if (@$produtos == 'ocultar') {
	echo "<script>window.location='../index.php'</script>";
	exit();
}

// --- LÓGICA DE QUALIDADES (Mover para o topo do arquivo base) ---
$pdo->exec("SET NAMES utf8");

if (!function_exists('padronizarQualidade')) {
	function padronizarQualidade($texto)
	{
		$num = preg_replace('/[^0-9]/', '', $texto);
		return $num ? $num . '°' : '';
	}
}

$qualidades = [];
$query_cat = $pdo->query("SELECT nome FROM categorias");
$res_cat = $query_cat->fetchAll(PDO::FETCH_ASSOC);

foreach ($res_cat as $c) {
	if (preg_match('/(\d+)(\xAA|\xB0|ª|°)$/u', $c['nome'], $matches)) {
		$qualidades[] = padronizarQualidade($matches[0]);
	}
}
$qualidades = array_unique($qualidades);
sort($qualidades);
// ----------------------------------------------------------------
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

	<?php include_once("../painel/paginas/comissao_vendas/comissao_vendas/modais.php"); ?>

	<input type="hidden" id="ids">

	<script>
		// Definindo a variável global aqui
		window.listaQualidadesGlobal = <?php echo json_encode($qualidades); ?>;
		var pag = "<?= $pag ?>";

		window.onload = function() {
			if (typeof buscar === 'function') buscar();
		};
	</script>

	<script src="../painel/paginas/comissao_vendas/comissao_vendas/comissao_vendas_scripts.js"></script>

	<script type="text/javascript">
		$(document).ready(function() {
			$('.select2').select2({
				placeholder: "Selecione os romaneios",
				allowClear: true,
				width: 'resolve'
			});
		});

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

		function buscarCat(id) {
			$('#cat').val(id);
			listar(id)
		}
	</script>
</div>