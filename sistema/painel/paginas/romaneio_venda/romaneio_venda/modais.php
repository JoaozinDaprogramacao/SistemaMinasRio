

<div class="modal fade" id="modalForm" tabindex="-1" aria-labelledby="exampleModalLabel" aria-hidden="true">
	<div class="modal-dialog modal-lg">
		<div class="modal-content">
			<div class="modal-header bg-primary text-white">
				<h4 class="modal-title" id="exampleModalLabel"><span id="titulo_inserir"></span></h4>
				<button id="btn-fechar" aria-label="Close" class="btn-close" data-bs-dismiss="modal" type="button"><span class="text-white" aria-hidden="true">&times;</span></button>
			</div>
			<h3 class="fs-3 text-center mt-3">Romaneio de Vendas</h3>
			<div class="mensagens-container" style="margin-bottom: 20px;">
				<div id="mensagem-erro" class="alert alert-danger" style="display: none;"></div>
				<div id="mensagem-sucesso" class="alert alert-success" style="display: none;"></div>
			</div>
			<form id="form-romaneio" method="post">
				<input type="hidden" id="romaneios_selecionados" name="romaneios_selecionados">
				<div id="mensagem-erro"></div>
				<div id="mensagem-sucesso"></div>
				<div class="container-fluid px-4">
					<div class="row g-3">

						<div class="col-md-6">
							<div class="mb-2">
								<label class="form-label">Data</label>
								<input type="date" class="form-control form-control-sm data_atual" name="data" value="<?= date('Y-m-d'); ?>" onchange="calcularVencimento()">
							</div>

							<div class="mb-2">
								<label class="form-label">Plano Pgto</label>
								<select id="plano_pgto" name="plano_pgto" class="form-select form-select-sm sel2" onchange="calculaTotais()">
									<option value="0">Escolher Plano</option>
									<?php
									$query = $pdo->query("SELECT * from planos_pgto order by id asc");
									$res = $query->fetchAll(PDO::FETCH_ASSOC);
									$linhas = @count($res);
									if ($linhas > 0) {
										for ($i = 0; $i < $linhas; $i++) { ?>
											<option value="<?php echo $res[$i]['id'] ?>"><?php echo $res[$i]['nome'] ?></option>
									<?php }
									} ?>
								</select>
							</div>

							<div class="mb-2">
								<label class="form-label">Vencimento</label>
								<input type="date" class="form-control form-control-sm" name="vencimento" id="vencimento" value="<?= date('Y-m-d'); ?>">
							</div>
						</div>

						<div class="col-md-6">
							<div class="mb-2">
								<label class="form-label">Cliente</label>
								<select id="cliente_modal" name="cliente" class="form-select form-select-sm" onchange="buscarDadosCliente(this.value); atualizarListaRomaneiosCompra(this.value); calculaTotais();">
									<option value="0">Escolher Cliente</option>
									<?php
									// Mantido a ordenação por NOME
									$query = $pdo->query("SELECT * from clientes order by nome asc");
									$res = $query->fetchAll(PDO::FETCH_ASSOC);
									$linhas = @count($res);
									if ($linhas > 0) {
										for ($i = 0; $i < $linhas; $i++) { ?>
											<option value="<?php echo $res[$i]['id'] ?>"><?php echo $res[$i]['nome'] ?></option>
									<?php }
									} ?>
								</select>
							</div>

							<div class="mb-2">
								<label class="form-label">Dias</label>
								<input type="number" id="quant_dias" class="form-control form-control-sm" name="quant_dias" placeholder="Dias" onkeyup="calcularVencimento()">
							</div>

							<div class="mb-2">
								<label class="form-label">Nota Fiscal</label>
								<input type="text" class="form-control form-control-sm" id="nota_fiscal" name="nota_fiscal" placeholder="NF">
							</div>
						</div>
					</div>

					<div class="row mt-3">
						<div class="col-md-12">
							<label class="form-label">Romaneios de Compra</label>
							<div class="lista-romaneios form-control form-control-md" id="lista-romaneios-compra" style="min-height: 80px;">
								<p class="text-secondary text-center pt-2">Selecione um Cliente para carregar os Romaneios de Compra relacionados.</p>
							</div>
						</div>
					</div>
				</div>

				<div id="linha-template_1" class="linha_1" style="display: none;">
					<div class="linha-inferior">
						<div class="coluna_romaneio">
							<label for="quant_caixa_1">QUANT. CX</label>
							<input type="number" class="quant_caixa_1" name="quant_caixa_1[]" onkeyup="handleInput(this); calcularValores(this.closest('.linha_1'));">
						</div>
						<div class="coluna_romaneio">
							<label for="produto_1">Variedade</label>
							<select name="produto_1[]" class="produto_1" onchange="handleInput(this); calcularValores(this.closest('.linha_1'));">
								<option value="">Selecione Variedade</option>
								<?php
								$query_sql = "SELECT p.id AS id_produto, p.nome AS nome_produto, c.nome AS nome_categoria 
                                                FROM produtos p 
                                                INNER JOIN categorias c ON p.categoria = c.id 
                                                ORDER BY p.nome ASC"; // Alterei para ordenar por nome do produto, pode ser p.id também

								$stmt = $pdo->query($query_sql);
								$produtos_com_categorias = $stmt->fetchAll(PDO::FETCH_ASSOC);

								if (count($produtos_com_categorias) > 0) {
									foreach ($produtos_com_categorias as $item) { ?>
										<option value="<?php echo $item['id_produto']; ?>">
											<?php echo htmlspecialchars($item['nome_produto']) . ' - ' . htmlspecialchars($item['nome_categoria']); ?>
										</option>
								<?php }
								} ?>
							</select>
						</div>
						<div class="coluna_romaneio">
							<label for="preco_kg_1">Preço KG</label>
							<input type="text" class="preco_kg_1" id="preco_kg_1" name="preco_kg_1[]" onkeyup="mascara_decimal(this); handleInput(this); calcularValores(this.closest('.linha_1'));">
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
									for ($i = 0; $i < $linhas; $i++) {
										// Busca a unidade de medida dentro do loop
										$id_unidade = $res[$i]['unidade_medida'];
										$queryUnidade = $pdo->query("SELECT unidade FROM unidade_medida WHERE id = $id_unidade");
										$resUnidade = $queryUnidade->fetch(PDO::FETCH_ASSOC);
										$unidade = $resUnidade['unidade'] ?? 'N/D'; // N/D caso não encontre a unidade
								?>
										<option value="<?php echo $res[$i]['id'] ?>">
											<?php echo $res[$i]['tipo'] . ' ' . $unidade ?>
										</option>
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

				<div id="linha-container_1"></div>
				<div class="resumo-tabela">
					<div class="resumo-linha">
						<div class="resumo-celula" id="total_caixa">0 CXS</div>
						<div class="resumo-celula">TOTAL BRUTO - BANANA</div>
						<div class="resumo-celula" id="total_bruto">R$ 0,00</div>
					</div>
					<div class="resumo-linha">
						<div class="resumo-celula" id="total_kg">0 KG</div>
						<div class="resumo-celula input">
							<label for="desc-avista">DESCONTO RECEBIMENTO - À VISTA</label>
							<div class="input-wrapper">
								<input id="desc-avista" name="desc-avista" type="text" placeholder="%" onkeyup="(calculaTotais())" />
							</div>
						</div>
						<div class="resumo-celula" id="total-desc">R$ 0,00</div>
					</div>
					<div class="resumo-linha">
						<div class="resumo-celula">TOTAL LÍQUIDO - BANANA</div>
						<div class="resumo-celula"></div>
						<div class="resumo-celula">R$ <p id="total-geral">0,00</p>
						</div>
					</div>
				</div>
				<div id="linha-template_2" class="linha_2" style="display: none;">
					<div class="linha-inferior">
						<div class="coluna_romaneio">
							<label for="desc_2">Descrição</label>
							<select name="desc_2[]" class="desc_2" onchange="handleInput2(this); calcularValores2(this.closest('.linha_2'));">
								<option value="">Selecione Descrição</option>
								<?php
								$query = $pdo->query("SELECT * from descricao_romaneio order by id asc");
								$res = $query->fetchAll(PDO::FETCH_ASSOC);
								$linhas = @count($res);
								if ($linhas > 0) {
									for ($i = 0; $i < $linhas; $i++) { ?>
										<option value="<?php echo $res[$i]['id'] ?>"><?php echo $res[$i]['descricao'] ?></option>
								<?php }
								} ?>
							</select>
						</div>
						<div class="coluna_romaneio">
							<label for="quant_caixa_2">QUANT. CX</label>
							<input type="number" class="quant_caixa_2" name="quant_caixa_2[]" onkeyup="handleInput2(this); calcularValores2(this.closest('.linha_2'));">
						</div>
						<div class="coluna_romaneio">
							<label for="preco_kg_2">Preço KG</label>
							<input type="text" class="preco_kg_2" name="preco_kg_2[]" onkeyup="mascara_decimal(this);  handleInput2(this); calcularValores2(this.closest('.linha_2'));">
						</div>
						<div class="coluna_romaneio">
							<label for="tipo_cx_2">TIPO CX</label>
							<select name="tipo_cx_2[]" class="tipo_cx_2" onchange="handleInput2(this); calcularValores2(this.closest('.linha_2'));">
								<option value="">Selecione</option>
								<?php
								$query = $pdo->query("SELECT * from tipo_caixa order by id asc");
								$res = $query->fetchAll(PDO::FETCH_ASSOC);
								$linhas = @count($res);

								if ($linhas > 0) {
									for ($i = 0; $i < $linhas; $i++) {
										// Busca a unidade de medida dentro do loop
										$id_unidade = $res[$i]['unidade_medida'];
										$queryUnidade = $pdo->query("SELECT unidade FROM unidade_medida WHERE id = $id_unidade");
										$resUnidade = $queryUnidade->fetch(PDO::FETCH_ASSOC);
										$unidade = $resUnidade['unidade'] ?? 'N/D'; // N/D caso não encontre a unidade
								?>
										<option value="<?php echo $res[$i]['id'] ?>">
											<?php echo $res[$i]['tipo'] . ' ' . $unidade ?>
										</option>
								<?php }
								} ?>
							</select>
						</div>
						<div class="coluna_romaneio">
							<label for="preco_unit_2">PREÇO UNIT.</label>
							<input type="text" class="preco_unit_2" name="preco_unit_2[]" readonly>
						</div>
						<div class="coluna_romaneio">
							<label for="valor_2">Valor</label>
							<input type="text" class="valor_2" name="valor_2[]" readonly>
						</div>
					</div>

				</div>

				<div id="linha-container_2"></div>
				<div class="resumo-tabela">
					<div class="resumo-linha">
						<div class="resumo-celula">TOTAL COMISSÃO</div>
						<div class="resumo-celula">R$ <p id="total_comissao">0,00</p>
						</div>
					</div>
				</div>

				<div id="linha-template_3" class="linha_3" style="display: none;">
					<div class="linha-inferior" style="grid-template-columns: repeat(5, 1fr);">
						<div class="coluna_romaneio">
							<label for="obs_3">Observação</label>
							<input type="text" name="obs_3[]" class="obs_3" onchange="handleInput3(this); calcularValores3(this.closest('.linha_3'));">

						</div>
						<div class="coluna_romaneio">
							<label for="material">Descrição</label>
							<select name="material[]" class="material" onchange="handleInput3(this); calcularValores(this.closest('.linha_3'));">
								<option value="">Selecione um Material</option>
								<?php
								$query = $pdo->query("SELECT * from materiais order by id asc");
								$res = $query->fetchAll(PDO::FETCH_ASSOC);
								$linhas = @count($res);
								if ($linhas > 0) {
									for ($i = 0; $i < $linhas; $i++) { ?>
										<option value="<?php echo $res[$i]['id'] ?>"><?php echo $res[$i]['nome'] ?></option>
								<?php }
								} ?>
							</select>
						</div>
						<div class="coluna_romaneio">
							<label for="quant_3">QUANT.</label>
							<input type="text" class="quant_3" name="quant_3[]" onkeyup="handleInput3(this); calcularValores3(this.closest('.linha_3'));">
						</div>
						<div class="coluna_romaneio">
							<label for="preco_unit_3">PREÇO UNIT.</label>
							<input type="text" class="preco_unit_3" name="preco_unit_3[]" onkeyup="mascara_decimal(this); handleInput3(this); calcularValores3(this.closest('.linha_3'));">
						</div>
						<div class="coluna_romaneio">
							<label for="valor_3">Valor</label>
							<input type="text" class="valor_3" name="valor_3[]" readonly>
						</div>
					</div>

				</div>
				<div id="linha-container_3"></div>
				<div class="resumo-tabela">
					<div class="resumo-linha">
						<div class="resumo-celula">TOTAL MATERIAIS</div>
						<div class="resumo-celula">R$ <p id="total_materiais">0,00</p>
						</div>
					</div>
				</div>

				<div class="resumo-tabela">
					<div class="resumo-linha">
						<div class="resumo-celula">TOTAL DA CARGA</div>
						<div class="resumo-celula">R$ <p id="total_carga">0,00</p>
						</div>
					</div>
					<div class="resumo-linha radio">
						<div class="radio-group" style="display: block !important;">
							<div style="display: flex; gap: 10px; align-items: center;">
								<label>
									<input type="checkbox" name="adicional_ativo" id="adicional_ativo" onchange="adicionalAtivado()">
									Adicional
								</label>
								<input type="text" placeholder="Descrição do Adicional" name="descricao_adicional" id="descricao_adicional">
								<input type="text" placeholder="Valor do Adicional" name="valor_adicional" id="valor_adicional" onkeyup="mascara_decimal(this)">
							</div>

							<br>

							<div style="display: flex; gap: 10px; align-items: center;">
								<label>
									<input type="checkbox" name="desconto_ativo" id="desconto_ativo" onchange="descontoAtivado()">
									Desconto
								</label>
								<input type="text" placeholder="Descrição do Desconto" name="descricao_desconto" id="descricao_desconto">
								<input type="text" placeholder="Valor do Desconto" name="valor_desconto" id="valor_desconto" onkeyup="mascara_decimal(this)">
							</div>
						</div>
					</div>

					<div class="resumo-linha">
						<div class="resumo-celula">VALOR LÍQUIDO A RECEBER</div>
						<div class="resumo-celula" style="display: flex; gap: 5px;">R$ <p id="total_liquido">0,00</p>
						</div>
					</div>

				</div>

				<input type="hidden" id="valor_liquido" name="valor_liquido">
				<input type="hidden" id="id" name="id">
				<div class="modal-footer d-flex align  justify-content-center align-items-center">
					<button type="submit" id="btn_salvar" class="btn btn-primary">Salvar</button>
				</div>
				<small>
					<div id="mensagem" align="center"></div>
				</small>
			</form>

		</div>
	</div>
</div>


<!-- ... rest of the HTML/Modals ... -->

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

	.select2-container {
		width: 100% !important;
	}

	.select2-container .select2-selection--multiple {
		min-height: 38px;
		border: 1px solid #ced4da;
	}

	.select2-container--default .select2-selection--multiple .select2-selection__choice {
		background-color: #0d6efd;
		color: white;
		border: none;
		padding: 2px 8px;
		margin: 2px;
	}

	.select2-container--default .select2-selection--multiple .select2-selection__choice__remove {
		color: white;
		margin-right: 5px;
	}

	.lista-romaneios {
		height: 100px;
		overflow-y: auto;
		padding: 0.25rem 0.5rem;
		background-color: #fff;
		font-size: 0.875rem;
	}

	.romaneio-item {
		padding: 4px;
		border-bottom: 1px solid #eee;
		cursor: pointer;
	}

	.romaneio-item:hover {
		background-color: #f8f9fa;
	}

	.romaneio-item.selecionado {
		background-color: #e7f3ff;
		border-left: 3px solid #0d6efd;
	}

	.is-invalid {
		border: 1px solid #dc3545 !important;
		background-color: #fff8f8 !important;
	}

	.mensagem-erro {
		color: #dc3545;
		font-size: 14px;
		margin-top: 5px;
		display: block;
	}

	.text-info {
		color: #0dcaf0 !important;
	}

	.text-success {
		color: #198754 !important;
	}

	.text-danger {
		color: #dc3545 !important;
	}

	#mensagem-erro,
	#mensagem-sucesso {
		margin: 10px 0;
		padding: 10px;
		border-radius: 4px;
		display: none;
	}

	#mensagem-erro {
		background-color: #ffe6e6;
		border: 1px solid #ff9999;
	}

	#mensagem-sucesso {
		background-color: #e6ffe6;
		border: 1px solid #99ff99;
		color: #006600;
	}

	.text-danger {
		color: #cc0000 !important;
	}

	.modal-lg {
		max-width: 70%;
	}

	.modal-content {
		border-radius: 8px;
	}

	.modal-header {
		padding: 10px 15px;
	}

	.modal-body {
		padding: 15px;
	}

	.form-control-sm,
	.form-select-sm {
		height: 31px;
		padding: 0.25rem 0.5rem;
		font-size: 0.875rem;
	}

	.form-label {
		font-size: 0.875rem;
		margin-bottom: 0.25rem;
	}

	.container-fluid {
		padding: 15px;
	}
