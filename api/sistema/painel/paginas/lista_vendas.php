<?php 
$pag = 'lista_vendas';

//verificar se ele tem a permissão de estar nessa página
if(@$lista_vendas == 'ocultar'){
    echo "<script>window.location='index.php'</script>";
    exit();
}
?>

<div class="justify-content-between">
	<form action="rel/pagar_class.php" target="_blank" method="POST">
 	<div class="left-content mt-2">




		<div class="row" style="padding:15px; margin-top: 15px">
		<div class="col-md-5" style="margin-bottom:5px;">
			<div style="float:left; margin-right:10px"><span><small><i title="Data de Vencimento Inicial" class="fa fa-calendar-o"></i></small></span></div>
			<div  style="float:left; margin-right:20px">
				<input type="date" class="form-control " name="data-inicial"  id="data-inicial" value="<?php echo date('Y-m-d') ?>" required onchange="buscar()">
			</div>

			<div style="float:left; margin-right:10px"><span><small><i title="Data de Vencimento Final" class="fa fa-calendar-o"></i></small></span></div>
			<div  style="float:left; margin-right:30px">
				<input type="date" class="form-control " name="data-final"  id="data-final" value="<?php echo date('Y-m-d') ?>" required onchange="buscar()">
			</div>
		</div>

		<div style="display: inline-block;">
			<select class="form-select ocultar_mobile" aria-label="Default select example" name="status-busca" id="status-busca" onchange="buscar()">
				<option value="">Todas</option>
				<option value="Não">Pendentes</option>
				<option value="Sim">Pagas</option>
				
			</select>
		</div>
	



			<div style="display: inline-block; margin-top: 5px">
				<span class="ocultar_mobile" style="font-size: 14px; border:1px solid #6092a8; padding:5px; ">
					<small >
						<a title="Contas à Pagar Vencidas" class="text-muted" href="#" onclick="buscar();  $('#tipo_conta').val('Vencidas')"><span>Vencidas</span></a> / 
						<a title="Contas à Pagar Hoje" class="text-muted" href="#" onclick="buscar(); $('#tipo_conta').val('Hoje')"><span>Hoje</span></a> / 
						<a title="Contas à Pagar Amanhã" class="text-muted" href="#" onclick="buscar(); $('#tipo_conta').val('Amanha')"><span>Amanhã</span></a> / 
						<a title="Vendas Canceladas" class="text-muted" href="#" onclick="buscar(); $('#tipo_conta').val('Canceladas')"><span>Canceladas</span></a>
					</small>
				</span>
			</div>

	
		<small class="ocultar_mobile" style="display: inline-block; position: absolute; right: 20px; margin-top: 10px"><i class="fa fa-usd verde"></i> <span class="text-dark">Total: <span class="verde" id="total_itens"></span></span></small>



	</div>

	<input type="hidden" name="tipo_data" id="tipo_data">
	<input type="hidden" name="tipo_conta" id="tipo_conta">
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


