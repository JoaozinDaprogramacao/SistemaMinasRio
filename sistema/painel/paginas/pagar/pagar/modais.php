
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
								<table class="text-left table table-bordered">
									<tr>
										<td class="bg-warning alert-warning">Pessoa</td>
										<td><span id="cliente_dados"></span></td>
									</tr>
									<tr>
										<td class="bg-warning alert-warning">Vencimento</td>
										<td><span id="vencimento_dados"></span></td>
									</tr>
									<tr>
										<td class="bg-warning alert-warning w_150">Pagamento</td>
										<td><span id="data_pgto_dados"></span></td>
									</tr>
									<tr>
										<td class="bg-warning alert-warning w_150">Frequência</td>
										<td><span id="frequencia_dados"></span></td>
									</tr>
									<tr>
										<td class="bg-warning alert-warning w_150">Multa</td>
										<td><span id="multa_dados"></span></td>
									</tr>
									<tr>
										<td class="bg-warning alert-warning w_150">Júros</td>
										<td><span id="juros_dados"></span></td>
									</tr>
									<tr>
										<td class="bg-warning alert-warning w_150">Desconto</td>
										<td><span id="desconto_dados"></span></td>
									</tr>
									<tr>
										<td class="bg-warning alert-warning w_150">Taxa</td>
										<td><span id="taxa_dados"></span></td>
									</tr>
									<tr>
										<td class="bg-warning alert-warning w_150">Subtotal</td>
										<td><span id="total_dados"></span></td>
									</tr>
								</table>
							</div>
						</div>
					</div>

					<div class="col-md-6">
						<div class="tile">
							<div class="table-responsive">
								<table class="text-left table table-bordered">
									<tr>
										<td class="bg-warning alert-warning w_150">Pago</td>
										<td><span id="pago_dados"></span></td>
									</tr>
									<tr>
										<td class="bg-warning alert-warning w_150">Lançado Por</td>
										<td><span id="usu_lanc_dados"></span></td>
									</tr>
									<tr>
										<td class="bg-warning alert-warning w_150">Baixa Usuário</td>
										<td><span id="usu_pgto_dados"></span></td>
									</tr>
									<tr>
										<td class="bg-warning alert-warning w_150">OBS</td>
										<td><span id="obs_dados"></span></td>
									</tr>
									<tr>
										<td align="center"><img src="" id="target_dados" width="200px"></td>
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

<script>
document.addEventListener('DOMContentLoaded', function () {
    const textarea = document.getElementById('obs-baixar');
    const contador = document.getElementById('contador-obs');
    const limite = 1000;

    textarea.addEventListener('input', function () {
        const caracteresRestantes = limite - this.value.length;
        contador.textContent = `${caracteresRestantes} caracteres restantes`;
        if (caracteresRestantes <= 50) {
            contador.classList.add('text-danger');
            contador.classList.remove('text-muted');
        } else {
            contador.classList.remove('text-danger');
            contador.classList.add('text-muted');
        }
    });
});
</script>

<div class="modal fade" id="modalParcelar" tabindex="-1" role="dialog" aria-hidden="true">
	<div class="modal-dialog" role="document">
		<div class="modal-content">
			<div class="modal-header bg-primary text-white">
				<h4 class="modal-title">Parcelar Conta: <span id="nome-parcelar"> </span></h4>
				<button id="btn-fechar-parcelar" aria-label="Close" class="btn-close" data-bs-dismiss="modal" type="button"><span class="text-white" aria-hidden="true">&times;</span></button>
			</div>
			<form method="post" id="form-parcelar">
				<div class="modal-body">
					<div class="row">
						<div class="col-md-3">
							<div class="mb-3">
								<label>Valor</label>
								<input type="text" class="form-control" name="valor-parcelar" id="valor-parcelar" readonly>
							</div>
						</div>
						<div class="col-md-2">
							<div class="mb-3">
								<label>Parcelas</label>
								<input type="number" class="form-control" name="qtd-parcelar" id="qtd-parcelar" required>
							</div>
						</div>
						<div class="col-md-4">
							<div class="form-group">
								<label>Frequência Parcelas</label>
								<select class="form-select" name="frequencia" id="frequencia-parcelar" required style="width:100%;">
									<?php
									$query = $pdo->query("SELECT * FROM frequencias order by id asc");
									$res = $query->fetchAll(PDO::FETCH_ASSOC);
									for ($i = 0; $i < @count($res); $i++) {
										$nome_item = $res[$i]['frequencia'];
										$dias = $res[$i]['dias'];
										if ($nome_item != 'Uma Vez' && $nome_item != 'Única' && $nome_item != 'Nenhuma') {
									?>
										<option <?php if ($nome_item == 'Mensal') echo 'selected'; ?> value="<?php echo $dias ?>"><?php echo $nome_item ?></option>
									<?php } } ?>
								</select>
							</div>
						</div>
						<div class="col-md-3" style="margin-top:25px">
							<button type="submit" class="btn btn-primary">Parcelar</button>
						</div>
					</div>
					<br>
					<input type="hidden" name="id-parcelar" id="id-parcelar">
					<input type="hidden" name="nome-parcelar" id="nome-input-parcelar">
					<small>
						<div id="mensagem-parcelar" align="center" class="mt-3"></div>
					</small>
				</div>
				<div class="modal-footer"></div>
			</form>
		</div>
	</div>
