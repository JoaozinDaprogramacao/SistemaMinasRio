<?php
$pag = 'compras';

//verificar se ele tem a permissão de estar nessa página
if (@$compras == 'ocultar') {
	echo "<script>window.location='index.php'</script>";
	exit();
}
?>

<style>
	/* --- ESTILOS PADRÃO (MOBILE) --- */
	.pdv-container {
		display: flex;
		flex-direction: column;
		/* Empilha os blocos verticalmente no mobile */
		gap: 20px;
	}

	.pdv-sidebar {
		order: 1;
		/* Sidebar (carrinho) vem PRIMEIRO no mobile */
		width: 100%;
		background: #fef5ed;
		padding: 15px;
		border-radius: 8px;
		border: 1px solid #eee;
	}

	.pdv-sidebar .form-group,
	.pdv-sidebar .form-label {
		margin-bottom: 8px;
	}

	.pdv-produtos-grid {
		order: 2;
		/* Grid de produtos vem DEPOIS no mobile */
		width: 100%;
		display: flex;
		flex-wrap: wrap;
		/* Permite que os itens quebrem a linha */
		gap: 10px;
		/* Espaçamento entre os produtos */
	}

	.produto-item {
		/* 2 colunas no mobile: 50% da largura menos metade do espaçamento */
		flex-basis: calc(50% - 5px);
		text-decoration: none;
	}

	.produto-item .r3_counter_box {
		min-height: 70px;
		padding: 10px;
		display: flex;
		align-items: center;
		justify-content: center;
		text-align: center;
		border: 1px solid #ddd;
		border-radius: 5px;
		background: #fff;
		transition: all 0.2s ease-in-out;
		height: 100%;
		/* Garante que todos os cards tenham a mesma altura */
	}

	.produto-item:hover .r3_counter_box {
		border-color: #0d6efd;
		transform: translateY(-2px);
		box-shadow: 0 4px 8px rgba(0, 0, 0, 0.05);
	}

	.produto-item h5 {
		font-size: 13px;
		color: #333;
		margin: 0;
	}

	/* Links de desconto */
	.desconto_link_ativo {
		font-weight: bold;
		color: #0d6efd;
		text-decoration: none;
	}

	.desconto_link_inativo {
		color: #6c757d;
		text-decoration: underline;
	}

	/* --- ESTILOS PARA TELAS MAIORES (TABLET/DESKTOP) --- */
	@media (min-width: 992px) {
		.pdv-container {
			flex-direction: row;
			/* Layout lado a lado no desktop */
			align-items: flex-start;
			/* Alinha os containers no topo */
		}

		.pdv-sidebar {
			order: 2;
			/* Sidebar vai para a DIREITA no desktop */
			flex: 0 0 24%;
			/* Ocupa 24% da largura e não encolhe/estica */
			position: sticky;
			/* Efeito "grudento" ao rolar */
			top: 15px;
			margin-top: 15px;
			/* Espaçamento do topo para desktop */
		}

		.pdv-produtos-grid {
			order: 1;
			/* Grid de produtos vai para a ESQUERDA no desktop */
			flex: 1;
			/* Ocupa todo o espaço restante */
			margin-top: 15px;
			/* Espaçamento do topo para desktop */
		}

		.produto-item {
			/* 4 colunas no desktop */
			flex-basis: calc(25% - 8px);
		}
	}
</style>