</style>



<!-- Modal Dados -->
<div class="modal fade" id="modalDados" tabindex="-1" aria-labelledby="exampleModalLabel" aria-hidden="true">
	<div class="modal-dialog modal-lg">
		<div class="modal-content">
			<div class="modal-header bg-primary text-white">
				<h4 class="modal-title">Detalhes do Romaneio</h4>
				<button id="btn-fechar-dados" type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
			</div>
			<div class="modal-body">
				<div class="row">
					<div class="col-md-12">
						<div class="table-responsive">
							<table class="table table-bordered">
								<tr>
									<td class="bg-light"><strong>Nº Romaneio:</strong></td>
									<td><span id="id_dados"></span></td>
									<td class="bg-light"><strong>Data:</strong></td>
									<td><span id="data_dados"></span></td>
								</tr>
								<tr>
									<td class="bg-light"><strong>Cliente:</strong></td>
									<td><span id="cliente_dados"></span></td>
									<td class="bg-light"><strong>Nota Fiscal:</strong></td>
									<td><span id="nota_fiscal_dados"></span></td>
								</tr>
								<tr>
									<td class="bg-light"><strong>Plano Pagamento:</strong></td>
									<td><span id="plano_pgto_dados"></span></td>
									<td class="bg-light"><strong>Vencimento:</strong></td>
									<td><span id="vencimento_dados"></span></td>
								</tr>
							</table>
						</div>

						<h5 class="mt-4">Produtos</h5>
						<div id="itens_dados"></div>

						<h5 class="mt-4">Comissões</h5>
						<div id="comissoes_dados"></div>

						<h5 class="mt-4">Materiais e Observações</h5>
						<div id="materiais_dados"></div>

						<div class="table-responsive mt-4">
							<table class="table table-bordered">
								<tr>
									<td class="bg-light"><strong>Descrição:</strong></td>
									<td><span id="descricao_a_dados"></span></td>
									<td class="bg-light"><strong>Valor Adicional:</strong></td>
									<td><span id="adicional_dados"></span></td>
								</tr>
								<tr>
									<td class="bg-light"><strong>Descrição:</strong></td>
									<td><span id="descricao_d_dados"></span></td>
									<td class="bg-light"><strong>Valor Desconto:</strong></td>
									<td><span id="desconto_dados"></span></td>
								</tr>
								<tr>
									<td class="bg-light"><strong>Valor Total:</strong></td>
									<td colspan="3"><span id="total_liquido_dados"></span></td>
								</tr>
							</table>
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

