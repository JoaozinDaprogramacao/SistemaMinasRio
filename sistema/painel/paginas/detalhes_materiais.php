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
		<div class="dropdown" style="display: inline-block;">
			<a href="#" aria-expanded="false" aria-haspopup="true" data-bs-toggle="dropdown" class="btn btn-danger dropdown" id="btn-deletar" style="display:none"><i class="fe fe-trash-2"></i> Deletar</a>
			<div class="dropdown-menu tx-13">
				<div style="width: 240px; padding:15px 5px 0 10px;" class="dropdown-item-text">
					<p>Excluir Selecionados? <a href="#" onclick="deletarSel()"><span class="text-danger">Sim</span></a></p>
				</div>
			</div>
		</div>



	</div>


	<form action="rel/produtos_class.php" target="_blank" method="POST">
		<input type="hidden" name="cat" id="cat">
		<div style=" position:absolute; right:10px; margin-bottom: 10px; top:70px">
			<button style="width:40px" type="submit" class="btn btn-danger ocultar_mobile_app" title="Gerar Relatório"><i class="fa fa-file-pdf-o"></i></button>
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
			$id = $res[$i]['id'];
			$nome = $res[$i]['nome'];

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

<input type="hidden" id="ids">

<script src="paginas/js/<?php echo $pag; ?>/materiais.js" defer></script>



<div class="modal fade" id="modalForm" tabindex="-1" aria-labelledby="exampleModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header bg-primary text-white">
                <h4 class="modal-title" id="exampleModalLabel"><span id="titulo_inserir">Gerenciar Estoque</span></h4>
                <button id="btn-fechar" aria-label="Close" class="btn-close" data-bs-dismiss="modal" type="button">
                    <span class="text-white" aria-hidden="true">&times;</span>
                </button>
            </div>
           <form id="form_itens">
				<div class="container-superior">
				<div id="linha-template_1" class="linha_1" style="display: none;">
					<!-- Bloco Superior (2x2) -->

					<!-- Bloco Inferior (em linha) -->
					<div class="linha-inferior">
						<div class="coluna_romaneio">
							<label for="data">Data</label>
							<input type="date" class="data" name="data" value="<?= date('Y-m-d'); ?>">
						</div>
						<div class="coluna_romaneio">
							<label for="fornecedor">Fornecedor</label>
							<select name="fornecedor[]" class="fornecedor" onchange="handleInput(this); calcularValores(this.closest('.linha_1'));">
								<option value="">Selecione Fornecedor</option>
								<?php
								$query = $pdo->query("SELECT * from fornecedores order by id asc");
								$res = $query->fetchAll(PDO::FETCH_ASSOC);
								$linhas = @count($res);
								if ($linhas > 0) {
									for ($i = 0; $i < $linhas; $i++) { ?>
										<option value="<?php echo $res[$i]['id'] ?>"><?php echo $res[$i]['nome_atacadista'] ?></option>
								<?php }
								} ?>
							</select>
						</div>
							<div class="coluna_romaneio">
							<label for="compra">Compra</label>
							<input type="number" class="compra" id="compra" name="compra[]" onkeyup="handleInput(this); calcularValores(this.closest('.linha_1'));">
						</div>
						<div class="coluna_romaneio">
							<label for="venda">Venda</label>
							<input type="number" class="venda" id="venda" name="venda[]" onkeyup="handleInput(this); calcularValores(this.closest('.linha_1'));">
						</div>
						<div class="coluna_romaneio">
							<label for="preco">Preço</label>
							<input type="text" class="preco" id="preco" name="preco[]" onkeyup="mascara_decimal('preco'); handleInput(this); calcularValores(this.closest('.linha_1'));">
						</div>
						<div class="coluna_romaneio">
							<label for="valor_compra">Valor- R$ compra</label>
							<input type="text" class="valor_compra" name="valor_compra[]" readonly>
						</div>
						<div class="coluna_romaneio">
							<label for="valor_venda">Valor- R$ Venda</label>
							<input type="text" class="valor_venda" name="valor_venda3[]" readonly>
						</div>
						<div class="coluna_romaneio">
							<label for="tipo_cx_1">TIPO CX</label>
							<select name="tipo_cx_1[]" class="tipo_cx_1" onchange="handleInput(this); calcularValores(this.closest('.linha_1'));">
								<option value="">Selecione</option>
								<?php
								$query = $pdo->query("SELECT * from tipo_caixa order by id asc");
								$res = $query->fetchAll(PDO::FETCH_ASSOC);
								$linhas = @count($res);
								if ($linhas > 0) {
									for ($i = 0; $i < $linhas; $i++) { ?>
										<option value="<?php echo $res[$i]['tipo'] ?>"><?php echo $res[$i]['tipo'] ?></option>
								<?php }
								} ?>
							</select>
						</div>
						<div class="coluna_romaneio">
							<label for="preco_unit_1">PREÇO UNIT.</label>
							<input type="text" class="preco_unit_1" name="preco_unit_1[]" readonly>
						</div>
						<div class="coluna_romaneio">
							<label for="valor_1">Valor</label>
							<input type="text" class="valor_1" name="valor_1[]" readonly>
						</div>
					</div>

				</div>

				<!-- Contêiner para as linhas -->
				<div id="linha-container_1"></div>
				</div>
			
		<div class="resumo-linha radio">
                <div class="modal-footer">
                    <button type="submit" id="btn_salvar" class="btn btn-primary">Salvar</button>
                </div>
            </form>
        </div>
    </div>