<div class="pdv-container">

	<div class="pdv-sidebar">
		<div class="row">
			<div class="col-10" style="padding-right: 5px;">
				<div class="form-group">
					<div id="listar_clientes"></div>
				</div>
			</div>
			<div class="col-2" style="padding-left: 0;">
				<button type="button" class="btn btn-primary w-100" data-bs-toggle="modal" data-bs-target="#modalCliente"><i class="fa fa-plus"></i></button>
			</div>
		</div>

		<div id="listar_compras" style="margin-top: 5px"></div>

		<form id="form_compra">
			<div class="row" style="margin-top: 10px">
				<div class="col-md-7 col-7">
					<div class="form-group">
						<select class="form-select" name="saida" id="saida" required>
							<option value="">Forma de Pgto</option>
							<?php
							$query_pgto = $pdo->query("SELECT * FROM formas_pgto order by id asc");
							$res_pgto = $query_pgto->fetchAll(PDO::FETCH_ASSOC);
							foreach ($res_pgto as $pgto) {
								echo "<option value='{$pgto['id']}'>{$pgto['nome']}</option>";
							}
							?>
						</select>
					</div>
				</div>
				<div class="col-md-5 col-5">
					<div class="form-group">
						<input type="text" class="form-control" id="valor_pago" name="valor_pago" placeholder="Valor Pago" onkeyup="FormaPg()">
					</div>
				</div>
			</div>

			<div class="row mt-2">
				<div class="col-md-7 col-7">
					<label class="form-label small">Desconto <a id="desc_reais" class="desconto_link_ativo" href="#" onclick="tipoDesc('reais')">R$</a> / <a id="desc_p" class="desconto_link_inativo" href="#" onclick="tipoDesc('%')">%</a></label>
					<input type="number" class="form-control" id="desconto" name="desconto" placeholder="R$" onkeyup="listarCompras()">
				</div>
				<div class="col-md-5 col-5">
					<label class="form-label small">Troco Para</label>
					<input type="number" class="form-control" id="troco" name="troco" placeholder="R$" onkeyup="listarCompras()">
				</div>
			</div>

			<div class="row mt-2">
				<div class="col-md-7 col-7">
					<label class="form-label small">Data Pgto</label>
					<input type="date" class="form-control" id="data2" name="data2" value="<?php echo date('Y-m-d'); ?>">
				</div>
				<div class="col-md-5 col-5">
					<label class="form-label small">Frete</label>
					<input type="text" class="form-control" id="frete" name="frete" placeholder="R$" onkeyup="listarCompras()">
				</div>
			</div>

			<div id="div_pgto2" class="mt-2">
			</div>

			<div class="d-grid gap-2 mt-3">
				<button id="btn_compra" type="submit" class="btn btn-success">Fechar Compra</button>
				<button id="btn_limpar" onclick="limparCompra()" type="button" class="btn btn-secondary">Limpar Compra</button>
				<div class="text-center">
					<img id="img_loading" src="../img/loading.gif" width="40px" style="display:none">
				</div>
			</div>

			<div id="mensagem" class="text-center mt-2 small"></div>
			<input type="hidden" name="cliente" id="cliente_input">
			<input type="hidden" name="tipo_desconto" id="tipo_desconto" value="reais">
			<input type="hidden" name="subtotal_compra" id="subtotal_compra"> <input type="hidden" name="ids_itens" id="ids_itens">
			<input type="hidden" name="valor_restante" id="valor_restante">
		</form>
	</div>

	<div class="pdv-produtos-grid">
		<?php
		$query_mat = $pdo->query("SELECT * from materiais order by nome asc");
		$res_mat = $query_mat->fetchAll(PDO::FETCH_ASSOC);
		if (count($res_mat) > 0) {
			foreach ($res_mat as $mat) {
				$id_prod = $mat['id'];
				$nome_prod = $mat['nome'];
		?>
				<a href="#" class="produto-item" onclick="addCompra(<?php echo $id_prod; ?>, '<?php echo addslashes($nome_prod); ?>')">
					<div class="r3_counter_box">
						<div class="stats">
							<h5><strong><?php echo $nome_prod ?></strong></h5>
						</div>
					</div>
				</a>
		<?php
			}
		} else {
			echo '<p class="text-muted w-100 text-center">Nenhum produto cadastrado.</p>';
		}
		?>
	</div>

</div>