</div>

<!-- Modal Baixar (multi-linha, igual ao receber) -->
<div class="modal fade" id="modalBaixar" tabindex="-1" aria-hidden="true">
	<div class="modal-dialog modal-lg">
		<div class="modal-content border-0 shadow">
			<div class="modal-header bg-primary text-white py-2">
				<h5 class="modal-title">Pagar Conta: <span id="descricao-baixar" class="fw-light"></span></h5>
				<button id="btn-fechar-baixar" aria-label="Close" class="btn-close btn-close-white" data-bs-dismiss="modal" type="button"></button>
			</div>

			<form id="form-baixar" method="post" enctype="multipart/form-data">
				<div class="modal-body bg-white">

					<!-- Cabeçalho: Romaneio de Compra -->
					<div id="secao-romaneio-baixar" class="row g-2 mb-3 pb-3 border-bottom" style="display:none;">
						<div class="col-12 mb-1">
							<span class="badge bg-warning text-dark">
								<i class="fa fa-file-text-o me-1"></i> Romaneio de Compra
							</span>
						</div>
						<div class="col-md-3">
							<label class="text-uppercase fw-bold text-muted small">Fornecedor</label>
							<input type="text" class="form-control form-control-sm bg-light border-0" id="fornecedor-baixar" readonly>
						</div>
						<div class="col-md-2">
							<label class="text-uppercase fw-bold text-muted small">Faturamento</label>
							<input type="text" class="form-control form-control-sm bg-light border-0" id="fat-baixar" readonly>
						</div>
						<div class="col-md-2">
							<label class="text-uppercase fw-bold text-muted small">Romaneio #</label>
							<input type="text" class="form-control form-control-sm bg-light border-0" id="romaneio-baixar" readonly>
						</div>
						<div class="col-md-2">
							<label class="text-uppercase fw-bold text-muted small">Valor Original</label>
							<input type="text" class="form-control form-control-sm bg-light border-0 fw-bold" id="valor-original-baixar" readonly>
						</div>
						<div class="col-md-3">
							<label class="text-uppercase fw-bold text-muted small">Vencimento</label>
							<input type="text" class="form-control form-control-sm bg-light border-0" id="vencimento-baixar" readonly>
						</div>
					</div>

					<!-- Cabeçalho: Conta Avulsa (água, luz, funcionário etc.) -->
					<div id="secao-conta-baixar" class="row g-2 mb-3 pb-3 border-bottom" style="display:none;">
						<div class="col-12 mb-1">
							<span class="badge bg-secondary text-white" id="badge-tipo-conta">
								<i class="fa fa-file-o me-1"></i> Conta
							</span>
						</div>
						<div class="col-md-5">
							<label class="text-uppercase fw-bold text-muted small">Pessoa / Responsável</label>
							<input type="text" class="form-control form-control-sm bg-light border-0" id="pessoa-baixar" readonly>
						</div>
						<div class="col-md-3">
							<label class="text-uppercase fw-bold text-muted small">Valor Original</label>
							<input type="text" class="form-control form-control-sm bg-light border-0 fw-bold" id="valor-original-baixar" readonly>
						</div>
						<div class="col-md-4">
							<label class="text-uppercase fw-bold text-muted small">Vencimento</label>
							<input type="text" class="form-control form-control-sm bg-light border-0" id="vencimento-baixar" readonly>
						</div>
					</div>

					<div class="row g-2 mb-4 p-3 rounded border bg-light">
						<div class="col-md-3">
							<label class="small text-muted fw-bold text-uppercase">Multa (+)</label>
							<input onkeyup="totalizar()" type="text" class="form-control form-control-sm input-zeravel" name="valor-multa" id="valor-multa" value="0">
						</div>
						<div class="col-md-3">
							<label class="small text-muted fw-bold text-uppercase">Juros (+)</label>
							<input onkeyup="totalizar()" type="text" class="form-control form-control-sm input-zeravel" name="valor-juros" id="valor-juros" value="0">
						</div>
						<div class="col-md-3">
							<label class="small text-muted fw-bold text-uppercase">Acréscimo (+)</label>
							<input onkeyup="totalizar()" type="text" class="form-control form-control-sm input-zeravel" name="valor-acrescimo" id="valor-acrescimo" value="0">
						</div>
						<div class="col-md-3">
							<label class="small text-muted fw-bold text-uppercase">Desconto (-)</label>
							<input onkeyup="totalizar()" type="text" class="form-control form-control-sm input-zeravel" name="valor-desconto" id="valor-desconto" value="0">
						</div>
					</div>

					<div class="row g-0 mb-4 p-3 rounded border align-items-center shadow-sm" style="background-color: #f8f9fa;">
						<div class="col-md-4 text-center px-2">
							<label class="small text-primary fw-bold text-uppercase mb-1">Subtotal Líquido</label>
							<input type="text" class="form-control form-control-lg fw-bold bg-white border-primary text-primary text-center mx-auto shadow-none" name="subtotal" id="subtotal" readonly style="height: 45px;">
						</div>
						<div class="col-md-4 text-center border-start border-end px-2">
							<label class="small text-danger fw-bold text-uppercase mb-1">Total a Pagar</label>
							<div class="d-flex align-items-center justify-content-center" style="height: 45px;">
								<span class="fs-4 fw-bold text-danger" id="lbl-total-recebido">R$ 0,00</span>
							</div>
						</div>
						<div class="col-md-4 text-center px-2">
							<label class="small text-secondary fw-bold text-uppercase mb-1">Status da Conta</label>
							<div class="d-flex align-items-center justify-content-center" style="height: 45px;">
								<span class="fs-4" id="lbl-status-conta" style="font-weight: 700;">-</span>
							</div>
						</div>
					</div>

					<div class="mb-3 border-bottom pb-3">
						<label class="fw-bold text-dark mb-2">Detalhes dos Pagamentos</label>

						<div id="linha-template-pagamento" class="linha-pagamento row g-2 mb-2 align-items-end p-2 bg-light border rounded" style="display: none;">
							<div class="col-md-2">
								<label class="small fw-bold text-secondary text-uppercase">Valor</label>
								<input type="text" class="form-control form-control-sm valor_pagamento" name="valor_baixar[]" onkeyup="handlePagamentoInput(this); totalizarPagamentos();">
							</div>
							<div class="col-md-3">
								<label class="small fw-bold text-secondary text-uppercase">Data</label>
								<input type="date" class="form-control form-control-sm data_pagamento" name="data_baixar[]" value="<?php echo date('Y-m-d') ?>" onchange="handlePagamentoInput(this)">
							</div>
							<div class="col-md-2">
								<label class="small fw-bold text-secondary text-uppercase">Forma</label>
								<select class="form-select form-select-sm forma_pagamento" name="saida_baixar[]" onchange="handlePagamentoInput(this)">
									<option value="">Selecione...</option>
									<?php
									$query = $pdo->query("SELECT * FROM formas_pgto order by id asc");
									$res = $query->fetchAll(PDO::FETCH_ASSOC);
									for ($i = 0; $i < @count($res); $i++) { ?>
										<option value="<?php echo $res[$i]['id'] ?>"><?php echo $res[$i]['nome'] ?></option>
									<?php } ?>
								</select>
							</div>
							<div class="col-md-2">
								<label class="small fw-bold text-secondary text-uppercase">Banco</label>
								<select class="form-select form-select-sm banco_pagamento" name="banco_baixar[]" onchange="handlePagamentoInput(this)">
									<option value="">Selecione...</option>
									<?php
									$query = $pdo->query("SELECT * FROM bancos order by id asc");
									$res = $query->fetchAll(PDO::FETCH_ASSOC);
									for ($i = 0; $i < @count($res); $i++) { ?>
										<option value="<?php echo $res[$i]['id'] ?>"><?php echo $res[$i]['banco'] ?></option>
									<?php } ?>
								</select>
							</div>
							<div class="col-md-3">
								<label class="small fw-bold text-secondary text-uppercase">N° Operação</label>
								<input type="text" class="form-control form-control-sm operacao_pagamento" name="numero_operacao[]" placeholder="Cód/DOC" onkeyup="handlePagamentoInput(this)">
							</div>
						</div>

						<div id="linha-container-pagamento"></div>
					</div>

					<div class="row g-3">
						<div class="col-md-7">
							<label class="small fw-bold text-secondary text-uppercase" for="obs-baixar">Observações</label>
							<textarea class="form-control shadow-sm" name="obs-baixar" id="obs-baixar" rows="2" maxlength="1000" placeholder="Informações adicionais sobre o pagamento..."></textarea>
							<div id="contador-obs" class="form-text text-end text-muted" style="font-size: 0.75rem;">
								1000 caracteres restantes
							</div>
						</div>
						<div class="col-md-5">
							<label class="small fw-bold text-secondary text-uppercase" for="comprovante">Arquivar Comprovante</label>
							<input type="file" class="form-control shadow-sm" name="comprovante" id="comprovante" accept="image/*,.pdf">
						</div>
					</div>
					<input type="hidden" name="id-baixar" id="id-baixar">
				</div>

				<div class="modal-footer bg-light border-0">
					<button type="submit" class="btn btn-success px-5 fw-bold shadow-sm">Confirmar Pagamento</button>
				</div>
			</form>
		</div>
	</div>
