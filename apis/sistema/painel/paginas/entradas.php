<?php 
$pag = 'entradas';

//verificar se ele tem a permissão de estar nessa página
if(@$entradas == 'ocultar'){
    echo "<script>window.location='index.php'</script>";
    exit();
}
 ?>

<div class="justify-content-between" style="margin-bottom: 20px; margin-top: 30px">

 	<div class="left-content mt-2" >


	<form method="POST" action="rel/entradas_class.php" target="_blank"> 

		<span class="esc" style="font-size: 14px;">Data Inicial</span>
		<input type="date" id="dataInicial" name="dataInicial" class="form-control" style="width:150px; margin-bottom: 10px; display:inline-block; margin-right: 20px" onchange="mudarData()" required value="<?php echo $data_inicio_mes ?>">

		<span class="esc" style="font-size: 14px">Data Final</span>
		<input type="date" id="dataFinal" name="dataFinal" class="form-control" style="width:150px; display:inline-block;" onchange="mudarData()" required value="<?php echo $data_final_mes ?>">

		<button style="position:absolute; right:100px" class="btn btn-success esc botao_rel" type="submit">Relatório<i class="fa fa-check ms-2"></i></button>

		

	</form>
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


<script type="text/javascript">var pag = "<?=$pag?>"</script>
<script src="js/ajax.js"></script>


<script type="text/javascript">
	function mudarData(){
		var dataInicial = $("#dataInicial").val();
		var dataFinal = $("#dataFinal").val();

		if(dataInicial != "" && dataFinal != ""){			
			listar(dataInicial, dataFinal)
		}
	}
</script>