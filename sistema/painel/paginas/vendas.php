<?php
// Certifique-se de que a sessão está iniciada e a conexão com o banco de dados ($pdo) está estabelecida.
// Este código assume que 'conexao.php' e 'data_atual' estão definidos em um ponto anterior.
// require_once("conexao.php"); 
// $data_atual = date('Y-m-d'); 
// $impressao_automatica = 'Não'; // Exemplo de variável global
// $vendas = 'permitido'; // Exemplo de variável de permissão

$pag = 'vendas';

// Bloco de verificação de permissão (sem alterações)
if (@$vendas == 'ocultar') {
	echo "<script>window.location='index.php'</script>";
	exit();
}

// Garante que temos o ID do usuário logado para as queries
$id_usuario_logado = $_SESSION['id'];

error_log("[VENDAS] ETAPA 0 -> modo_edicao_venda=" . (isset($_SESSION['modo_edicao_venda']) ? '1' : '0') . " carrinho_em_modo_edicao=" . (isset($_SESSION['carrinho_em_modo_edicao']) ? '1' : '0') . " user={$id_usuario_logado}");


// ======================================================================= //
// ===== LÓGICA DE CONTROLE DE ESTADO DO CARRINHO (SOLUÇÃO FINAL) ======== //
// ======================================================================= //

// ETAPA 1 (DESATIVADA): não limpar automaticamente aqui! Isso apaga o carrinho copiado.
/*
if (!isset($_SESSION['modo_edicao_venda']) && isset($_SESSION['carrinho_em_modo_edicao'])) {
    $stmt_limpar = $pdo->prepare("DELETE FROM itens_venda WHERE id_venda = 0 AND funcionario = :id_funcionario");
    $stmt_limpar->execute([':id_funcionario' => $id_usuario_logado]);
    unset($_SESSION['carrinho_em_modo_edicao']);
}
*/


// ======================================================================= //
// ===== LÓGICA PARA INICIAR UMA NOVA EDIÇÃO ============================== //
// ======================================================================= //

// Inicialização das variáveis padrão para o formulário
$id_venda_edicao = 0;
$cliente_edicao = '';
$desconto_edicao = '';
$tipo_desconto_edicao = 'reais';
$frete_edicao = '';
$valor_pago_edicao = '';
$forma_pgto_edicao = '';
$data_edicao = date('Y-m-d');

