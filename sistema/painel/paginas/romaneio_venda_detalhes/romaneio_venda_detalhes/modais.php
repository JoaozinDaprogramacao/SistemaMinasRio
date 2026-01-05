

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