</div>


<style>
	.radio {
		display: flex !important;
		align-items: center;
		justify-content: center;
		padding: 10px;
		gap: 15px;
	}

	.radio-group {
		display: flex;
		justify-content: space-between;
		margin-bottom: 15px;

	}

	.radio-group label {
		font-size: 14px;
		display: flex;
		align-items: center;
		cursor: pointer;
		gap: 8px;
	}

	input[type="radio"] {
		accent-color: #007bff;
		width: 18px;
		height: 18px;
	}

	input[type="text"],
	input[type="number"] {
		width: 100%;
		padding: 10px;
		margin-bottom: 10px;
		border: 1px solid #ccc;
		border-radius: 5px;
		font-size: 14px;
		transition: border-color 0.3s;
	}

	input[type="text"]:focus,
	input[type="number"]:focus {
		border-color: #007bff;
		outline: none;
		box-shadow: 0 0 5px rgba(0, 123, 255, 0.5);
	}

	.final .resumo-celula {
		background-color: rgb(102, 160, 64) !important;
	}

	.danger {
		color: red !important;
	}

	/* Contêiner principal */
	.linha_1,
	.linha_2,
	.linha_3 {
		display: flex;
		flex-direction: column;
		/* Organiza os blocos verticalmente */
		gap: 20px;
		/* Espaço entre os blocos */
		padding: 10px;
		background-color: #f9f9f9;
		border: 1px solid #e0e0e0;
		border-radius: 8px;
		margin-bottom: 10px;
		margin-left: 10px;
		margin-right: 10px;
		margin-top: 10px;
	}

	.container-superior {
		display: flex;
		align-items: center;
		justify-content: center;
		margin-top: 15px;
		margin-bottom: 15px;
	}

	/* Bloco Superior (2x2) */
	.linha-superior {
		display: grid;
		grid-template-columns: repeat(2, 1fr);
		/* Duas colunas */
		gap: 15px;
		width: 50%;
	}

	/* Bloco Inferior (em linha) */
	.linha-inferior {
		display: grid;
		grid-template-columns: repeat(6, 1fr);
		/* Seis colunas */
		gap: 15px;
		margin: auto;
	}

	/* Estilo dos rótulos */
	.coluna_romaneio label {
		font-size: 12px;
		font-weight: bold;
		color: #6c757d;
		margin-bottom: 5px;
		display: block;
	}

	/* Estilo dos inputs e selects */
	.coluna_romaneio input,
	.coluna_romaneio select {
		width: 100%;
		padding: 8px;
		border: 1px solid #ced4da;
		border-radius: 5px;
		font-size: 14px;
		color: #495057;
		background-color: #ffffff;
		box-shadow: inset 0 1px 2px rgba(0, 0, 0, 0.075);
		transition: border-color 0.3s ease, box-shadow 0.3s ease;
	}

	/* Efeito de foco nos inputs */
	.coluna_romaneio input:focus,
	.coluna_romaneio select:focus {
		border-color: #007bff;
		box-shadow: 0 0 5px rgba(0, 123, 255, 0.5);
		outline: none;
	}

	/* Estilo do contêiner principal */
	.resumo-tabela {
		display: table;
		width: 100%;
		border-collapse: collapse;
		background-color: #f8f9fa;
		/* Fundo semelhante a Excel */
		border: 1px solid black;
		/* Borda ao redor da tabela */
		box-shadow: 0 0 10px rgba(0, 0, 0, 0.1);
	}

	/* Estilo das linhas */
	.resumo-linha {
		display: table-row;
	}

	/* Estilo das células */
	.resumo-celula {
		display: table-cell;
		padding: 10px;
		border: 1px solid #dee2e6;
		/* Linhas semelhantes a Excel */
		font-size: 14px;
		text-align: left;
		font-weight: bold;
		vertical-align: middle;
		background-color: #c5e0b3;
		/* Fundo branco para células */
		color: #212529;
		/* Texto em cinza escuro */
	}

	.input {
		display: flex;
		justify-content: space-between;
		align-items: center;
	}

	.input label {

		font-size: 14px;
		font-weight: bold;
	}


	/* Estilo das células de entrada */
	.resumo-celula input {
		width: 20%;
		padding: 5px;
		border: 1px solid #ced4da;
		border-radius: 4px;
		font-size: 14px;
		color: #495057;
		background-color: yellow;
	}

	/* Estilo de foco nos inputs */
	.resumo-celula input:focus {
		border-color: #007bff;
		box-shadow: 0 0 5px rgba(0, 123, 255, 0.5);
		outline: none;
	}
