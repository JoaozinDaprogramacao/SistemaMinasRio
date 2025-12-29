<?php
$pag = 'romaneio_compra';

//verificar se ele tem a permissão de estar nessa página
if (@$produtos == 'ocultar') {
	echo "<script>window.location='../index.php'</script>";
	exit();
}
?>

<div class="justify-content-between">
	<div class="left-content mt-2 mb-3">
		<a class="btn ripple btn-primary text-white" onclick="inserir()" type="button"><i class="fe fe-plus me-2"></i>Novo Romaneio</a>

		<!-- Adicionar os filtros aqui -->
		<div class="row g-2 mb-3 mt-1 align-items-center">
			<!-- Filtro de Fornecedor -->
			<div class="col-auto">
				<select name="fornecedor" id="fornecedor_filtro" class="form-select form-select-sm" onchange="buscar()">
					<option value="">Fornecedor</option>
					<?php
					$query = $pdo->query("SELECT * FROM fornecedores ORDER BY nome_atacadista ASC");
					$res = $query->fetchAll(PDO::FETCH_ASSOC);
					foreach ($res as $row) {
						echo '<option value="' . $row['id'] . '">' . $row['nome_atacadista'] . '</option>';
					}
					?>
				</select>
			</div>

			<!-- Filtro de Data Inicial -->
			<div class="col-auto">
				<input type="date" name="dataInicial" id="dataInicial" class="form-control form-control-sm" onchange="buscar()">
			</div>

			<!-- Filtro de Data Final -->
			<div class="col-auto">
				<input type="date" name="dataFinal" id="dataFinal" class="form-control form-control-sm" onchange="buscar()">
			</div>
		</div>

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

<div class="row row-sm">
	<div class="col-lg-12">
		<div class="card custom-card">
			<div class="card-body" id="listar">

			</div>
		</div>
	</div>
</div>

<input type="hidden" id="ids">

