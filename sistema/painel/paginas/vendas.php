<?php
$pag = 'vendas';

//verificar se ele tem a permissão de estar nessa página
if (@$vendas == 'ocultar') {
	echo "<script>window.location='index.php'</script>";
	exit();
}

// Lógica PHP para verificar modo de edição (sem alterações)
$id_venda_edicao = 0;
$cliente_edicao = '';
$desconto_edicao = '';
$tipo_desconto_edicao = 'reais';
$frete_edicao = '';
$valor_pago_edicao = '';
$forma_pgto_edicao = '';
$data_edicao = date('Y-m-d');

if (@$_SESSION['modo_edicao_venda'] === true && isset($_SESSION['dados_edicao_venda'])) {
	$dados = $_SESSION['dados_edicao_venda'];

	$id_venda_edicao = $dados['id'];
	$cliente_edicao = $dados['cliente_id'];
	$desconto_edicao = $dados['desconto'];
	$tipo_desconto_edicao = $dados['tipo_desconto'] == '%' ? '%' : 'reais';
	$frete_edicao = $dados['frete'];
	$valor_pago_edicao = $dados['valor_pago'];
	$forma_pgto_edicao = $dados['forma_pagamento_id'];
	$data_edicao = date('Y-m-d', strtotime($dados['data_venda']));

	unset($_SESSION['modo_edicao_venda']);
	unset($_SESSION['dados_edicao_venda']);
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
		margin-top: 15px;
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
		margin-bottom: 10px;
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
			align-items: flex-start;
		}

		.pdv-sidebar {
			order: 2;
			flex: 0 0 24%;
			position: sticky;
			top: 15px;
			margin-top: 15px;
			/* <<<<<<< ADICIONE ESTA LINHA AQUI TAMBÉM <<<<<<< */
		}

		.pdv-produtos-grid {
			order: 1;
			flex: 1;
			margin-top: 15px;
			/* Margem que já tínhamos adicionado */
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

		<div id="listar_vendas" style="margin-top: 5px"></div>

		<form id="form_venda">
			<input type="hidden" name="id_venda_edicao" id="id_venda_edicao" value="<?php echo $id_venda_edicao; ?>">

			<div class="row" style="margin-top: 10px">
				<div class="col-md-7 col-7">
					<div class="form-group">
						<select class="form-select" name="saida" id="saida" required>
							<option value="">Forma Pgto</option>
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
						<input type="text" class="form-control" id="valor_pago" name="valor_pago" placeholder="Valor Pago" onkeyup="FormaPg()" value="<?php echo $valor_pago_edicao; ?>">
					</div>
				</div>
			</div>

			<div class="row mt-2">
				<div class="col-md-7 col-7">
					<label class="form-label small">Desconto <a id="desc_reais" class="desconto_link_ativo" href="#" onclick="tipoDesc('reais')">R$</a> / <a id="desc_p" class="desconto_link_inativo" href="#" onclick="tipoDesc('%')">%</a></label>
					<input type="number" class="form-control" id="desconto" name="desconto" placeholder="R$" onkeyup="listarVendas()" value="<?php echo $desconto_edicao; ?>">
				</div>
				<div class="col-md-5 col-5">
					<label class="form-label small">Troco Para</label>
					<input type="number" class="form-control" id="troco" name="troco" placeholder="R$" onkeyup="listarVendas()">
				</div>
			</div>

			<div class="row mt-2">
				<div class="col-md-7 col-7">
					<label class="form-label small">Data Pgto</label>
					<input type="date" class="form-control" id="data2" name="data2" value="<?php echo $data_edicao; ?>">
				</div>
				<div class="col-md-5 col-5">
					<label class="form-label small">Frete</label>
					<input type="text" class="form-control" id="frete" name="frete" placeholder="R$" onkeyup="listarVendas()" value="<?php echo $frete_edicao; ?>">
				</div>
			</div>

			<div id="div_pgto2" class="mt-2">
			</div>

			<div class="d-grid gap-2 mt-3">
				<button id="btn_venda" type="submit" class="btn btn-success">
					<?php echo ($id_venda_edicao > 0) ? 'Salvar Edição' : 'Fechar Venda'; ?>
				</button>
				<button id="btn_limpar" onclick="limparVenda()" type="button" class="btn btn-secondary">Limpar Venda</button>
				<div class="text-center">
					<img id="img_loading" src="../img/loading.gif" width="40px" style="display:none">
				</div>
			</div>

			<div id="mensagem" class="text-center mt-2 small"></div>
			<input type="hidden" name="cliente" id="cliente_input">
			<input type="hidden" name="tipo_desconto" id="tipo_desconto" value="reais">
			<input type="hidden" name="subtotal_venda" id="subtotal_venda">
			<input type="hidden" name="ids_itens" id="ids_itens">
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
				<a href="#" class="produto-item" onclick="addVenda(<?php echo $id_prod; ?>, '<?php echo addslashes($nome_prod); ?>')">
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
				<h4 class="modal-title" id="exampleModalLabel">Adicionar Cliente</h4>
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
						<a onclick="addVenda('', '', '', '')" href="#" class="btn btn-primary">Adicionar</a>
					</div>

					<input type="hidden" id="id_do_p">


				</div>


			</div>

		</div>
	</div>
</div>




<script type="text/javascript">
	function trocarCliente() {
		$('#cliente_input').val($('#cliente').val());
	}
	var pag = "<?= $pag ?>"
	// Adicione este bloco para sincronizar o select com o input hidden
	$('#listar_clientes').on('change', '#cliente', function() {
		// Pega o valor (ID do cliente) do <select> que acabou de ser alterado
		var clienteIdSelecionado = $(this).val();

		// Atualiza o valor do campo oculto
		$('#cliente_input').val(clienteIdSelecionado);

		// DEBUG: Linha adicionada para vermos a mágica acontecer
		console.log('%c[DEBUG PASSO 1] O <select> mudou! ID selecionado: ' + clienteIdSelecionado, 'color: green; font-weight: bold;');
		console.log('%c[DEBUG PASSO 1] Valor do campo oculto #cliente_input agora é: "' + $('#cliente_input').val() + '"', 'color: blue;');
	});
</script>
<script src="js/ajax.js"></script>

<script type="text/javascript">
	$(document).ready(function() {
		var id_venda_edicao = '<?php echo $id_venda_edicao; ?>';
		var cliente_id_edicao = '<?php echo $cliente_edicao; ?>'; // Esta é a variável importante

		// ========================================================== //
		// ===== ADICIONE ESTA LINHA PARA O DIAGNÓSTICO 1 ===== //
		// ========================================================== //
		console.log('%c[DIAGNÓSTICO 1] Página carregada. O ID do cliente para edição é: "' + cliente_id_edicao + '"', 'background: #222; color: #bada55; font-size: 14px;');
		// ========================================================== //

		var forma_pgto_edicao = '<?php echo $forma_pgto_edicao; ?>';
		// ----- CONFIGURAÇÃO INICIAL -----
		$('#div_pgto2').hide();

		$('.sel2').select2({
			dropdownParent: $('#modalForm')
		});

		$(document).on('select2:open', () => {
			document.querySelector('.select2-search__field').focus();
		});

		// ----- LÓGICA DE EDIÇÃO vs NOVA VENDA -----
		var id_venda_edicao = '<?php echo $id_venda_edicao; ?>';
		var cliente_id_edicao = '<?php echo $cliente_edicao; ?>';
		var forma_pgto_edicao = '<?php echo $forma_pgto_edicao; ?>';
		var tipo_desconto_edicao = '<?php echo $tipo_desconto_edicao; ?>';

		if (id_venda_edicao > 0) {
			// ============ MODO EDIÇÃO ============

			// 1. Preenche os selects e define o tipo de desconto
			if (forma_pgto_edicao !== '') {
				$('#saida').val(forma_pgto_edicao);
			}
			if (tipo_desconto_edicao === '%') {
				tipoDesc('%');
			} else {
				tipoDesc('reais');
			}

			// 2. Carrega a lista de clientes JÁ com o cliente correto selecionado
			listarClientes(cliente_id_edicao);

			// 3. Carrega os itens da venda e recalcula os totais
			listarVendas();

			// 4. Exibe a notificação para o usuário
			Swal.fire({
				title: 'Modo de Edição',
				text: 'Venda #' + id_venda_edicao + ' carregada. Faça suas alterações e clique em "Salvar Edição".',
				icon: 'info',
				timer: 4000,
				showConfirmButton: true
			});

		} else {
			// ============ MODO NOVA VENDA (comportamento padrão) ============

			// Carrega a lista de clientes vazia e a lista de vendas (que estará vazia)
			listarClientes();
			listarVendas();
		}
	});
</script>

<script type="text/javascript">
	$("#form_venda").submit(function(event) {
		// Previne o comportamento padrão do formulário
		event.preventDefault();

		// ========================================================== //
		// ===== NOVA VALIDAÇÃO DE ITENS OBRIGATÓRIOS ADICIONADA ==== //
		// ========================================================== //
		// A variável global 'itens' é sempre atualizada pela função listarVendas()
		if (itens == 0) {
			alert('É obrigatório adicionar pelo menos um item para fechar a venda!');

			// Exibe uma mensagem de erro na interface para o usuário
			$('#mensagem').text('Adicione pelo menos um item para continuar.').addClass('text-danger');

			// PARA a execução do script aqui, impedindo o envio do formulário.
			return;
		}
		// ========================================================== //
		// ==================== FIM DA VALIDAÇÃO ==================== //
		// ========================================================== //

		var valorFinalCliente = $('#cliente_input').val();
		console.log('%c[DIAGNÓSTICO 3] Formulário enviado! O valor final no campo oculto #cliente_input é: "' + valorFinalCliente + '"', 'background: #dc3545; color: #fff; font-size: 16px;');

		// Oculta os botões e exibe o indicador de carregamento
		$("#btn_venda").hide();
		$("#btn_limpar").hide();
		$("#img_loading").show();
		// Limpa a mensagem de erro, caso houvesse uma da validação anterior
		$('#mensagem').text('').removeClass('text-danger');

		// Verifica se a data e o cliente são válidos
		var data = $("#data2").val();
		var cliente = $("#cliente").val();
		var data_atual = "<?= $data_atual ?>";

		if (data > data_atual && cliente == "") {
			alert('Você precisa selecionar um cliente para essa venda!');
			$("#img_loading").hide();
			$("#btn_venda").show();
			$("#btn_limpar").show(); // Adicionado para consistência
			return;
		}

		// Cria um objeto FormData com os dados do formulário
		var formData = new FormData(this);

		// Envia a requisição AJAX
		$.ajax({
			url: 'paginas/' + pag + "/salvar.php",
			type: 'POST',
			data: formData,
			cache: false,
			contentType: false,
			processData: false,

			success: function(mensagem) {
				// Processa a resposta do servidor
				var msg = mensagem.split("-");

				$('#mensagem').text('');
				$('#mensagem').removeClass();

				if (msg[0].trim() == "Salvo com Sucesso") {
					$("#img_loading").hide();

					// Limpa os campos do formulário
					$('#desconto').val('');
					$('#troco').val('');
					$('#cliente').val('').change();
					$('#cliente_input').val('');
					$('#data').val('<?= $data_atual ?>');

					// Atualiza as listas de vendas e produtos
					listar();
					listarVendas();

					// Verifica se a impressão automática está habilitada
					var imp_auto = "<?= $impressao_automatica ?>";
					if (imp_auto == 'Sim') {
						window.open('rel/comprovante.php?id=' + msg[1]);
					} else {
						alert('Venda Efetuada!');
						$('#div_pgto2').hide();
					}
				} else {
					// Exibe mensagem de erro
					alert(msg[0]);
					$("#btn_venda").show();
					$("#img_loading").hide();
					$("#btn_limpar").show();
				}

				// Restaura os botões (redundante, mas garante que apareçam)
				$("#btn_venda").show();
				$("#btn_limpar").show();
			},

			error: function(xhr, status, error) {
				// Trata erros de requisição AJAX
				$("#img_loading").hide();
				$("#btn_venda").show();
				$("#btn_limpar").show();

				// Exibe detalhes do erro no console
				console.error("Erro na requisição AJAX:");
				console.error("Status: " + status);
				console.error("Erro: " + error);
				console.error("Resposta do servidor: " + xhr.responseText);

				// Exibe uma mensagem de erro para o usuário
				alert("Ocorreu um erro ao processar a requisição. Por favor, tente novamente.\nDetalhes: " + error);
			}
		});
	});

	function buscar() {
		var busca = $('#txt_buscar').val();
		listar('', busca)
	}

	function addVenda(id_material, produto) {

		console.log("Clicado");

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
					listarVendas();

				} else {
					alert(mensagem)
				}
			}
		});



	}

	function listarVendas() {
		var desconto = $("#desconto").val();
		var frete = $("#frete").val();
		var troco = $("#troco").val();
		var tipo_desconto = $("#tipo_desconto").val();
		$.ajax({
			url: 'paginas/' + pag + "/listar_vendas.php",
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

	function limparVenda() {
		$("#cliente").val('').change();
		$("#quantidade").val('1');
		$("#desconto").val('');
		$("#troco").val('');
		$("#frete").val('');
		$("#data").val('<?= $data_atual ?>');
		$("#cliente_input").val('');
		$('#div_pgto2').hide();
		listarVendas()

		$("#btn_limpar").hide();
		$.ajax({
			url: 'paginas/' + pag + "/limpar_venda.php",
			method: 'POST',
			data: {},
			dataType: "html",

			success: function(result) {
				listarVendas();
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
		// A variável 'pag' deve estar definida globalmente no seu script
		var pag = "<?= $pag ?>";

		console.log('[DEBUG] Chamando listarClientes(). ID para pré-selecionar: "' + valor + '"');

		$.ajax({
			url: 'paginas/' + pag + "/listar_clientes.php",
			method: 'POST',
			data: {
				valor: valor
			}, // Passa o ID do cliente para ser marcado como 'selected' no PHP
			dataType: "html",
			success: function(result) {
				// 1. Injete o HTML do <select> na div
				$("#listar_clientes").html(result);

				// 2. Inicialize o Select2 no novo <select> que acabamos de criar
				$('#cliente').select2();

				// 3. LEIA o valor que está de fato selecionado no <select> após a carga
				var clienteSelecionado = $('#cliente').val();

				// 4. ATUALIZE O CAMPO OCULTO IMEDIATAMENTE!
				// Esta é a correção definitiva.
				$('#cliente_input').val(clienteSelecionado);

				// Log de confirmação final
				console.log('%c[PÓS-AJAX] O campo oculto #cliente_input foi FORÇADO para o valor: "' + $('#cliente_input').val() + '"', 'color: purple; font-weight: bold;');
			},
			error: function() {
				console.error("Falha ao carregar a lista de clientes via AJAX.");
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
		listarVendas();
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