</style>


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

								<input type="text" class="form-control" id="quantidade_saida" name="quantidade_saida" placeholder="Quantidade Saída" required onkeyup="mascara_decimal('quantidade_saida')">
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
				<small>
					<div id="mensagem-saida" align="center"></div>
				</small>
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

								<input type="text" class="form-control" id="quantidade_entrada" name="quantidade_entrada" placeholder="Quantidade Entrada" required onkeyup="mascara_decimal('quantidade_entrada')">
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
				<small>
					<div id="mensagem-entrada" align="center"></div>
				</small>
			</div>


		</div>
	</div>
</div>




<script type="text/javascript">
	var pag = "<?= $pag ?>"
</script>
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



<script type="text/javascript">
	$("#form_itens").submit(function() {

		event.preventDefault();
		var formData = new FormData(this);

		$('#mensagem').text('Salvando...')
		$('#btn_salvar').hide();

		$.ajax({
			url: 'paginas/' + pag + "/salvar.php",
			type: 'POST',
			data: formData,

			success: function(mensagem) {
				$('#mensagem').text('');
				$('#mensagem').removeClass()
				if (mensagem.trim() == "Salvo com Sucesso") {

					$('#btn-fechar').click();
					var id = $('#cat').val()
					listar(id)

					$('#mensagem').text('')

				} else {

					$('#mensagem').addClass('text-danger')
					$('#mensagem').text(mensagem)
				}

				$('#btn_salvar').show();

			},

			cache: false,
			contentType: false,
			processData: false,

		});

	});
</script>




<script type="text/javascript">
	$("#form-saida").submit(function() {

		event.preventDefault();
		var formData = new FormData(this);

		$.ajax({
			url: 'paginas/' + pag + "/saida.php",
			type: 'POST',
			data: formData,

			success: function(mensagem) {
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
	$("#form-entrada").submit(function() {

		event.preventDefault();
		var formData = new FormData(this);

		$.ajax({
			url: 'paginas/' + pag + "/entrada.php",
			type: 'POST',
			data: formData,

			success: function(mensagem) {
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