<!-- Modal Parcelar-->
<div class="modal fade" id="modalParcelar" tabindex="-1" role="dialog" aria-labelledby="exampleModalLabel" aria-hidden="true" >
	<div class="modal-dialog" role="document">
		<div class="modal-content">
			<div class="modal-header bg-primary text-white">
				<h4 class="modal-title" id="tituloModal">Parcelar Conta: <span id="nome-parcelar"> </span></h4>
				<button id="btn-fechar-parcelar" aria-label="Close" class="btn-close" data-bs-dismiss="modal" type="button"><span class="text-white" aria-hidden="true">&times;</span></button>
			</div>
			<form method="post" id="form-parcelar">
				<div class="modal-body">


					<div class="row">
						<div class="col-md-3">
							<div class="mb-3">
								<label for="exampleFormControlInput1" class="form-label">Valor</label>
								<input type="text" class="form-control" name="valor-parcelar"  id="valor-parcelar"  readonly>
							</div>
						</div>

						<div class="col-md-3">
							<div class="mb-3">
								<label for="exampleFormControlInput1" class="form-label">Parcelas</label>
								<input type="number" class="form-control" name="qtd-parcelar"  id="qtd-parcelar"  required>
							</div>
						</div>

						<div class="col-md-6 mt-1">
							<div class="form-group"> 
								<label>Frequência Parcelas</label> 
								<select class="form-control sel3" name="frequencia" id="frequencia-parcelar" required style="width:100%;">
									<option value="0">Uma Vez</option>
									<?php 
									$query = $pdo->query("SELECT * FROM frequencias order by id asc");
									$res = $query->fetchAll(PDO::FETCH_ASSOC);
									for($i=0; $i < @count($res); $i++){
										foreach ($res[$i] as $key => $value){	}
											$id_item = $res[$i]['id'];
										$nome_item = $res[$i]['frequencia'];
										$dias = $res[$i]['dias'];

										if($nome_item != 'Uma Vez' and $nome_item != 'Única'){

											?>
											<option <?php if($nome_item == 'Mensal'){ ?> selected <?php } ?> value="<?php echo $dias ?>"><?php echo $nome_item ?></option>

										<?php } } ?>


									</select>
								</div>
							</div>

							
						</div>			



				
						<input type="hidden" name="id-parcelar" id="id-parcelar"> 
						<input type="hidden" name="nome-parcelar" id="nome-input-parcelar"> 
						<small><div id="mensagem-parcelar" align="center" class="mt-3"></div></small>

						<div align="right">
							<div class="col-md-3" style="margin-top:20px">						 
								<button type="submit" class="btn btn-primary">Parcelar</button>
							</div>
						</div>						

					</div>

						


				</form>

			</div>
		</div>
	</div>






	<!-- Modal Baixar-->
	<div class="modal fade" id="modalBaixar" tabindex="-1" aria-labelledby="exampleModalLabel" aria-hidden="true">
		<div class="modal-dialog">
			<div class="modal-content">
				<div class="modal-header bg-primary text-white">
					<h4 class="modal-title" id="tituloModal">Baixar Conta: <span id="descricao-baixar"> </span></h4>
					<button id="btn-fechar-baixar" aria-label="Close" class="btn-close" data-bs-dismiss="modal" type="button"><span class="text-white" aria-hidden="true">&times;</span></button>
				</div>
				<form id="form-baixar" method="post">
					<div class="modal-body">

						<div class="row">
							<div class="col-md-6">
								<div class="mb-3">
									<label for="exampleFormControlInput1" class="form-label">Valor <small class="text-muted">(Total ou Parcial)</small></label>
									<input onkeyup="totalizar()" type="text" class="form-control" name="valor-baixar"  id="valor-baixar" required>
								</div>
							</div>


							<div class="col-md-6 mt-1">
								<div class="form-group"> 
									<label>Tipo Pagamento</label> 
									<select class="form-control sel4" name="saida-baixar" id="saida-baixar" required style="width:100%;">	
									<?php 
									$query = $pdo->query("SELECT * FROM formas_pgto order by id asc");
									$res = $query->fetchAll(PDO::FETCH_ASSOC);
									for($i=0; $i < @count($res); $i++){
										foreach ($res[$i] as $key => $value){}

											?>	
										<option value="<?php echo $res[$i]['nome'] ?>"><?php echo $res[$i]['nome'] ?></option>

									<?php } ?>

								</select>
								</div>
							</div>

						</div>	


						<div class="row">


							<div class="col-md-4">
								<div class="mb-3">
									<label for="exampleFormControlInput1" class="form-label">Multa em R$</label>
									<input onkeyup="totalizar()" type="text" class="form-control" name="valor-multa"  id="valor-multa" placeholder="Ex 15.00" value="0">
								</div>
							</div>

							<div class="col-md-4">
								<div class="mb-3">
									<label for="exampleFormControlInput1" class="form-label">Júros em R$</label>
									<input onkeyup="totalizar()" type="text" class="form-control" name="valor-juros"  id="valor-juros" placeholder="Ex 0.15" value="0">
								</div>
							</div>

							<div class="col-md-4">
								<div class="mb-3">
									<label for="exampleFormControlInput1" class="form-label">Desconto em R$</label>
									<input onkeyup="totalizar()" type="text" class="form-control" name="valor-desconto"  id="valor-desconto" placeholder="Ex 15.00" value="0" >
								</div>
							</div>

						</div>


						<div class="row">

							<div class="col-md-6">
								<div class="mb-3">
									<label for="exampleFormControlInput1" class="form-label">Data da Baixa</label>
									<input type="date" class="form-control" name="data-baixar"  id="data-baixar" value="<?php echo date('Y-m-d') ?>" >
								</div>
							</div>


							<div class="col-md-6">
								<div class="mb-3">
									<label for="exampleFormControlInput1" class="form-label">SubTotal</label>
									<input type="text" class="form-control" name="subtotal"  id="subtotal" readonly>
								</div>	
							</div>
						</div>




						<small><div id="mensagem-baixar" align="center"></div></small>

						<input type="hidden" class="form-control" name="id-baixar"  id="id-baixar">

						<div align="right">
							<button id="btn-salvar-baixar" type="submit" class="btn btn-success">Baixar</button>
						</div>


					</div>

				</form>
			</div>
		</div>
	</div>





	<!-- Modal Residuos-->
	<div class="modal fade" id="modalResiduos" tabindex="-1" aria-labelledby="exampleModalLabel" aria-hidden="true">
		<div class="modal-dialog">
			<div class="modal-content">
				<div class="modal-header">
					<h4 class="modal-title" id="tituloModal">Residuos da Conta</h4>
					<button id="btn-fechar-parcelar" type="button" class="close" data-dismiss="modal" aria-label="Close" style="margin-top: -20px">
						<span aria-hidden="true">&times;</span>
					</button>
				</div>
				<div class="modal-body">

					<small><div id="listar-residuos"></div></small>

				</div>
				
			</div>
		</div>
	</div>






	<!-- Modal Arquivos -->
	<div class="modal fade" id="modalArquivos" tabindex="-1" aria-labelledby="exampleModalLabel" aria-hidden="true">
		<div class="modal-dialog modal-lg">
			<div class="modal-content">
				<div class="modal-header bg-primary text-white">
					<h4 class="modal-title" id="tituloModal">Gestão de Arquivos - <span id="nome-arquivo"> </span></h4>
					 <button id="btn-fechar-arquivos" aria-label="Close" class="btn-close" data-bs-dismiss="modal" type="button"><span class="text-white" aria-hidden="true">&times;</span></button>
				</div>
				<form id="form-arquivos" method="post">
					<div class="modal-body">

						<div class="row">
							<div class="col-md-8">						
								<div class="form-group"> 
									<label>Arquivo</label> 
									<input class="form-control" type="file" name="arquivo_conta" onChange="carregarImgArquivos();" id="arquivo_conta">
								</div>	
							</div>
							<div class="col-md-4">	
								<div id="divImgArquivos">
									<img src="images/arquivos/sem-foto.png"  width="60px" id="target-arquivos">									
								</div>					
							</div>




						</div>

						<div class="row">
							<div class="col-md-8">
								<input type="text" class="form-control" name="nome-arq"  id="nome-arq" placeholder="Nome do Arquivo * " required>
							</div>

							<div class="col-md-4">										 
								<button type="submit" class="btn btn-primary">Inserir</button>
							</div>
						</div>

						<hr>

						<small><div id="listar-arquivos"></div></small>

						<br>
						<small><div align="center" id="mensagem-arquivo"></div></small>

						<input type="hidden" class="form-control" name="id-arquivo"  id="id-arquivo">


					</div>
				</form>
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
			$(document).ready(function() {
				$('.sel3').select2({
					dropdownParent: $('#modalParcelar')
				});
			});
		</script>

		<script type="text/javascript">
			$(document).ready(function() {
				$('.sel4').select2({
					dropdownParent: $('#modalBaixar')
				});
			});
		</script>



			<script type="text/javascript">

			function buscar(){
				var venc = $('#tipo_conta').val();
				var dataInicial = $('#data-inicial').val();
				var dataFinal = $('#data-final').val();
				var status = $('#status-busca').val();
				listar(venc, dataInicial, dataFinal, status);

			}

		</script>




		<script type="text/javascript">
			function carregarImg() {
				var target = document.getElementById('target');
				var file = document.querySelector("#arquivo").files[0];

				var arquivo = file['name'];
				resultado = arquivo.split(".", 2);

				if(resultado[1] === 'pdf'){
					$('#target').attr('src', "images/pdf.png");
					return;
				}

				if(resultado[1] === 'rar' || resultado[1] === 'zip'){
					$('#target').attr('src', "images/rar.png");
					return;
				}

				if(resultado[1] === 'doc' || resultado[1] === 'docx'){
					$('#target').attr('src', "images/word.png");
					return;
				}

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
			$("#form-parcelar").submit(function () {

				$('#mensagem-parcelar').text('Carregando!!!');
    			$('#btn-salvar-parcelar').hide();

				event.preventDefault();
				var formData = new FormData(this);

				$.ajax({
					url: 'paginas/' + pag + "/parcelar.php",
					type: 'POST',
					data: formData,

					success: function (mensagem) {
						$('#mensagem-parcelar').text('');
						$('#mensagem-parcelar').removeClass()
						if (mensagem.trim() == "Parcelado com Sucesso") {                    
							$('#btn-fechar-parcelar').click();
							buscar();
						} else {
							$('#mensagem-parcelar').addClass('text-danger')
							$('#mensagem-parcelar').text(mensagem)
						}
						$('#btn-salvar-parcelar').show();

					},

					cache: false,
					contentType: false,
					processData: false,

				});

			});
		</script>



		<script type="text/javascript">
			$("#form-baixar").submit(function () {

				$('#mensagem-baixar').text('Carregando!!!');
    			$('#btn-salvar-baixar').hide();

				event.preventDefault();
				var formData = new FormData(this);

				$.ajax({
					url: 'paginas/' + pag + "/baixar.php",
					type: 'POST',
					data: formData,

					success: function (mensagem) {
						$('#mensagem-baixar').text('');
						$('#mensagem-baixar').removeClass()
						if (mensagem.trim() == "Baixado com Sucesso") { 

							$('#btn-fechar-baixar').click();
							buscar();
						} else {
							$('#mensagem-baixar').addClass('text-danger')
							$('#mensagem-baixar').text(mensagem)
						}

						$('#btn-salvar-baixar').hide();

					},

					cache: false,
					contentType: false,
					processData: false,

				});

			});
		</script>


		<script type="text/javascript">

			function totalizar(){
				valor = $('#valor-baixar').val();
				desconto = $('#valor-desconto').val();
				juros = $('#valor-juros').val();
				multa = $('#valor-multa').val();

				valor = valor.replace(",", ".");
				desconto = desconto.replace(",", ".");
				juros = juros.replace(",", ".");
				multa = multa.replace(",", ".");

				if(valor == ""){
				valor = 0;
			}

			if(desconto == ""){
				desconto = 0;
			}

			if(juros == ""){
				juros = 0;
			}

			if(multa == ""){
				multa = 0;
			}

				subtotal = parseFloat(valor) + parseFloat(juros) + parseFloat(multa) - parseFloat(desconto);


				console.log(subtotal)

				$('#subtotal').val(subtotal);

			}
		</script>


	



		


		<script type="text/javascript">
			function carregarImgArquivos() {
				var target = document.getElementById('target-arquivos');
				var file = document.querySelector("#arquivo_conta").files[0];

				var arquivo = file['name'];
				resultado = arquivo.split(".", 2);

				if(resultado[1] === 'pdf'){
					$('#target-arquivos').attr('src', "images/pdf.png");
					return;
				}

				if(resultado[1] === 'rar' || resultado[1] === 'zip'){
					$('#target-arquivos').attr('src', "images/rar.png");
					return;
				}

				if(resultado[1] === 'doc' || resultado[1] === 'docx' || resultado[1] === 'txt'){
					$('#target-arquivos').attr('src', "images/word.png");
					return;
				}


				if(resultado[1] === 'xlsx' || resultado[1] === 'xlsm' || resultado[1] === 'xls'){
					$('#target-arquivos').attr('src', "images/excel.png");
					return;
				}


				if(resultado[1] === 'xml'){
					$('#target-arquivos').attr('src', "images/xml.png");
					return;
				}



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
			$("#form-arquivos").submit(function () {
				event.preventDefault();
				var formData = new FormData(this);

				$.ajax({
					url: 'paginas/' + pag + "/arquivos.php",
					type: 'POST',
					data: formData,

					success: function (mensagem) {
						$('#mensagem-arquivo').text('');
						$('#mensagem-arquivo').removeClass()
						if (mensagem.trim() == "Inserido com Sucesso") {                    
						//$('#btn-fechar-arquivos').click();
						$('#nome-arq').val('');
						$('#arquivo_conta').val('');
						$('#target-arquivos').attr('src','images/arquivos/sem-foto.png');
						listarArquivos();
					} else {
						$('#mensagem-arquivo').addClass('text-danger')
						$('#mensagem-arquivo').text(mensagem)
					}

				},

				cache: false,
				contentType: false,
				processData: false,

			});

			});
		</script>

		<script type="text/javascript">
			function listarArquivos(){
				var id = $('#id-arquivo').val();	
				$.ajax({
					url: 'paginas/' + pag + "/listar-arquivos.php",
					method: 'POST',
					data: {id},
					dataType: "text",

					success:function(result){
						$("#listar-arquivos").html(result);
					}
				});
			}

		</script>




<script type="text/javascript">
	
$("#form-contas").submit(function () {
	event.preventDefault();
	var formData = new FormData(this);

	$.ajax({
		url: 'paginas/' + pag + "/inserir.php",
		type: 'POST',
		data: formData,

		success: function (mensagem) {
            $('#mensagem').text('');
            $('#mensagem').removeClass()
            if (mensagem.trim() == "Salvo com Sucesso") {                    
                    $('#btn-fechar').click();
                   var dataInicial = $('#data-inicial').val();
				var dataFinal = $('#data-final').val();
				var status = $('#status-busca').val();
				var alterou_data = 'Sim';
				buscar();
                } else {
                	$('#mensagem').addClass('text-danger')
                    $('#mensagem').text(mensagem)
                }

            },

            cache: false,
            contentType: false,
            processData: false,
            
        });

});


function cancelar(id){   
    $('#mensagem-excluir').text('Excluindo...')
    
    $.ajax({
        url: 'paginas/' + pag + "/excluir.php",
        method: 'POST',
        data: {id},
        dataType: "html",

        success:function(mensagem){
            if (mensagem.trim() == "Excluído com Sucesso") {                
                buscar();
            } else {
                $('#mensagem-excluir').addClass('text-danger')
                $('#mensagem-excluir').text(mensagem)
            }
        }
    });
}

</script>