// ETAPA 2: PREPARA O AMBIENTE QUANDO O USUÁRIO CLICA EM "EDITAR"
if (isset($_SESSION['modo_edicao_venda']) && @$_SESSION['modo_edicao_venda'] === true) {

	$dados = $_SESSION['dados_edicao_venda'];
	$id_venda_para_editar = $dados['id'];

	try {
		$pdo->beginTransaction();

		// Log para diagnóstico (O que vamos editar?)
		error_log("[VENDAS] ETAPA 2 - INÍCIO. id_venda_para_editar: " . $id_venda_para_editar);

		// Limpa qualquer carrinho existente deste usuário
		$stmt_limpar = $pdo->prepare("DELETE FROM itens_venda WHERE id_venda = 0 AND funcionario = :id_funcionario");
		$stmt_limpar->execute([':id_funcionario' => $id_usuario_logado]);

		// Descobre o 'codigo' do carrinho atual
		$stmt_cod = $pdo->prepare("SELECT codigo FROM itens_venda WHERE id_venda = 0 AND funcionario = :id_funcionario LIMIT 1");
		$stmt_cod->execute([':id_funcionario' => $id_usuario_logado]);
		$codigo_carrinho = $stmt_cod->fetchColumn();

		if (!$codigo_carrinho) {
			$codigo_carrinho = session_id(); // <- troque por $_SESSION['codigo_caixa'] se for o seu padrão
		}

		// CÓPIA dos itens da venda para o carrinho temporário do usuário
		$stmt_copiar = $pdo->prepare(
			"INSERT INTO itens_venda (material, valor, quantidade, total, funcionario, id_venda, codigo)
     SELECT iv.material, iv.valor, iv.quantidade, iv.total, :id_funcionario, 0, :codigo_carrinho
     FROM itens_venda AS iv
     WHERE iv.id_venda_real = :id_venda_origem" /* <-- MUDANÇA CRÍTICA AQUI */
		);
		$stmt_copiar->execute([
			':id_funcionario'   => $id_usuario_logado,
			':codigo_carrinho'  => $codigo_carrinho,
			':id_venda_origem'  => $id_venda_para_editar
		]);

		// ADICIONADO LOG PARA VERIFICAR A COPIA
		$itens_copiados = $stmt_copiar->rowCount(); // Pega o número de linhas afetadas
		error_log("[VENDAS][EDIT] Tentativa de cópia da Venda ID #{$id_venda_para_editar} para Carrinho. Itens copiados: {$itens_copiados}.");

		$dbg = $pdo->prepare("SELECT COUNT(*) FROM itens_venda WHERE id_venda = 0 AND funcionario = :f");
		$dbg->execute([':f' => $id_usuario_logado]);
		error_log("[VENDAS][EDIT] Copiados para carrinho do user {$id_usuario_logado}: " . $dbg->fetchColumn());

		$pdo->commit();

		// "Etiquetamos" o carrinho
		$_SESSION['carrinho_em_modo_edicao'] = true;
		error_log("[VENDAS] ETAPA 2 - FIM. Carrinho etiquetado.");
	} catch (Exception $e) {
		$pdo->rollBack();
		die("ERRO CRÍTICO AO COPIAR ITENS PARA EDIÇÃO: <br>" . $e->getMessage());
	}

	// Popula variáveis do formulário
	$id_venda_edicao    = $dados['id'];
	$cliente_edicao     = $dados['cliente_id'];
	$desconto_edicao    = $dados['desconto'];
	$tipo_desconto_edicao = $dados['tipo_desconto'] == '%' ? '%' : 'reais';
	$frete_edicao       = $dados['frete'];
	$valor_pago_edicao  = $dados['valor_pago'];
	$forma_pgto_edicao  = $dados['forma_pagamento_id'];
	$data_edicao        = date('Y-m-d', strtotime($dados['data_venda']));

	// Limpa o gatilho inicial
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
	// Não precisamos mais de input hidden. O <select id="cliente" name="cliente">
	// será enviado diretamente no FormData por estar vinculado ao form.

	var pag = "<?= $pag ?>";

	// Observa a seleção do cliente (funciona com ou sem Select2)
	$(document).on('change', '#cliente', function() {
		console.log('[CLIENTE] selecionado:', $(this).val());
	});

	// (Opcional) Log específico do Select2, se estiver ativo
	$(document).on('select2:select', '#cliente', function(e) {
		console.log('[CLIENTE][select2:select] selecionado:', $(this).val());
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

		// ========================================================== //
		// ===== DEBUG 3: VERIFICAÇÃO FINAL DO VALOR NO FORM DATA ===== //
		// ========================================================== //
		var check_cliente = formData.get('cliente');
		var check_cliente_input_val = $('#cliente_input').val();

		console.log('%c[DEBUG 3.1 - SUBMIT] Valor do #cliente_input (via .val()): "' + check_cliente_input_val + '"', 'background: #dc3545; color: #fff; font-size: 16px;');
		console.log('%c[DEBUG 3.2 - SUBMIT] Valor do campo "cliente" no FormData (que será enviado): "' + check_cliente + '"', 'background: #007bff; color: #fff; font-size: 16px;');
		// ========================================================== //

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
					// AÇÃO ADICIONADA: Chama o script para limpar a sessão de edição
					$.post('paginas/' + pag + '/limpar_sessao_edicao.php');
					$("#img_loading").hide();

					// Limpa os campos do formulário
					$('#desconto').val('');
					$('#troco').val('');
					$('#cliente').val('').change();
					$('#cliente_input').val('');
					$('#data').val('<?= $data_atual ?>');

					// Atualiza as listas de vendas e produtos
					// Assumindo que 'listar()' existe para atualizar a lista principal de vendas
					// listar(); 
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
				// AÇÃO ADICIONADA: Chama o script para limpar a sessão de edição
				$.post('paginas/' + pag + '/limpar_sessao_edicao.php');

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
					// Assumindo que 'listar()' existe para atualizar a lista principal
					// listar(); 
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

				$('#cliente').on('change', function() {
					// Se quiser logar/validar
					console.log('[CLIENTE] selecionado:', $(this).val());
				});


				// 3. LEIA o valor que está de fato selecionado no <select> após a carga
				var clienteSelecionado = $('#cliente').val();

				// 4. ATUALIZE O CAMPO OCULTO IMEDIATAMENTE!
				// Esta é a correção definitiva.
				$('#cliente_input').val(clienteSelecionado);
				console.log('%c[DEBUG 1.1 - PÓS-AJAX] Valor lido do SELECT #cliente: "' + $('#cliente').val() + '"', 'color: orange; font-weight: bold;');
				console.log('%c[DEBUG 1.2 - PÓS-AJAX] Valor no HIDDEN #cliente_input: "' + $('#cliente_input').val() + '"', 'color: purple; font-weight: bold;');

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

<?php
// require_once("../../../conexao.php"); // Presumindo que já está conectado
$tabela = 'itens_venda';
// @session_start(); // Presumindo que a sessão já está iniciada
$id_usuario = $_SESSION['id'];


// --- Lógica de totais (sem alterações) ---
$desconto = floatval(str_replace(',', '.', $_POST['desconto'] ?? '0'));
$troco = floatval(str_replace(',', '.', $_POST['troco'] ?? '0'));
$tipo_desconto = $_POST['tipo_desconto'] ?? '';
$frete = floatval(str_replace(',', '.', $_POST['frete'] ?? '0'));
$subtotal_itens = 0;
$ids_itens = [];

// Seleciona APENAS os itens do carrinho (id_venda = 0) do funcionário logado
$query = $pdo->prepare("SELECT * FROM $tabela WHERE funcionario = :id_usuario AND id_venda = 0 ORDER BY id ASC");
$query->execute([':id_usuario' => $id_usuario]);
$res = $query->fetchAll(PDO::FETCH_ASSOC);
$linhas = @count($res);

if ($linhas > 0) {
	foreach ($res as $item) {
		$subtotal_itens += $item['total'];
		$ids_itens[] = $item['id'];
	}
}
$valor_desconto = ($tipo_desconto == '%') ? ($subtotal_itens * ($desconto / 100)) : $desconto;
$total_final = $subtotal_itens - $valor_desconto + $frete;
$total_troco = ($troco > 0 && $troco > $total_final) ? ($troco - $total_final) : 0;
?>

<style>
	/* SEUS ESTILOS CSS (sem alterações) */
	.lista-vendas-container {
		overflow-y: auto;
		max-height: 250px;
		width: 100%;
		scrollbar-width: thin;
		scrollbar-color: #888 #f1f1f1;
		border-top: 1px solid #eee;
		border-bottom: 1px solid #eee;
		padding-top: 5px;
	}

	.item-venda {
		display: flex;
		flex-direction: column;
		gap: 12px;
		padding: 12px 8px;
		border-bottom: 1px solid #f0f0f0;
	}

	.item-venda:last-child {
		border-bottom: none;
	}

	.item-header {
		display: flex;
		justify-content: space-between;
		align-items: flex-start;
	}

	.nome-produto {
		font-size: 14px;
		font-weight: 600;
		color: #333;
		padding-right: 10px;
	}

	.item-body {
		display: flex;
		justify-content: space-between;
		align-items: center;
	}

	.controles-produto {
		display: flex;
		align-items: center;
		gap: 10px;
	}

	.controle-qtd a {
		color: #555;
		text-decoration: none;
	}

	.controle-qtd big {
		font-size: 1.3em;
	}

	.controle-preco label {
		font-size: 11px;
		color: #666;
	}

	.input-preco-produto {
		width: 85px;
		height: 30px;
		font-size: 13px;
		padding: 5px;
	}

	.preco-total-item {
		font-size: 14px;
		font-weight: bold;
		color: #2c2c2c;
	}

	.btn-remover-item {
		color: #7d1107;
		text-decoration: none;
		font-size: 1.3em;
		padding: 0 5px;
	}

	.btn-remover-item:hover {
		color: #a9180b;
	}

	.rodape-venda {
		margin-top: 15px;
		padding-top: 10px;
		font-size: 14px;
		border-top: 1px solid #ccc;
	}

	.rodape-linha {
		display: flex;
		justify-content: space-between;
		padding: 3px 5px;
	}

	.rodape-linha span:last-child {
		font-weight: bold;
	}

	@media (min-width: 768px) {
		.item-venda {
			flex-direction: row;
			justify-content: space-between;
			align-items: center;
			padding: 10px 5px;
		}

		.item-header {
			flex-grow: 1;
		}

		.item-body {
			justify-content: flex-end;
			gap: 20px;
		}

		.item-body {
			order: 3;
		}

		.item-header .btn-remover-item {
			order: 4;
			padding-left: 15px;
		}

		.nome-produto {
			font-weight: 500;
		}
	}
</style>

<div class="lista-vendas-container">
	<?php
	if ($linhas > 0) {
		foreach ($res as $item) {
			$id = $item['id'];
			$material_id = $item['material'];
			$valor = $item['valor'];
			$quantidade = $item['quantidade'];
			$total = $item['total'];

			// ========================================================================//
			// ===== CORREÇÃO PARA GARANTIR QUE O NOME DO PRODUTO É ENCONTRADO ======= //
			// ========================================================================//
			// Buscamos o nome na tabela correta 'materiais'
			$query2 = $pdo->prepare("SELECT nome FROM materiais WHERE id = :material_id");
			$query2->execute([':material_id' => $material_id]);
			$nome_produto = $query2->fetchColumn();

			// Se falhar (produto não encontrado/excluído), defina um nome substituto
			if ($nome_produto === false) {
				$nome_produto = "[Material Excluído], id: " . strval($material_id);
			}

			// Formatação
			$quantidadeF = (fmod($quantidade, 1) == 0) ? intval($quantidade) : $quantidade;
			$valorF = number_format($valor, 2, ',', '.');
			$totalF = number_format($total, 2, ',', '.');
	?>
			<div class="item-venda">
				<div class="item-header">
					<div class="nome-produto"><?php echo htmlspecialchars($nome_produto); ?></div>
					<a href="#" onclick="confirmarExclusao(<?php echo $id; ?>)" class="btn-remover-item" title="Remover Item">
						<i class="fa fa-times"></i>
					</a>
				</div>

				<div class="item-body">
					<div class="controles-produto">
						<div class="controle-qtd">
							<a href="#" onclick="diminuir(<?php echo $id; ?>, <?php echo $quantidade; ?>)"><big><i class="fa fa-minus-circle text-danger"></i></big></a>
							<span style="margin: 0 8px; font-size: 14px;"><?php echo $quantidadeF; ?></span>
							<a href="#" onclick="aumentar(<?php echo $id; ?>, <?php echo $quantidade; ?>)"><big><i class="fa fa-plus-circle text-success"></i></big></a>
						</div>
						<div class="controle-preco">
							<label for="preco-produto-<?php echo $id; ?>">Unit.:</label>
							<input type="text" id="preco-produto-<?php echo $id; ?>"
								class="form-control input-preco-produto"
								data-id="<?php echo $id; ?>"
								onkeyup="mascara(this, 'moeda')"
								value="<?php echo $valorF; ?>">
						</div>
					</div>
					<span class="preco-total-item">R$ <?php echo $totalF; ?></span>
				</div>
			</div>
	<?php
		}
	} else {
		echo '<p style="text-align:center; color:#888; padding: 20px 0;">Nenhum item adicionado.</p>';
	}
	?>
</div>

<?php
$total_finalF = number_format($total_final, 2, ',', '.');
$total_trocoF = number_format($total_troco, 2, ',', '.');
?>
<div class="rodape-venda">
	<div class="rodape-linha">
		<span>Itens:</span>
		<span><?php echo $linhas; ?></span>
	</div>
	<div class="rodape-linha" style="font-size: 16px;">
		<span>Total da Venda:</span>
		<span>R$ <?php echo $total_finalF; ?></span>
	</div>
	<?php if ($total_troco > 0): ?>
		<div class="rodape-linha text-primary" style="margin-top: 5px;">
			<span>Troco:</span>
			<span>R$ <?php echo $total_trocoF; ?></span>
		</div>
	<?php endif; ?>
</div>

<?php
$ids_itens_json = json_encode(array_values($ids_itens));
?>

<script type="text/javascript">
	// --- Lógica JavaScript (sem alterações) ---
	var itens = <?= $linhas ?>;
	var ids_materiais = <?= $ids_itens_json ?>;
	$('#ids_itens').val(ids_materiais.join(','));
	$('#subtotal_venda').val('<?= $total_final ?>');
	if ($('#valor_pago').val() === '') {
		// Inicializa o valor pago com o total, se estiver vazio
		$('#valor_pago').val('<?= number_format($total_final, 2, ',', '.') ?>');
	}
	FormaPg();
	if (itens > 0) {
		$("#btn_limpar").show();
		$("#btn_venda").show();
	} else {
		$("#btn_limpar").hide();
		$("#btn_venda").hide();
	}

	function confirmarExclusao(id) {
		if (confirm("Deseja realmente remover este item?")) {
			excluirItem(id);
		}
	}

	function excluirItem(id) {
		$.ajax({
			url: 'paginas/' + pag + "/excluir-item.php",
			method: 'POST',
			data: {
				id
			},
			success: function(msg) {
				(msg.trim() == "Excluído com Sucesso") ? listarVendas(): alert(msg);
			}
		});
	}

	function diminuir(id, quantidade) {
		$.ajax({
			url: 'paginas/' + pag + "/diminuir.php",
			method: 'POST',
			data: {
				id,
				quantidade
			},
			success: function(msg) {
				(msg.trim() == "Excluído com Sucesso" || msg.trim() == "Atualizado com Sucesso") ? listarVendas(): alert(msg);
			}
		});
	}

	function aumentar(id, quantidade) {
		$.ajax({
			url: 'paginas/' + pag + "/aumentar.php",
			method: 'POST',
			data: {
				id,
				quantidade
			},
			success: function(msg) {
				(msg.trim() == "Atualizado com Sucesso") ? listarVendas(): alert(msg);
			}
		});
	}
	$('.input-preco-produto').on('blur', function() {
		var id = $(this).data('id');
		var preco = $(this).val().replace(/\./g, '').replace(',', '.').replace('R$ ', '');
		if (preco !== "" && !isNaN(preco)) {
			$.ajax({
				url: 'paginas/' + pag + "/atualizar-preco.php",
				method: 'POST',
				data: {
					id: id,
					preco: preco
				},
				success: function(res) {
					if (res.trim() === "Atualizado com Sucesso") {
						listarVendas();
					}
				}
			});
		}
	});
	// Funções de máscara de moeda (certifique-se de ter a função 'mascara_decimal' também)
	function mascara(o, f) {
		v_obj = o;
		v_fun = f;
		setTimeout("execmascara()", 1);
	}

	function execmascara() {
		v_obj.value = v_fun(v_obj.value);
	}

	function moeda(v) {
		v = v.replace(/\D/g, "");
		v = v.replace(/(\d)(\d{2})$/, "$1,$2");
		v = v.replace(/(?=(\d{3})+(\D))\B/g, ".");
		return v;
	}
	// function mascara_decimal(id) { /* Implemente ou certifique-se que está em js/ajax.js */ }
</script>