<script src="paginas/js/<?php echo $pag; ?>/romaneio.js" defer></script>
<div class="modal fade" id="modalForm" tabindex="-1" aria-labelledby="exampleModalLabel" aria-hidden="true">
	<div class="modal-dialog modal-xl">
		<div class="modal-content">
			<div class="modal-header bg-primary text-white">
				<h4 class="modal-title" id="exampleModalLabel"><span id="titulo_inserir"></span></h4>
				<button id="btn-fechar" aria-label="Close" class="btn-close" data-bs-dismiss="modal" type="button"><span class="text-white" aria-hidden="true">&times;</span></button>
			</div>
			<h3 class="fs-3 text-center mt-3">Romaneio de Compra</h3>
			<form id="form-romaneio" method="post">
				<div class="alert alert-danger" id="mensagem-erro" style="display: none; margin: 10px 0;"></div>
				<div class="alert alert-success" id="mensagem-sucesso" style="display: none; margin: 10px 0;"></div>

				<div class="container-fluid px-4 mb-3">
					<div class="row">
						<div class="col-md-6">

							<div class="col-12 mb-2">
								<label class="form-label">Data</label>
								<input type="date" class="form-control" name="data" value="<?= date('Y-m-d'); ?>" onchange="calcularVencimento()">
							</div>

							<div class="col-12 mb-2">
								<label class="form-label">Plano Pgto</label>
								<select id="plano_pgto" name="plano_pgto" class="form-select" onchange="(calculaTotais())">
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

							<div class="col-12 mb-2">
								<label class="form-label">Dias</label>
								<input type="number" id="quant_dias" name="quant_dias" class="form-control" placeholder="Quant. Dias" oninput="calcularVencimento()">
							</div>

							<div class="col-12 mb-2">
								<label class="form-label">Vencimento</label>
								<input type="date" id="vencimento" name="vencimento" class="form-control" value="<?= date('Y-m-d'); ?>">
							</div>

						</div>

						<div class="col-md-6">

							<div class="col-12 mb-2">
								<label class="form-label">Fornecedor</label>
								<select id="fornecedor" name="fornecedor" class="form-select" onchange="buscarDadosFornecedor(this.value)">
									<option value="">Selecione o Fornecedor</option>
									<?php
									$query = $pdo->query("SELECT * from fornecedores order by nome_atacadista asc");
									$res = $query->fetchAll(PDO::FETCH_ASSOC);
									foreach ($res as $row) {
										echo '<option value="' . $row['id'] . '">' . $row['nome_atacadista'] . '</option>';
									}
									?>
								</select>
							</div>

							<div class="col-12 mb-2">
								<label class="form-label">Fazenda</label>
								<input type="text" class="form-control" name="fazenda" placeholder="Desc. Fazenda">
							</div>

							<div class="col-12 mb-2">
								<label class="form-label">Nota Fiscal</label>
								<input type="text" class="form-control" id="nota_fiscal" name="nota_fiscal" placeholder="Número NF">
							</div>
							<div class="col-12 mb-2">
								<label class="form-label">Cliente Atacadista</label>
								<select id="cliente" name="cliente" class="form-select">
									<option value="0">Cliente</option>
									<?php
									// MUDANÇA AQUI: 'order by nome asc' para organizar alfabeticamente
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
                                                            ORDER BY p.nome ASC";

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
							<input
								type="text"
								class="preco_kg_1"
								id="preco_kg_1"
								name="preco_kg_1[]"
								onkeyup="mascara_moeda(this); handleInput(this); calcularValores(this.closest('.linha_1'));" />

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
										$id_unidade = $res[$i]['unidade_medida'];
										$queryUnidade = $pdo->query("SELECT unidade FROM unidade_medida WHERE id = $id_unidade");
										$resUnidade = $queryUnidade->fetch(PDO::FETCH_ASSOC);
										$unidade = $resUnidade['unidade'] ?? 'N/D';
								?>
										<option value="<?php echo $res[$i]['tipo'] ?>">
											<?php echo $res[$i]['tipo'] . '     ' . $unidade ?>
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

				<div id="linha-container_2">

					<div class="linha_2">
						<div class="linha-inferior linha-abatimentos">
							<div class="coluna_romaneio">
								<label for="desc_funrural">Descrição</label>
								<input id="desc_funrural" type="text" value="FUNRURAL" readonly>
							</div>
							<div class="coluna_romaneio">
								<label for="info_funrural">INFO</label>
								<select id="info_funrural" name="info_funrural" onchange="calcularTaxaFunrural()">
									<option value="">Selecione</option>
									<option value="liquido">V. LIQUIDO</option>
									<option value="bruto">V. BRUTO</option>
								</select>
							</div>
							<div class="coluna_romaneio">
								<label for="preco_unit_funrural">PREÇO UNIT</label>
								<select id="preco_unit_funrural" name="preco_unit_funrural" onchange="calcularTaxaFunrural()">
									<option value="">Selecione</option>
									<option value="1.50">1,50 %</option>
									<option value="2.00">2,00 %</option>
								</select>
							</div>
							<div class="coluna_romaneio">
								<label for="valor_funrural">Valor</label>
								<input id="valor_funrural" name="desc_funrural" type="text" class="valor_2" value="0,00" readonly>
							</div>
						</div>
					</div>

					<div class="linha_2">
						<div class="linha-inferior linha-abatimentos">
							<div class="coluna_romaneio">
								<label for="desc_ima">Descrição</label>
								<input id="desc_ima" type="text" value="IMA" readonly>
							</div>
							<div class="coluna_romaneio">
								<label for="info_ima">INFO</label>
								<select id="info_ima" name="info_ima" onchange="calcularTaxaIma()">
									<option value="">Selecione</option>
									<option value="cx">CX</option>
									<option value="um">1</option>
								</select>
							</div>
							<div class="coluna_romaneio">
								<label for="preco_unit_ima">PREÇO UNIT</label>
								<select id="preco_unit_ima" name="preco_unit_ima" onchange="calcularTaxaIma()">
									<option value="">Selecione</option>
									<option value="55.31">55,31</option>
									<option value="0.25">0,25</option>
									<option value="150.00">150,00</option>
								</select>
							</div>
							<div class="coluna_romaneio">
								<label for="valor_ima">Valor</label>
								<input id="valor_ima" name="desc_ima" type="text" class="valor_2" value="0,00" readonly>
							</div>
						</div>
					</div>

					<div class="linha_2">
						<div class="linha-inferior linha-abatimentos">
							<div class="coluna_romaneio">
								<label for="desc_abanorte">Descrição</label>
								<input id="desc_abanorte" type="text" value="ABANORTE" readonly>
							</div>
							<div class="coluna_romaneio">
								<label for="info_abanorte">INFO</label>
								<select id="info_abanorte" name="info_abanorte" onchange="calcularTaxaAbanorte()">
									<option value="">Selecione</option>
									<option value="kg">KG</option>
									<option value="um">1</option>
								</select>
							</div>
							<div class="coluna_romaneio">
								<label for="preco_unit_abanorte">PREÇO UNIT</label>
								<select id="preco_unit_abanorte" name="preco_unit_abanorte" onchange="calcularTaxaAbanorte()">
									<option value="">Selecione</option>
									<option value="52.80">52,80 %</option>
									<option value="0.0025">0,0025 %</option>
								</select>
							</div>
							<div class="coluna_romaneio">
								<label for="valor_abanorte">Valor</label>
								<input id="valor_abanorte" name="desc_abanorte" type="text" class="valor_2" value="0,00" readonly>
							</div>
						</div>
					</div>

					<div class="linha_2">
						<div class="linha-inferior linha-abatimentos">
							<div class="coluna_romaneio">
								<label for="desc_taxa_adm">Descrição</label>
								<input id="desc_taxa_adm" type="text" value="TAXA ADM" readonly>
							</div>
							<div class="coluna_romaneio">
								<label for="taxa_adm_percent">Taxa</label>
								<input
									id="taxa_adm_percent"
									name="taxa_adm_percent"
									type="number"
									oninput="calcularTaxaAdm()"
									placeholder="0">
							</div>
							<div class="coluna_romaneio">
								<label for="preco_unit_taxa_adm">PREÇO UNIT</label>
								<select id="preco_unit_taxa_adm" name="preco_unit_taxa_adm" onchange="calcularTaxaAdm()">
									<option value="">Selecione</option>
									<option value="5">5,00</option>
								</select>
							</div>
							<div class="coluna_romaneio">
								<label for="valor_taxa_adm">Valor</label>
								<input id="valor_taxa_adm" name="valor_taxa_adm" type="text" class="valor_2" value="0,00" readonly>
							</div>
						</div>
					</div>

				</div>
				<div id="linha-container_2"></div>
				<div class="resumo-tabela">
					<div class="resumo-linha">
						<div class="resumo-celula">TOTAL IMPOSTOS E TAXAS</div>
						<div class="resumo-celula">R$ <p id="total_comissao">0,00</p>
						</div>
					</div>
				</div>

				<div class="section-descontos">
					<h3>Descontos Diversos</h3>
					<button type="button" onclick="addDiscountLine()">+ Adicionar desconto</button>

					<div id="discount-template" class="linha_3" style="display: none;">
						<div class="linha-inferior linha-abatimentos">
							<div class="coluna_romaneio">
								<label>Tipo</label>
								<select class="desconto-type" name="desconto_tipo[]" onchange="calcularDescontosDiversos()">
									<option value="+">Adicionar</option>
									<option value="-">Subtrair</option>
								</select>
							</div>
							<div class="coluna_romaneio">
								<label>Valor</label>
								<input
									type="text"
									class="desconto-valor"
									name="desconto_valor[]"
									placeholder="0,00"
									onkeyup="mascara_moeda(this);"
									oninput="calcularDescontosDiversos()">
							</div>
							<div class="coluna_romaneio">
								<label>Obs</label>
								<input
									type="text"
									class="desconto-obs"
									name="desconto_obs[]"
									placeholder="Observação"
									oninput="calcularDescontosDiversos()">
							</div>
							<div class="coluna_romaneio">
								<label>&nbsp;</label>
								<button
									type="button"
									class="remove-btn"
									onclick="removeDiscountLine(this)">×</button>
							</div>
						</div>
					</div>

					<div id="discount-container"></div>

					<div class="resumo-tabela">
						<div class="resumo-linha">
							<div class="resumo-celula">Total Descontos</div>
							<div class="resumo-celula">R$ <span id="total_descontos_diversos">0,00</span></div>
						</div>
					</div>
				</div>

				<div class="resumo-tabela final">
					<div class="resumo-linha">
						<div class="resumo-celula">TOTAL LÍQUIDO A PAGAR</div>
						<div class="resumo-celula">R$ <span id="total_liquido_pagar">0,00</span></div>
					</div>
				</div>


				<input type="hidden" id="valor_liquido" name="valor_liquido">
				<input type="hidden" id="id" name="id">
				<div class="modal-footer d-flex align  justify-content-center align-items-center">
					<button type="submit" id="btn_salvar" class="btn btn-primary">Salvar</button>
				</div>
				<small>
					<div id="mensagem" align="center"></div>
					<div id="mensagemErro" class="alert alert-danger" style="display:none;"></div>

				</small>
			</form>

		</div>

	</div>