</div>

<!-- Modal Baixa em Massa -->
<div class="modal fade" id="modalBaixarMassa" tabindex="-1" aria-hidden="true">
	<div class="modal-dialog">
		<div class="modal-content border-0 shadow">
			<div class="modal-header bg-success text-white py-2">
				<h5 class="modal-title">Baixa em Massa</h5>
				<button id="btn-fechar-baixar-massa" aria-label="Close" class="btn-close btn-close-white" data-bs-dismiss="modal" type="button"></button>
			</div>
			<form id="form-baixar-massa" method="post">
				<div class="modal-body bg-white">
					<div class="alert alert-light border d-flex justify-content-between align-items-center mb-3">
						<span><span id="qtd_contas_massa">0</span> título(s) selecionado(s)</span>
						<span class="text-success fw-bold" id="total_contas"></span>
					</div>

					<div class="row g-2 mb-2">
						<div class="col-md-6">
							<label class="small fw-bold text-secondary text-uppercase">Data do Pagamento</label>
							<input type="date" class="form-control form-control-sm" name="data_baixar" id="data-baixar-massa" value="<?php echo date('Y-m-d') ?>" required>
						</div>
						<div class="col-md-6">
							<label class="small fw-bold text-secondary text-uppercase">Forma de Pagamento</label>
							<select class="form-select form-select-sm" name="forma_baixar" id="forma-baixar-massa" required>
								<option value="">Selecione...</option>
								<?php
								$query = $pdo->query("SELECT * FROM formas_pgto order by id asc");
								$res = $query->fetchAll(PDO::FETCH_ASSOC);
								for ($i = 0; $i < @count($res); $i++) { ?>
									<option value="<?php echo $res[$i]['id'] ?>"><?php echo $res[$i]['nome'] ?></option>
								<?php } ?>
							</select>
						</div>
					</div>

					<div class="row g-2 mb-2">
						<div class="col-md-6">
							<label class="small fw-bold text-secondary text-uppercase">Banco</label>
							<select class="form-select form-select-sm" name="banco_baixar" id="banco-baixar-massa" required>
								<option value="">Selecione...</option>
								<?php
								$query = $pdo->query("SELECT * FROM bancos order by id asc");
								$res = $query->fetchAll(PDO::FETCH_ASSOC);
								for ($i = 0; $i < @count($res); $i++) { ?>
									<option value="<?php echo $res[$i]['id'] ?>"><?php echo $res[$i]['banco'] ?></option>
								<?php } ?>
							</select>
						</div>
						<div class="col-md-6">
							<label class="small fw-bold text-secondary text-uppercase">N° Operação</label>
							<input type="text" class="form-control form-control-sm" name="numero_operacao_baixar" id="operacao-baixar-massa" placeholder="Cód/DOC">
						</div>
					</div>

					<div class="mb-2">
						<label class="small fw-bold text-secondary text-uppercase">Observações</label>
						<textarea class="form-control form-control-sm" name="obs_baixar" id="obs-baixar-massa" rows="2" placeholder="Aplicada a todos os títulos selecionados..."></textarea>
					</div>

					<input type="hidden" name="ids" id="ids-baixar-massa">
					<div id="mensagem-baixar-massa" class="mt-2"></div>
				</div>
				<div class="modal-footer bg-light border-0">
					<button type="submit" class="btn btn-success px-4 fw-bold shadow-sm">Confirmar Baixa</button>
				</div>
			</form>
		</div>
	</div>