<!-- Modal Cliente -->
<div class="modal fade" id="modalCliente" tabindex="-1" aria-labelledby="exampleModalLabel" aria-hidden="true">
	<div class="modal-dialog modal-lg">
		<div class="modal-content">
			<div class="modal-header bg-primary text-white">
				<h4 class="modal-title" id="exampleModalLabel">Adicionar Fornecedor</h4>
				<button id="btn-fechar-cliente" aria-label="Close" class="btn-close" data-bs-toggle="modal" data-bs-target="#modalForm" type="button"><span class="text-white" aria-hidden="true">&times;</span></button>
			</div>
			<form id="form-cliente">
				<div class="modal-body">


					<div class="row">
						<div class="col-md-6 mb-2 col-6">
							<label>Nome</label>
							<input type="text" class="form-control" id="nome" name="nome" placeholder="Seu Nome" required>
						</div>

						<div class="col-md-3 col-6">
							<label>Telefone</label>
							<input type="text" class="form-control" id="telefone" name="telefone" placeholder="Seu Telefone">
						</div>

						<div class="col-md-3 mb-2">
							<label>Nascimento</label>
							<input type="date" class="form-control" id="data_nasc" name="data_nasc" placeholder="">
						</div>


					</div>


					<div class="row">

						<div class="col-md-2 mb-2 col-6">
							<label>Pessoa</label>
							<select name="tipo_pessoa" id="tipo_pessoa" class="form-select" onchange="mudarPessoa()">
								<option value="Física">Física</option>
								<option value="Jurídica">Jurídica</option>
							</select>
						</div>

						<div class="col-md-3 mb-2 col-6">
							<label>CPF / CNPJ</label>
							<input type="text" class="form-control" id="cpf" name="cpf" placeholder="CPF/CNPJ">
						</div>


						<div class="col-md-3">
							<label>RG</label>
							<input type="text" class="form-control" id="rg" name="rg" placeholder="RG">
						</div>


						<div class="col-md-4">
							<label>Email</label>
							<input type="email" class="form-control" id="email" name="email" placeholder="Email">
						</div>


					</div>

					<div class="row">

						<div class="col-md-2 mb-2">
							<label>CEP</label>
							<input type="text" class="form-control" id="cep" name="cep" placeholder="CEP" onblur="pesquisacep(this.value);">
						</div>

						<div class="col-md-5 mb-2">
							<label>Rua</label>
							<input type="text" class="form-control" id="endereco" name="endereco" placeholder="Rua">
						</div>

						<div class="col-md-2 mb-2">
							<label>Número</label>
							<input type="text" class="form-control" id="numero" name="numero" placeholder="Número">
						</div>

						<div class="col-md-3 mb-2">
							<label>Complemento</label>
							<input type="text" class="form-control" id="complemento" name="complemento" placeholder="Se houver">
						</div>



					</div>


					<div class="row">

						<div class="col-md-4 mb-2">
							<label>Bairro</label>
							<input type="text" class="form-control" id="bairro" name="bairro" placeholder="Bairro">
						</div>

						<div class="col-md-5 mb-2">
							<label>Cidade</label>
							<input type="text" class="form-control" id="cidade" name="cidade" placeholder="Cidade">
						</div>

						<div class="col-md-3 mb-2">
							<label>Estado</label>
							<select class="form-select" id="estado" name="estado">
								<option value="">Selecionar</option>
								<option value="AC">Acre</option>
								<option value="AL">Alagoas</option>
								<option value="AP">Amapá</option>
								<option value="AM">Amazonas</option>
								<option value="BA">Bahia</option>
								<option value="CE">Ceará</option>
								<option value="DF">Distrito Federal</option>
								<option value="ES">Espírito Santo</option>
								<option value="GO">Goiás</option>
								<option value="MA">Maranhão</option>
								<option value="MT">Mato Grosso</option>
								<option value="MS">Mato Grosso do Sul</option>
								<option value="MG">Minas Gerais</option>
								<option value="PA">Pará</option>
								<option value="PB">Paraíba</option>
								<option value="PR">Paraná</option>
								<option value="PE">Pernambuco</option>
								<option value="PI">Piauí</option>
								<option value="RJ">Rio de Janeiro</option>
								<option value="RN">Rio Grande do Norte</option>
								<option value="RS">Rio Grande do Sul</option>
								<option value="RO">Rondônia</option>
								<option value="RR">Roraima</option>
								<option value="SC">Santa Catarina</option>
								<option value="SP">São Paulo</option>
								<option value="SE">Sergipe</option>
								<option value="TO">Tocantins</option>
								<option value="EX">Estrangeiro</option>
							</select>
						</div>


					</div>



					<div class="row">
						<div class="col-md-6 mb-2">
							<label>Genitor</label>
							<input type="text" class="form-control" id="genitor" name="genitor" placeholder="Nome do Pai">
						</div>

						<div class="col-md-6 mb-2">
							<label>Genitora</label>
							<input type="text" class="form-control" id="genitora" name="genitora" placeholder="Nome da mãe">
						</div>
					</div>



					<input type="hidden" class="form-control" id="id" name="id">

					<br>
					<small>
						<div id="mensagem_cliente" align="center"></div>
					</small>
				</div>
				<div class="modal-footer">
					<button type="submit" id="btn_salvar_cliente" class="btn btn-primary">Salvar</button>
				</div>
			</form>
		</div>
	</div>
</div>