</div>


<style>
	.linha-abatimentos {
		display: grid;
		grid-template-columns: repeat(4, 1fr) !important;
		/* 4 colunas iguais */
		gap: 15px;
	}


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
<div class="modal fade" id="modalMostrarDados" tabindex="-1" aria-labelledby="exampleModalLabel" aria-hidden="true">
	<div class="modal-dialog modal-lg">
		<div class="modal-content">
			<div class="modal-header bg-primary text-white">
				<h4 class="modal-title">Detalhes do Romaneio de Compra</h4>
				<button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
			</div>
			<div class="modal-body">
				<div class="row">
					<div class="col-md-4">
						<span class="fw-bold">Fornecedor:</span>
						<span id="fornecedor_modal"></span>
					</div>
					<div class="col-md-4">
						<span class="fw-bold">Data:</span>
						<span id="data_modal"></span>
					</div>
					<div class="col-md-4">
						<span class="fw-bold">Nota Fiscal:</span>
						<span id="nota_modal"></span>
					</div>
				</div>

				<div class="row mt-2">
					<div class="col-md-4">
						<span class="fw-bold">Plano de Pagamento:</span>
						<span id="plano_modal"></span>
					</div>
					<div class="col-md-4">
						<span class="fw-bold">Vencimento:</span>
						<span id="vencimento_modal"></span>
					</div>
					<div class="col-md-4">
						<span class="fw-bold">Quantidade de Dias:</span>
						<span id="quant_dias_modal"></span>
					</div>
				</div>

				<div class="row mt-2">
					<div class="col-md-4">
						<span class="fw-bold">Fazenda:</span>
						<span id="fazenda_modal"></span>
					</div>
					<div class="col-md-4">
						<span class="fw-bold">Cliente Atacadista:</span>
						<span id="cliente_modal"></span>
					</div>
					<div class="col-md-4">
						<span class="fw-bold">Total Líquido a Pagar:</span>
						<span id="total_liquido_modal"></span>
					</div>
				</div>

				<div class="mt-4">
					<h6 class="fw-bold">Produtos</h6>
					<div class="table-responsive">
						<table class="table table-striped">
							<thead>
								<tr>
									<th>Variedade</th>
									<th>Tipo Caixa</th>
									<th>Quant</th>
									<th>Preço KG</th>
									<th>Preço Unit</th>
									<th>Valor</th>
								</tr>
							</thead>
							<tbody id="produtos_modal">
							</tbody>
						</table>
					</div>
				</div>

				<div class="mt-4">
					<h6 class="fw-bold">Impostos, Taxas e Descontos Fixos</h6>
					<div class="row">
						<div class="col-md-4"> <span class="fw-bold">Desconto à Vista (%):</span>
							<span id="desc_avista_perc_modal"></span>
						</div>
						<div class="col-md-4">
							<span class="fw-bold">Funrural:</span>
							<span id="desc_funrural_modal"></span>
						</div>
						<div class="col-md-4">
							<span class="fw-bold">IMA:</span>
							<span id="desc_ima_modal"></span>
						</div>
					</div>
					<div class="row mt-1">
						<div class="col-md-4">
							<span class="fw-bold">Abanorte:</span>
							<span id="desc_abanorte_modal"></span>
						</div>
						<div class="col-md-4">
							<span class="fw-bold">Taxa ADM:</span>
							<span id="desc_taxaadm_modal"></span>
						</div>
						<div class="col-md-4">
						</div>
					</div>
				</div>

				<div class="mt-4">
					<h6 class="fw-bold">Descontos Diversos</h6>
					<div class="table-responsive">
						<table class="table table-sm">
							<thead>
								<tr>
									<th>Tipo</th>
									<th>Obs</th>
									<th>Valor</th>
								</tr>
							</thead>
							<tbody id="descontos_modal">
								<tr>
									<td colspan="3" class="text-center">Nenhum desconto diverso informado</td>
								</tr>
							</tbody>
						</table>
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