</div>

<!-- Modal Residuos -->
<div class="modal fade" id="modalResiduos" tabindex="-1" aria-hidden="true">
	<div class="modal-dialog">
		<div class="modal-content">
			<div class="modal-header bg-primary text-white">
				<h4 class="modal-title">Resíduos da Conta</h4>
				<button id="btn-fechar-residuos" aria-label="Close" class="btn-close" data-bs-dismiss="modal" type="button"><span class="text-white" aria-hidden="true">&times;</span></button>
			</div>
			<div class="modal-body">
				<small>
					<div id="listar-residuos"></div>
				</small>
			</div>
		</div>
	</div>
</div>

<!-- Modal Arquivos -->
<div class="modal fade" id="modalArquivos" tabindex="-1" aria-hidden="true">
	<div class="modal-dialog">
		<div class="modal-content">
			<div class="modal-header bg-primary text-white">
				<h4 class="modal-title">Gestão de Arquivos - <span id="nome-arquivo"> </span></h4>
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
								<img src="images/arquivos/sem-foto.png" width="60px" id="target-arquivos">
							</div>
						</div>
					</div>
					<div class="row">
						<div class="col-md-8">
							<input type="text" class="form-control" name="nome-arq" id="nome-arq" placeholder="Nome do Arquivo *" required>
						</div>
						<div class="col-md-4">
							<button type="submit" class="btn btn-primary">Inserir</button>
						</div>
					</div>
					<hr>
					<small>
						<div id="listar-arquivos"></div>
					</small>
					<br>
					<small>
						<div align="center" id="mensagem-arquivo"></div>
					</small>
					<input type="hidden" class="form-control" name="id-arquivo" id="id-arquivo">
				</div>
			</form>
		</div>
	</div>