<!-- Modal Quantidade -->
<div class="modal fade" id="modalQuantidade" tabindex="-1" aria-labelledby="exampleModalLabel" aria-hidden="true">
	<div class="modal-dialog ">
		<div class="modal-content">
			<div class="modal-header bg-primary text-white">
				<h4 class="modal-title" id="exampleModalLabel">Quantidade: <span id="nome_do_prod"></span></h4>
				<button id="btn-fechar-quant" aria-label="Close" class="btn-close" data-bs-toggle="modal" data-bs-target="#modalForm" type="button"><span class="text-white" aria-hidden="true">&times;</span></button>
			</div>

			<div class="modal-body">

				<div class="row">
					<div class="col-md-8 mb-2">
						<label>Quantidade em <span id="uni_do_prod"></span></label>
						<input type="text" class="form-control" id="quantidade_prod" placeholder="0.5" onkeyup="mascara_decimal('quantidade_prod')">
					</div>

					<div class="col-md-4" style="margin-top: 22px">
						<a onclick="addCompra('', '', '', '')" href="#" class="btn btn-primary">Adicionar</a>
					</div>

					<input type="hidden" id="id_do_p">


				</div>


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
		$('#div_pgto2').hide();
		listarCompras()

		$('.sel2').select2({
			dropdownParent: $('#modalForm')
		});

		$(document).on('select2:open', () => {
			document.querySelector('.select2-search__field').focus();
		});
		listarClientes();
	});
</script>

<script type="text/javascript">
	$("#form-compras").submit(function(event) {
		event.preventDefault();

		var $btn = $('#btn_compra');
		$btn.prop('disabled', true).html('<span class="spinner-border spinner-border-sm"></span> Salvando...');

		var formData = new FormData(this);

		$.ajax({
			url: 'paginas/compras/salvar.php',
			type: 'POST',
			data: formData,
			cache: false,
			contentType: false,
			processData: false,

			success: function(response) {
				try {
					if (typeof response === 'string') {
						response = JSON.parse(response);
					}

					if (response.status === 'success') {
						limparCompra();
						listarCompras();
						alert(response.mensagem);
					} else {
						alert(response.mensagem || 'Erro ao processar a compra');
					}
				} catch (e) {
					console.error("Erro ao processar resposta:", e);
					alert('Erro ao processar resposta do servidor');
				}
			},
			error: function(xhr, status, error) {
				console.error("Erro na requisição:", error);
				alert('Erro na requisição: ' + error);
			}
		}).always(function() {
			$btn.prop('disabled', false).html('Salvar');
		});
	});

	function buscar() {
		var busca = $('#txt_buscar').val();
		listar('', busca)
	}

	function addCompra(id_material, produto) {

		if (id_material == "") {
			var id_material = $('#id_do_p').val();
			var quantidade = $('#quantidade_prod').val();
			$('#btn-fechar-quant').click();
			$('#quantidade_prod').val('');
		} else {
			var quantidade = 1;
			$('#id_do_p').val(id_material);
		}

		$('#nome_do_prod').text(produto);


		if (quantidade <= 0) {
			alert('A quantidade deve ser maior que zero')
			return;
		}

		console.log(id_material);
		$.ajax({
			url: 'paginas/' + pag + "/inserir_item.php",
			method: 'POST',
			data: {
				quantidade,
				id_material
			},
			dataType: "html",

			success: function(mensagem) {
				if (mensagem.trim() == "Inserido com Sucesso") {
					listarCompras();

				} else {
					alert(mensagem)
				}
			}
		});



	}

	function listarCompras() {
		var desconto = $("#desconto").val();
		var frete = $("#frete").val();
		var troco = $("#troco").val();
		var tipo_desconto = $("#tipo_desconto").val();
		$.ajax({
			url: 'paginas/' + pag + "/listar_compras.php",
			method: 'POST',
			data: {
				desconto,
				troco,
				tipo_desconto,
				frete
			},
			dataType: "html",

			success: function(result) {
				$("#listar_vendas").html(result);
			}
		});

		FormaPg()
	}

	function limparCompra() {
		$("#fornecedor").val('').change();
		$("#quantidade").val('1');
		$("#desconto").val('');
		$("#troco").val('');
		$("#frete").val('');
		$("#data").val('<?= $data_atual ?>');
		$("#valor_pago").val('');
		$("#valor_restante").val('');
		$("#subtotal_compra").val('0.00');
		$('#div_pgto2').hide();

		$("#btn_limpar").hide();

		$.ajax({
			url: 'paginas/' + pag + "/limpar_compra.php",
			method: 'POST',
			data: {},
			dataType: "html",
			success: function(result) {
				listarCompras();
				FormaPg();
			}
		});
	}
</script>