<script type="text/javascript">
	var pag = "<?= $pag ?>"
</script>

<script type="text/javascript">
	$(document).ready(function() {
		$('.sel2').select2({
			dropdownParent: $('#modalForm'),
			width: 'resolve'
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
	$("#form-romaneio").submit(function(event) {
		event.preventDefault();

		// Validação de pagamento à vista
		if (!verificarPlanoAVista()) {
			$('#mensagem-erro')
				.html('<ul style="margin: 0; padding-left: 20px;"><li>Para pagamento à vista, o desconto é obrigatório</li></ul>')
				.show();
			$('html, body').animate({
				scrollTop: $("#form-romaneio").offset().top - 100
			}, 500);
			return false;
		}

		var formData = new FormData(this);

		// Esconde mensagens antigas e desabilita botão
		$('#mensagem-erro').hide();
		$('#mensagem-sucesso').hide();
		$('#btn-salvar').prop('disabled', true);

		// Scroll para o topo do formulário
		$('html, body').animate({
			scrollTop: $("#form-romaneio").offset().top - 100
		}, 500);

		// Mensagem de salvando
		$('#mensagem-erro').html('Salvando...').show();

		$.ajax({
			url: 'paginas/romaneio_compra/salvar.php',
			type: 'POST',
			data: formData,
			contentType: false,
			processData: false,

			success: function(response) {
				try {
					const data = typeof response === 'string' ?
						JSON.parse(response) :
						response;

					if (data.status === 'sucesso') {
						// Sucesso: esconde erro, mostra sucesso
						$('#mensagem-erro').hide();
						$('#mensagem-sucesso')
							.html(data.mensagem)
							.show();

						// Limpa e fecha
						limparCampos();
						$('#modalForm').modal('hide');
						$('#btn-salvar').prop('disabled', false);
						listar();

					} else {
						// Erro de validação: mostra lista de mensagens
						$('#btn-salvar').prop('disabled', false);
						const itens = data.mensagem
							.split('<br>')
							.map(msg => `<li>${msg}</li>`)
							.join('');
						$('#mensagem-erro')
							.html(`<ul style="margin: 0; padding-left: 20px;">${itens}</ul>`)
							.show();
					}
				} catch (e) {
					console.error('Erro ao processar resposta:', e, response);
					$('#btn-salvar').prop('disabled', false);
					$('#mensagem-erro')
						.html('Erro ao processar resposta do servidor')
						.show();
				}
			},

			error: function(xhr, status, error) {
				console.error('Erro na requisição:', error);
				$('#btn-salvar').prop('disabled', false);
				$('#mensagem-erro')
					.html('Erro ao comunicar com o servidor. Tente novamente.')
					.show();

				// Logs detalhados no console
				console.log('Status da requisição:', xhr.status);
				console.log('Texto do status:', xhr.statusText);
				console.log('Resposta:', xhr.responseText);

				// Tenta exibir mensagem retornada pelo servidor
				if (xhr.responseText) {
					try {
						const resp = JSON.parse(xhr.responseText);
						if (resp.mensagem) {
							$('#mensagem-erro')
								.html('Erro: ' + resp.mensagem)
								.show();
						}
					} catch (e2) {
						const text = xhr.responseText.length > 100 ?
							xhr.responseText.substring(0, 100) + '...' :
							xhr.responseText;
						$('#mensagem-erro')
							.html('Erro: ' + text)
							.show();
					}
				}
			}
		});

		return false;
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

<script type="text/javascript">
	function calculaTotais() {
		var total = 0;
		$('.valor_1').each(function() {
			var valor = $(this).val() ? parseFloat($(this).val().replace(',', '.')) : 0;
			total += valor;
		});

		// Pegar os valores dos descontos
		var descFunrural = $('#desc-funrural').val() ? parseFloat($('#desc-funrural').val().replace(',', '.')) : 0;
		var descIma = $('#desc-ima').val() ? parseFloat($('#desc-ima').val().replace(',', '.')) : 0;

		// Calcular o total líquido subtraindo os descontos
		var totalLiquido = total - descFunrural - descIma;

		// Atualizar os campos na tela
		$('#valor_total').val(total.toFixed(2).replace('.', ','));
		$('#valor_liquido').val(totalLiquido.toFixed(2).replace('.', ','));

		// Atualizar também os totais exibidos na tabela verde
		$('#total-bruto-banana').text('R$ ' + total.toFixed(2).replace('.', ','));
		$('#total-liquido-banana').text('R$ ' + totalLiquido.toFixed(2).replace('.', ','));
	}

	// Adicionar eventos para chamar calculaTotais quando os descontos forem alterados
	$('#desc-funrural, #desc-ima').on('input', function() {
		calculaTotais();
	});
</script>



<script type="text/javascript">
	$('#fornecedor').on('change', function() {
		buscarDadosFornecedor($(this).val());
	});
</script>

<script type="text/javascript">
	function buscarDadosFornecedor(id) {
		$.ajax({
			url: 'paginas/romaneio_compra/buscar_fornecedor.php',
			type: 'POST',
			data: {
				id: id
			},
			dataType: 'json',
			success: function(dados) {
				if (dados && !dados.error) {
					const planoId = parseInt(dados.plano_pagamento);
					const prazoDias = parseInt(dados.prazo_pagamento);

					document.getElementById('plano_pgto').value = planoId;
					document.getElementById('quant_dias').value = prazoDias;

					calcularVencimento();
					calculaTotais();
				}
			},
			error: function(xhr, status, error) {
				// Tratamento silencioso do erro
			}
		});
	}
</script>

<script type="text/javascript">
	function calcularVencimento() {
		// 1. Pega o valor do input 'data' pelo atributo name, já que ele não tem a classe .data_atual
		var dataEmissao = $('input[name="data"]').val();

		// 2. Pega a quantidade de dias
		var dias = parseInt($('#quant_dias').val());

		// 3. Só calcula se houver data e dias preenchidos
		if (dataEmissao && !isNaN(dias)) {
			// CORREÇÃO DE FUSO HORÁRIO:
			// Divide a string (ex: "2023-11-25") em partes para criar a data localmente
			// Isso evita que o navegador subtraia 1 dia por causa do fuso horário
			var partes = dataEmissao.split('-');
			var dataObj = new Date(partes[0], partes[1] - 1, partes[2]);

			// Adiciona os dias
			dataObj.setDate(dataObj.getDate() + dias);

			// Formata para o padrão do input date (YYYY-MM-DD)
			var ano = dataObj.getFullYear();
			var mes = String(dataObj.getMonth() + 1).padStart(2, '0');
			var dia = String(dataObj.getDate()).padStart(2, '0');

			var dataFinal = ano + '-' + mes + '-' + dia;

			// Define o valor no campo vencimento
			$('#vencimento').val(dataFinal);
		}
	}
</script>

<script type="text/javascript">
	function mascara_decimal(campo) {
		var valor = $('#' + campo).val();

		// Remover caracteres inválidos, manter apenas números e vírgula
		valor = valor.replace(/[^0-9,]/g, '');

		// Se não tiver valor, define como zero
		if (valor === '' || valor === undefined) {
			valor = '0';
		}

		// Tratar a vírgula
		if (valor.indexOf(',') !== -1) {
			// Se já tiver vírgula, garantir que o formato está correto
			var partes = valor.split(',');
			if (partes.length > 2) {
				// Se tiver mais de uma vírgula, considera apenas a primeira
				valor = partes[0] + ',' + partes[1];
			}

			// Formatar para cálculo
			valor = valor.replace(',', '.');
		}

		// Converter para número e formatar
		try {
			var numero = parseFloat(valor);
			if (isNaN(numero)) {
				numero = 0;
			}

			// Formatar com 2 casas decimais
			valor = numero.toFixed(2).replace('.', ',');
		} catch (e) {
			console.error('Erro ao formatar valor decimal:', e);
			valor = '0,00';
		}

		// Atualizar o campo
		$('#' + campo).val(valor);

		// Recalcular totais se necessário
		if (typeof calculaTotais === 'function') {
			calculaTotais();
		}
	}
</script>

<script type="text/javascript">
	function verificarPlanoAVista() {
		var planoSelecionado = $('#plano_pgto option:selected').text().trim().toUpperCase();
		var valorDesconto = $('#desc-avista').val();

		if (planoSelecionado === 'À VISTA' || planoSelecionado === 'Á VISTA') {
			if (!valorDesconto || valorDesconto === '0' || valorDesconto === '0,00') {
				$('#desc-avista').addClass('is-invalid');
				return false;
			}
		}

		$('#desc-avista').removeClass('is-invalid');
		return true;
	}

	// Evento change do plano de pagamento
	$('#plano_pgto').change(function() {
		verificarPlanoAVista();
	});
</script>

<script type="text/javascript">
	function buscar() {
		var dataInicial = $('#dataInicial').val();
		var dataFinal = $('#dataFinal').val();
		var fornecedor = $('#fornecedor_filtro').val();

		$.ajax({
			url: 'paginas/romaneio_compra/listar.php',
			method: 'POST',
			data: {
				p1: dataInicial,
				p2: dataFinal,
				p3: fornecedor
			},
			dataType: "html",
			success: function(result) {
				$("#listar").html(result);
			}
		});
	}
</script>

<script type="text/javascript">
	function formatarData(data) {
		if (!data) return '-';
		return new Date(data).toLocaleDateString('pt-BR');
	}

	function formatarNumero(valor) {
		if (!valor) return '0,00';
		return parseFloat(valor).toFixed(2).replace('.', ',');
	}
</script>

<script type="text/javascript">
	document.addEventListener('DOMContentLoaded', function() {
		const descFunruralInput = document.querySelector('[name="desc_funrural"]');
		const descImaAbanInput = document.querySelector('[name="desc_ima"]');
		const totalBrutoSpan = document.querySelector('#total-bruto');
		const totalLiquidoSpan = document.querySelector('#total-liquido');

		function atualizarTotalLiquido() {
			const totalBruto = parseFloat(totalBrutoSpan.textContent.replace('R$', '').replace('.', '').replace(',', '.'));
			const descFunrural = parseFloat(descFunruralInput.value.replace(',', '.')) || 0;
			const descImaAban = parseFloat(descImaAbanInput.value.replace(',', '.')) || 0;

			const totalLiquido = totalBruto - descFunrural - descImaAban;

			totalLiquidoSpan.textContent = totalLiquido.toLocaleString('pt-BR', {
				style: 'currency',
				currency: 'BRL'
			});
		}

		descFunruralInput.addEventListener('input', atualizarTotalLiquido);
		descImaAbanInput.addEventListener('input', atualizarTotalLiquido);
	});
</script>

<script src="js/ajax.js"></script>