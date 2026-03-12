<?php
$pag = 'romaneio_venda';

//verificar se ele tem a permissão de estar nessa página
if (@$produtos == 'ocultar') {
	echo "<script>window.location='../index.php'</script>";
	exit();
}
?>


<script src="../painel/paginas/romaneio_venda/romaneio_venda/romaneio_venda_scripts.js"></script>	

<script src="js/ajax.js"></script>
<script>
	function mascara_decimal(el) {
		// el pode ser this (o próprio <input>) ou um seletor jQuery
		var $el = $(el);
		var v = $el.val() || '';

		// 1) tira tudo que não for dígito
		v = v.replace(/\D/g, '');
		// 2) se vazio, vira "0"
		if (v === '') v = '0';
		// 3) garante no mínimo 3 dígitos
		while (v.length < 3) v = '0' + v;
		// 4) separa reais / centavos
		var inteiro = v.slice(0, -2);
		var centavos = v.slice(-2);
		// 5) separador de milhares (opcional)
		inteiro = inteiro.replace(/\B(?=(\d{3})+(?!\d))/g, '.');
		// 6) atualiza campo
		$el.val(inteiro + ',' + centavos);

		// 7) recálculo de totais
		if (typeof calculaTotais === 'function') calculaTotais();
	}
</script>


<div class="justify-content-between">
	<div class="left-content mt-2 mb-3">
		<a class="btn ripple btn-primary text-white" onclick="inserir()" type="button"><i class="fe fe-plus me-2"></i>Novo Romaneio</a>



		<div class="dropdown" style="display: inline-block;">
			<a href="#" aria-expanded="false" aria-haspopup="true" data-bs-toggle="dropdown" class="btn btn-danger dropdown" id="btn-deletar" style="display:none"><i class="fe fe-trash-2"></i> Deletar</a>
			<div class="dropdown-menu tx-13">
				<div style="width: 240px; padding:15px 5px 0 10px;" class="dropdown-item-text">
					<p>Excluir Selecionados? <a href="#" onclick="deletarSel()"><span class="text-danger">Sim</span></a></p>
				</div>
			</div>
		</div>

		<?php include_once("../painel/paginas/romaneio_venda/romaneio_venda/filtros.php"); ?>


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

<div class="row row-sm">
	<div class="col-lg-12">
		<div class="card custom-card">
			<div class="card-body" id="listar">

			</div>
		</div>
	</div>
</div>

<input type="hidden" id="ids">

<script src="paginas/js/<?php echo $pag; ?>/romaneio.js"></script>

<?php include_once("../painel/paginas/romaneio_venda/romaneio_venda/modais.php"); ?>

<script>
	var pag = "<?= $pag ?>";
	// Inicializa a lista ao carregar a página
	window.onload = function() {
		if (typeof buscar === 'function') buscar();
	};
</script>

<link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