<script type="text/javascript">
	$("#form-cliente").submit(function() {

		$('#mensagem_cliente').text('Salvando!!');
		$('#btn_salvar_cliente').hide();

		event.preventDefault();
		var formData = new FormData(this);
		var nova_pag = 'clientes';

		$.ajax({
			url: 'paginas/' + nova_pag + "/salvar.php",
			type: 'POST',
			data: formData,

			success: function(mensagem) {
				$('#mensagem_cliente').text('');
				$('#mensagem_cliente').removeClass()
				if (mensagem.trim() == "Salvo com Sucesso") {

					$('#btn-fechar-cliente').click();
					listar();
					listarClientes('1');


				} else {

					$('#mensagem_cliente').addClass('text-danger')
					$('#mensagem_cliente').text(mensagem)
				}

				$('#btn_salvar_cliente').show();

			},

			cache: false,
			contentType: false,
			processData: false,

		});

	});


	function listarClientes(valor) {
		$.ajax({
			url: 'paginas/' + pag + "/listar_clientes.php",
			method: 'POST',
			data: {
				valor
			},
			dataType: "html",

			success: function(result) {
				$("#listar_clientes").html(result);
			}
		});
	}


	function tipoDesc(p) {
		$('#desc_reais').removeClass()
		$('#desc_p').removeClass()

		if (p == '%') {
			$('#desconto').attr('placeholder', '%');
			$('#desc_reais').addClass('desconto_link_inativo')
			$('#desc_p').addClass('desconto_link_ativo')
		} else {
			$('#desconto').attr('placeholder', 'R$');
			$('#desc_reais').addClass('desconto_link_ativo')
			$('#desc_p').addClass('desconto_link_inativo')
		}

		$("#tipo_desconto").val(p);
		listarCompras();
	}



	function FormaPg() {
		var valor_pago = $('#valor_pago').val();
		var subtotal_venda = $('#subtotal_venda').val();

		console.log("Valor pago: " + valor_pago);
		console.log("Subtotal: " + subtotal_venda);

		if (parseFloat(valor_pago) < parseFloat(subtotal_venda)) {
			$('#div_pgto2').show();
		} else {
			$('#div_pgto2').hide();
		}

		if (valor_pago == "") {
			valor_pago = 0;
		}

		if (subtotal_venda == "") {
			subtotal_venda = 0;
		}

		var total_restante = parseFloat(subtotal_venda) - parseFloat(valor_pago);
		$('#total_restante').text(total_restante.toFixed(2));
		$('#valor_restante').val(total_restante);
	}
</script>






<script>
	function limpa_formulário_cep() {
		//Limpa valores do formulário de cep.
		document.getElementById('endereco').value = ("");
		document.getElementById('bairro').value = ("");
		document.getElementById('cidade').value = ("");
		document.getElementById('estado').value = ("");
		//document.getElementById('ibge').value=("");
	}

	function meu_callback(conteudo) {
		if (!("erro" in conteudo)) {
			//Atualiza os campos com os valores.
			document.getElementById('endereco').value = (conteudo.logradouro);
			document.getElementById('bairro').value = (conteudo.bairro);
			document.getElementById('cidade').value = (conteudo.localidade);
			document.getElementById('estado').value = (conteudo.uf);
			//document.getElementById('ibge').value=(conteudo.ibge);
		} //end if.
		else {
			//CEP não Encontrado.
			limpa_formulário_cep();
			alert("CEP não encontrado.");
		}
	}

	function pesquisacep(valor) {

		//Nova variável "cep" somente com dígitos.
		var cep = valor.replace(/\D/g, '');

		//Verifica se campo cep possui valor informado.
		if (cep != "") {

			//Expressão regular para validar o CEP.
			var validacep = /^[0-9]{8}$/;

			//Valida o formato do CEP.
			if (validacep.test(cep)) {

				//Preenche os campos com "..." enquanto consulta webservice.
				document.getElementById('endereco').value = "...";
				document.getElementById('bairro').value = "...";
				document.getElementById('cidade').value = "...";
				document.getElementById('estado').value = "...";
				//document.getElementById('ibge').value="...";

				//Cria um elemento javascript.
				var script = document.createElement('script');

				//Sincroniza com o callback.
				script.src = 'https://viacep.com.br/ws/' + cep + '/json/?callback=meu_callback';

				//Insere script no documento e carrega o conteúdo.
				document.body.appendChild(script);

			} //end if.
			else {
				//cep é inválido.
				limpa_formulário_cep();
				alert("Formato de CEP inválido.");
			}
		} //end if.
		else {
			//cep sem valor, limpa formulário.
			limpa_formulário_cep();
		}
	};
</script>