<?php 
$pag = 'estoque';

//verificar se ele tem a permissão de estar nessa página
if(@$produtos == 'ocultar'){
    echo "<script>window.location='../index.php'</script>";
    exit();
}
 ?>

<div class="justify-content-between">
 <div class="left-content mt-2 mb-3">

</div>


  <form action="rel/estoque_produtos_class.php" target="_blank" method="POST">
  	<input type="hidden" name="cat" id="cat">
 	 <div style=" position:absolute; right:10px; margin-bottom: 10px; top:70px">
			<button style="width:120px" type="submit" class="btn btn-danger " title="Gerar Relatório"><i class="fa fa-file-pdf-o"></i> Relatório</button>
		</div>
 </form>

</div>

<div class="row row-sm" style="margin-top: 50px">
<div class="col-lg-12">
<div class="card custom-card">
<div class="card-body" id="listar">

</div>
</div>
</div>
</div>



	<!-- Modal Dados -->
	<div class="modal fade" id="modalDados" tabindex="-1" aria-labelledby="exampleModalLabel" aria-hidden="true">
		<div class="modal-dialog modal-lg">
			<div class="modal-content">
				<div class="modal-header bg-primary text-white">
					<h4 class="modal-title" id="exampleModalLabel"><span id="titulo_dados"></span></h4>
					<button id="btn-fechar-dados" aria-label="Close" class="btn-close" data-bs-dismiss="modal" type="button"><span class="text-white" aria-hidden="true">&times;</span></button>
				</div>

				<div class="modal-body">


					<div class="row">


						<div class="col-md-6">
							<div class="tile">
								<div class="table-responsive">
									<table id="" class="text-left table table-bordered">
										<tr>
											<td class="bg-warning alert-warning w_150">Estoque</td>
											<td><span id="estoque_dados"></span></td>
										</tr>

										<tr>
											<td class="bg-warning alert-warning w_150">Estoque Mínimo</td>
											<td><span id="estoque_minimo_dados"></span></td>
										</tr>
										 

									</table>
								</div>
							</div>
						</div>



						<div class="col-md-6">
							<div class="tile">
								<div class="table-responsive">
									<table id="" class="text-left table table-bordered">
									
									<tr>
											<td align="center"><img src="" id="foto_dados" width="200px"></td>
										</tr>      
										
									</table>
								</div>
							</div>
						</div>

					</div>





				</div>

			</div>
		</div>
	</div>







<!-- Modal Saida-->
		<div class="modal fade" id="modalSaida" tabindex="-1" role="dialog" aria-labelledby="exampleModalLabel" aria-hidden="true">
			<div class="modal-dialog modal-lg" role="document">
				<div class="modal-content">
					<div class="modal-header bg-primary text-white">
						<h4 class="modal-title" id="exampleModalLabel"><span id="nome_saida"></span></h4>
						<button id="btn-fechar-saida" aria-label="Close" class="btn-close" data-bs-dismiss="modal" type="button"><span class="text-white" aria-hidden="true">&times;</span></button>
					</div>

					<div class="modal-body">
						<form id="form-saida">

							<div class="row">
								<div class="col-md-4">
									<div class="form-group">

										<input type="number" class="form-control" id="quantidade_saida" name="quantidade_saida" placeholder="Quantidade Saída" required>    
									</div> 	
								</div>

								<div class="col-md-5">
									<div class="form-group">								
										<input type="text" class="form-control" id="motivo_saida" name="motivo_saida" placeholder="Motivo Saída" required>    
									</div> 	
								</div>
								<div class="col-md-3">
									<button type="submit" class="btn btn-primary">Salvar</button>

								</div>
							</div>	

							<input type="hidden" id="id_saida" name="id">
							<input type="hidden" id="estoque_saida" name="estoque">

						</form>

						<br>
						<small><div id="mensagem-saida" align="center"></div></small>
					</div>


				</div>
			</div>
		</div>





		<!-- Modal Entrada-->
		<div class="modal fade" id="modalEntrada" tabindex="-1" role="dialog" aria-labelledby="exampleModalLabel" aria-hidden="true">
			<div class="modal-dialog modal-lg" role="document">
				<div class="modal-content">
					<div class="modal-header bg-primary text-white">
						<h4 class="modal-title" id="exampleModalLabel"><span id="nome_entrada"></span></h4>
						<button id="btn-fechar-entrada" aria-label="Close" class="btn-close" data-bs-dismiss="modal" type="button"><span class="text-white" aria-hidden="true">&times;</span></button>
					</div>

					<div class="modal-body">
						<form id="form-entrada">

							<div class="row">
								<div class="col-md-4">
									<div class="form-group">

										<input type="number" class="form-control" id="quantidade_entrada" name="quantidade_entrada" placeholder="Quantidade Entrada" required>    
									</div> 	
								</div>

								<div class="col-md-5">
									<div class="form-group">								
										<input type="text" class="form-control" id="motivo_entrada" name="motivo_entrada" placeholder="Motivo Entrada" required>    
									</div> 	
								</div>
								<div class="col-md-3">
									<button type="submit" class="btn btn-primary">Salvar</button>

								</div>
							</div>	

							<input type="hidden" id="id_entrada" name="id">
							<input type="hidden" id="estoque_entrada" name="estoque">

						</form>

						<br>
						<small><div id="mensagem-entrada" align="center"></div></small>
					</div>


				</div>
			</div>
		</div>




<script type="text/javascript">var pag = "<?=$pag?>"</script>
<script src="js/ajax.js"></script>

	<script type="text/javascript">
		$(document).ready(function() {
			$('.sel2').select2({
				dropdownParent: $('#modalForm')
			});
		});
	</script>


<script type="text/javascript">
	function carregarImg() {
    var target = document.getElementById('target');
    var file = document.querySelector("#foto").files[0];
    
        var reader = new FileReader();

        reader.onloadend = function () {
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


			$("#form-saida").submit(function () {

				event.preventDefault();
				var formData = new FormData(this);

				$.ajax({
					url: 'paginas/detalhes_materiais/saida.php',
					type: 'POST',
					data: formData,

					success: function (mensagem) {
						$('#mensagem-saida').text('');
						$('#mensagem-saida').removeClass()
						if (mensagem.trim() == "Salvo com Sucesso") {

							$('#btn-fechar-saida').click();
							listar();          

						} else {

							$('#mensagem-saida').addClass('text-danger')
							$('#mensagem-saida').text(mensagem)
						}


					},

					cache: false,
					contentType: false,
					processData: false,

				});

			});
		</script>





		<script type="text/javascript">


			$("#form-entrada").submit(function () {

				event.preventDefault();
				var formData = new FormData(this);

				$.ajax({
					url: 'paginas/detalhes_materiais/entrada.php',
					type: 'POST',
					data: formData,

					success: function (mensagem) {
						$('#mensagem-entrada').text('');
						$('#mensagem-entrada').removeClass()
						if (mensagem.trim() == "Salvo com Sucesso") {

							$('#btn-fechar-entrada').click();
							listar();          

						} else {

							$('#mensagem-entrada').addClass('text-danger')
							$('#mensagem-entrada').text(mensagem)
						}


					},

					cache: false,
					contentType: false,
					processData: false,

				});

			});

			
		</script>