</div>

<!-- Modal Form: Inserir / Editar Conta a Pagar -->
<div class="modal fade" id="modalForm" tabindex="-1" aria-hidden="true">
	<div class="modal-dialog modal-lg">
		<div class="modal-content">
			<div class="modal-header bg-primary text-white">
				<h4 class="modal-title"><span id="titulo_inserir">Inserir Registro</span></h4>
				<button id="btn-fechar" aria-label="Close" class="btn-close" data-bs-dismiss="modal" type="button"><span class="text-white" aria-hidden="true">&times;</span></button>
			</div>
			<form id="form" enctype="multipart/form-data">
				<div class="modal-body">
					<div class="row">
						<div class="col-md-4 mb-2">
							<label>Categoria</label>
							<select name="categoria_pagar" id="categoria_pagar" class="form-select">
								<option value="">— Selecione a Categoria —</option>
								<?php
								try {
									$q = $pdo->query("SELECT id, nome FROM categorias_pagar ORDER BY nome ASC");
									foreach ($q->fetchAll(PDO::FETCH_ASSOC) as $row) {
										echo '<option value="' . $row['id'] . '">' . htmlspecialchars($row['nome']) . '</option>';
									}
								} catch (Exception $e) {}
								?>
							</select>
						</div>
						<div class="col-md-3 mb-2">
							<label>Valor</label>
							<input type="text" class="form-control" id="valor" name="valor" placeholder="0,00" onkeyup="mascara_moeda(this)">
						</div>
						<div class="col-md-5 mb-2">
							<label>Descrição</label>
							<input type="text" class="form-control" id="descricao" name="descricao" placeholder="Ex: Salário Janeiro, Conta de Luz...">
						</div>
					</div>
					<div class="row">
						<div class="col-md-6 mb-2">
							<label>Fornecedor</label>
							<select name="fornecedor" id="fornecedor" class="form-select">
								<option value="0">— Nenhum —</option>
								<?php
								$q = $pdo->query("SELECT id, nome_atacadista FROM fornecedores ORDER BY nome_atacadista ASC");
								foreach ($q->fetchAll(PDO::FETCH_ASSOC) as $row) {
									echo '<option value="' . $row['id'] . '">' . htmlspecialchars($row['nome_atacadista']) . '</option>';
								}
								?>
							</select>
						</div>
						<div class="col-md-6 mb-2">
							<label>Funcionário</label>
							<select name="funcionario" id="funcionario" class="form-select">
								<option value="0">— Nenhum —</option>
								<?php
								$q = $pdo->query("SELECT id, nome FROM funcionarios ORDER BY nome ASC");
								foreach ($q->fetchAll(PDO::FETCH_ASSOC) as $row) {
									echo '<option value="' . $row['id'] . '">' . htmlspecialchars($row['nome']) . '</option>';
								}
								?>
							</select>
						</div>
					</div>
					<div class="row">
						<div class="col-md-2 mb-2">
							<label>Vencimento</label>
							<input type="date" name="vencimento" id="vencimento" value="<?php echo date('Y-m-d') ?>" class="form-control">
						</div>
						<div class="col-md-2 mb-2">
							<label>Pago Em</label>
							<input type="date" name="data_pgto" id="data_pgto" value="" class="form-control">
						</div>
						<div class="col-md-2 mb-2">
							<label>Forma Pgto</label>
							<select name="forma_pgto" id="forma_pgto" class="form-select">
								<?php
								$q = $pdo->query("SELECT id, nome FROM formas_pgto ORDER BY id ASC");
								foreach ($q->fetchAll(PDO::FETCH_ASSOC) as $row) {
									echo '<option value="' . $row['id'] . '">' . $row['nome'] . '</option>';
								}
								?>
							</select>
						</div>
						<div class="col-md-2 mb-2">
							<label>Frequência</label>
							<select name="frequencia" id="frequencia" class="form-select">
								<?php
								$q = $pdo->query("SELECT dias, frequencia FROM frequencias ORDER BY id ASC");
								foreach ($q->fetchAll(PDO::FETCH_ASSOC) as $row) {
									echo '<option value="' . $row['dias'] . '">' . $row['frequencia'] . '</option>';
								}
								?>
							</select>
						</div>
					</div>
					<div class="row">
						<div class="col-md-6 mb-2">
							<label>Observações</label>
							<input type="text" class="form-control" id="obs" name="obs" placeholder="Observações">
						</div>
						<div class="col-md-4 mb-2">
							<label>Arquivo</label>
							<input type="file" class="form-control" id="arquivo" name="foto" onchange="carregarImg()">
						</div>
						<div class="col-md-2">
							<img width="80px" id="target" src="images/contas/sem-foto.png">
						</div>
					</div>
					<div class="row d-none" id="div-banco">
						<div class="col-md-6 mb-2">
							<label>Banco</label>
							<select class="form-select" name="banco" id="banco">
								<option value="">Selecione</option>
								<?php
								$q = $pdo->query("SELECT id, banco FROM bancos ORDER BY id ASC");
								foreach ($q->fetchAll(PDO::FETCH_ASSOC) as $row) {
									echo '<option value="' . $row['id'] . '">' . $row['banco'] . '</option>';
								}
								?>
							</select>
						</div>
						<div class="col-md-6 mb-2">
							<label>Descrição (Banco)</label>
							<select class="form-select" name="descricao_banco" id="descricao_banco">
								<option value="">Selecione a Classificação</option>
								<?php
								$q = $pdo->query("SELECT id, descricao FROM descricao_banco ORDER BY descricao ASC");
								foreach ($q->fetchAll(PDO::FETCH_ASSOC) as $row) {
									echo '<option value="' . $row['id'] . '">' . htmlspecialchars($row['descricao']) . '</option>';
								}
								?>
							</select>
						</div>
					</div>
					<input type="hidden" id="id" name="id">
					<br>
					<small><div id="mensagem" align="center"></div></small>
				</div>
				<div class="modal-footer">
					<button type="submit" id="btn_salvar" class="btn btn-success">Salvar</button>
				</div>
			</form>
		</div>
	</div>
</div>
