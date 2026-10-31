<?php
$pag = 'detalhes_materiais';

//verificar se ele tem a permissão de estar nessa página
if (@$produtos == 'ocultar') {
	echo "<script>window.location='../index.php'</script>";
	exit();
}
?>

<div class="justify-content-between">
	<div class="left-content mt-2 mb-3">
		<small class="text-muted">Histórico das movimentações de materiais geradas pelas vendas.</small>
	</div>

	<form action="rel/materiais_class.php" target="_blank" method="POST">
		<div style=" position:absolute; right:10px; margin-bottom: 10px; top:70px">
			<button style="width:40px" type="submit" class="btn btn-danger ocultar_mobile_app" title="Relatório de Materiais"><i class="fa fa-file-pdf-o"></i></button>
		</div>
	</form>

</div>


<?php
$query = $pdo->query("SELECT * from materiais order by nome asc");
$res = $query->fetchAll(PDO::FETCH_ASSOC);
$linhas = @count($res);
if ($linhas > 0) {
?>
	<ul class="nav nav-tabs" id="myTab" role="tablist" style="background: #FFF">


		<li class="nav-item" role="presentation">
			<a onclick="buscarCat('')" class="nav-link active" data-bs-toggle="tab" data-bs-target="#home" type="button" role="tab" aria-controls="home" aria-selected="true">Todos</a>
		</li>

		<?php
		for ($i = 0; $i < $linhas; $i++) {
			$id = (int) $res[$i]['id'];
			$nome = htmlspecialchars($res[$i]['nome']);

		?>

			<li class="nav-item" role="presentation">
				<a onclick="buscarCat('<?php echo $id ?>')" class="nav-link " id="home-tab" data-bs-toggle="tab" data-bs-target="#home" type="button" role="tab" aria-controls="home" aria-selected="true"><?php echo $nome ?></a>
			</li>
		<?php } ?>
	</ul>
<?php } ?>


<div class="row row-sm">
	<div class="col-lg-12">
		<div class="card custom-card">
			<div class="card-body" id="listar">

			</div>
		</div>
	</div>
</div>


<script type="text/javascript">
	var pag = "<?= $pag ?>"
</script>
<script src="js/ajax.js"></script>

<script type="text/javascript">
	// A aba escolhe o material no filtro da tabela (o filtro fica em listar.php)
	function buscarCat(id) {
		$('#material').val(id);
		aplicarFiltros();
	}
</script>
