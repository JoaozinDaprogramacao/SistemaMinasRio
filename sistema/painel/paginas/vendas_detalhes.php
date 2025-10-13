<?php
$pag = 'vendas_detalhes';

//verificar se ele tem a permissão de estar nessa página
if (@$vendas_detalhes_perm == 'ocultar') { // Use uma variável de permissão apropriada
	echo "<script>window.location='../index.php'</script>";
	exit();
}
?>

<div class="justify-content-between">
	<div class="left-content mt-2 mb-3">
		<div class="dropdown" style="display: inline-block;">
			<a href="#" aria-expanded="false" aria-haspopup="true" data-bs-toggle="dropdown" class="btn btn-danger dropdown" id="btn-deletar" style="display:none"><i class="fe fe-trash-2"></i> Cancelar Selecionadas</a>
			<div class="dropdown-menu tx-13">
				<div style="width: 240px; padding:15px 5px 0 10px;" class="dropdown-item-text">
					<p>Cancelar Vendas? <a href="#" onclick="deletarSel()"><span class="text-danger">Sim</span></a></p>
				</div>
			</div>
		</div>
	</div>

	<form action="rel/vendas_detalhes_class.php" target="_blank" method="POST">
		<input type="hidden" name="cliente_filtro" id="cliente_filtro">
		<div style="position:absolute; right:10px; margin-bottom: 10px; top:70px">
			<button style="width:40px" type="submit" class="btn btn-danger ocultar_mobile_app" title="Gerar Relatório de Vendas"><i class="fa fa-file-pdf-o"></i></button>
		</div>
	</form>
</div>


<?php
// Busca todos os clientes que já fizeram compras para criar as abas de filtro
$query = $pdo->query("SELECT c.* from clientes c JOIN vendas v ON c.id = v.cliente_id GROUP BY c.id order by c.nome asc");
$res = $query->fetchAll(PDO::FETCH_ASSOC);
$linhas = @count($res);
if ($linhas > 0) {
?>
	<ul class="nav nav-tabs" id="myTab" role="tablist" style="background: #FFF">
		<li class="nav-item" role="presentation">
			<a onclick="buscarCliente('')" class="nav-link active" data-bs-toggle="tab" data-bs-target="#home" type="button" role="tab" aria-controls="home" aria-selected="true">Todas as Vendas</a>
		</li>

		<?php
		for ($i = 0; $i < $linhas; $i++) {
			$id = $res[$i]['id'];
			$nome = $res[$i]['nome'];
		?>
			<li class="nav-item" role="presentation">
				<a onclick="buscarCliente('<?php echo $id ?>')" class="nav-link" data-bs-toggle="tab" data-bs-target="#home" type="button" role="tab" aria-controls="home" aria-selected="true"><?php echo $nome ?></a>
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

<div class="modal fade" id="modalDetalhes" tabindex="-1" aria-labelledby="exampleModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header bg-primary text-white">
                <h4 class="modal-title" id="exampleModalLabel">
                    <i class="fa fa-file-text-o me-2"></i>Detalhes da Venda - #<span id="span_id_venda"></span>
                </h4>
                <button id="btn-fechar-detalhes" aria-label="Close" class="btn-close" data-bs-dismiss="modal" type="button">
                    <span class="text-white" aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body bg-light">
                
                <div class="card shadow-sm mb-4">
                    <div class="card-header bg-white">
                        <h5 class="mb-0">Informações Gerais</h5>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-6 mb-3 mb-md-0">
                                <div class="d-flex align-items-center">
                                    <i class="fa fa-user fa-2x text-primary me-3"></i>
                                    <div>
                                        <small class="text-muted">Cliente</small>
                                        <div class="fs-6 fw-bold" id="detalhes_cliente"></div>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="d-flex align-items-center">
                                    <i class="fa fa-calendar fa-2x text-primary me-3"></i>
                                    <div>
                                        <small class="text-muted">Data da Venda</small>
                                        <div class="fs-6 fw-bold" id="detalhes_data"></div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <hr class="my-3">
                        <div class="row">
                             <div class="col-md-6 mb-3 mb-md-0">
                                <div class="d-flex align-items-center">
                                    <i class="fa fa-briefcase fa-2x text-primary me-3"></i>
                                    <div>
                                        <small class="text-muted">Vendedor</small>
                                        <div class="fs-6 fw-bold" id="detalhes_vendedor"></div>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="d-flex align-items-center">
                                    <i class="fa fa-check-circle fa-2x text-primary me-3"></i>
                                    <div>
                                        <small class="text-muted">Status do Pagamento</small>
                                        <div id="detalhes_status"></div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="card shadow-sm mb-4">
                     <div class="card-header bg-white">
                        <h5 class="mb-0">Itens da Venda</h5>
                    </div>
                    <div class="card-body p-0">
                         <div id="listar-itens" class="table-responsive">
                            </div>
                    </div>
                </div>

                <div class="card shadow-sm">
                     <div class="card-header bg-white">
                        <h5 class="mb-0">Resumo Financeiro</h5>
                    </div>
                    <div class="card-body">
                        <dl class="row mb-0">
                            <dt class="col-sm-6 text-muted">Subtotal:</dt>
                            <dd class="col-sm-6 text-end" id="detalhes_subtotal"></dd>

                            <dt class="col-sm-6 text-muted">Desconto:</dt>
                            <dd class="col-sm-6 text-end" id="detalhes_desconto"></dd>

                            <dt class="col-sm-6 text-muted">Frete:</dt>
                            <dd class="col-sm-6 text-end" id="detalhes_frete"></dd>
                        </dl>
                        <hr class="my-2">
                        <dl class="row mb-0">
                            <dt class="col-sm-6 fs-5">Valor Total:</dt>
                            <dd class="col-sm-6 text-end fs-5 fw-bold" id="detalhes_total"></dd>

                            <dt class="col-sm-6 text-muted">Valor Pago:</dt>
                            <dd class="col-sm-6 text-end text-success" id="detalhes_pago"></dd>

                            <dt class="col-sm-6 text-muted">Valor Restante:</dt>
                            <dd class="col-sm-6 text-end fw-bold text-danger" id="detalhes_restante"></dd>
                        </dl>
                    </div>
                </div>

            </div>
            <div class="modal-footer">
                <a href="#" id="btn_imprimir" target="_blank" class="btn btn-dark"><i class="fa fa-print me-2"></i>Imprimir</a>
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Fechar</button>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="modalPagamento" tabindex="-1" role="dialog" aria-hidden="true">
	<div class="modal-dialog" role="document">
		<div class="modal-content">
			<div class="modal-header bg-primary text-white">
				<h4 class="modal-title">Adicionar Pagamento à Venda <span id="id_venda_pagamento"></span></h4>
				<button id="btn-fechar-pagamento" aria-label="Close" class="btn-close" data-bs-dismiss="modal" type="button"><span class="text-white" aria-hidden="true">&times;</span></button>
			</div>
			<div class="modal-body">
				<form id="form-pagamento">
					<div class="row">
						<div class="col-md-6">
							<label>Valor a Pagar</label>
							<input type="text" class="form-control" id="valor_pagamento" name="valor_pagamento" placeholder="R$ 0,00" required>
						</div>
						<div class="col-md-6">
							<label>Forma de Pgto</label>
							<select class="form-select" name="forma_pgto" required>
								<option value="">Selecione</option>
								<?php
								$query_pgto = $pdo->query("SELECT * from formas_pgto order by nome asc");
								$res_pgto = $query_pgto->fetchAll(PDO::FETCH_ASSOC);
								foreach($res_pgto as $pgto) {
									echo "<option value='{$pgto['id']}'>{$pgto['nome']}</option>";
								}
								?>
							</select>
						</div>
					</div>
					<div class="row mt-2">
						<div class="col-md-12">
							<label>Observação (Opcional)</label>
							<input type="text" class="form-control" name="obs" placeholder="Ex: Pagamento da segunda parcela">
						</div>
					</div>
					<input type="hidden" id="id_pagamento" name="id_venda">
					<input type="hidden" id="valor_restante_pagamento" name="valor_restante">
					<div class="modal-footer">
						<button type="submit" class="btn btn-primary">Salvar Pagamento</button>
					</div>
				</form>
				<br>
				<small><div id="mensagem-pagamento" align="center"></div></small>
			</div>
		</div>
	</div>
</div>

<div class="modal fade" id="modalEditar" tabindex="-1" role="dialog" aria-hidden="true">
	<div class="modal-dialog modal-lg" role="document">
		<div class="modal-content">
			<div class="modal-header bg-primary text-white">
				<h4 class="modal-title">Editar Venda <span id="id_venda_editar"></span></h4>
				<button id="btn-fechar-editar" aria-label="Close" class="btn-close" data-bs-dismiss="modal" type="button"><span class="text-white" aria-hidden="true">&times;</span></button>
			</div>
			<div class="modal-body">
				<form id="form-editar">
					<div class="alert alert-info">A funcionalidade de edição de vendas e seus itens pode ser desenvolvida aqui, seguindo o padrão de formulário.</div>
					
					<input type="hidden" id="id_editar" name="id">
					
					<div class="modal-footer">
						<button type="submit" class="btn btn-primary">Salvar Alterações</button>
					</div>
				</form>
				<br>
				<small><div id="mensagem-editar" align="center"></div></small>
			</div>
		</div>
	</div>
</div>

<style>
	/* ESTILOS COPIADOS DO SEU ARQUIVO DE REFERÊNCIA PARA MANTER O PADRÃO */
	.radio { display: flex !important; align-items: center; justify-content: center; padding: 10px; gap: 15px; }
	.radio-group { display: flex; justify-content: space-between; margin-bottom: 15px; }
	.radio-group label { font-size: 14px; display: flex; align-items: center; cursor: pointer; gap: 8px; }
	input[type="radio"] { accent-color: #007bff; width: 18px; height: 18px; }
	input[type="text"], input[type="number"] { width: 100%; padding: 10px; margin-bottom: 10px; border: 1px solid #ccc; border-radius: 5px; font-size: 14px; transition: border-color 0.3s; }
	input[type="text"]:focus, input[type="number"]:focus { border-color: #007bff; outline: none; box-shadow: 0 0 5px rgba(0, 123, 255, 0.5); }
	.final .resumo-celula { background-color: rgb(102, 160, 64) !important; }
	.danger { color: red !important; }
	.linha_1, .linha_2, .linha_3 { display: flex; flex-direction: column; gap: 20px; padding: 10px; background-color: #f9f9f9; border: 1px solid #e0e0e0; border-radius: 8px; margin: 10px; }
	.container-superior { display: flex; align-items: center; justify-content: center; margin-top: 15px; margin-bottom: 15px; }
	.linha-superior { display: grid; grid-template-columns: repeat(2, 1fr); gap: 15px; width: 50%; }
	.linha-inferior { display: grid; grid-template-columns: repeat(6, 1fr); gap: 15px; margin: auto; }
	.coluna_romaneio label { font-size: 12px; font-weight: bold; color: #6c757d; margin-bottom: 5px; display: block; }
	.coluna_romaneio input, .coluna_romaneio select { width: 100%; padding: 8px; border: 1px solid #ced4da; border-radius: 5px; font-size: 14px; color: #495057; background-color: #ffffff; box-shadow: inset 0 1px 2px rgba(0, 0, 0, 0.075); transition: border-color 0.3s ease, box-shadow 0.3s ease; }
	.coluna_romaneio input:focus, .coluna_romaneio select:focus { border-color: #007bff; box-shadow: 0 0 5px rgba(0, 123, 255, 0.5); outline: none; }
	.resumo-tabela { display: table; width: 100%; border-collapse: collapse; background-color: #f8f9fa; border: 1px solid black; box-shadow: 0 0 10px rgba(0, 0, 0, 0.1); }
	.resumo-linha { display: table-row; }
	.resumo-celula { display: table-cell; padding: 10px; border: 1px solid #dee2e6; font-size: 14px; text-align: left; font-weight: bold; vertical-align: middle; background-color: #c5e0b3; color: #212529; }
	.input { display: flex; justify-content: space-between; align-items: center; }
	.input label { font-size: 14px; font-weight: bold; }
	.resumo-celula input { width: 20%; padding: 5px; border: 1px solid #ced4da; border-radius: 4px; font-size: 14px; color: #495057; background-color: yellow; }
	.resumo-celula input:focus { border-color: #007bff; box-shadow: 0 0 5px rgba(0, 123, 255, 0.5); outline: none; }
</style>


<script type="text/javascript">
	var pag = "<?= $pag ?>";
</script>
<script src="js/ajax.js"></script>

<script type="text/javascript">
	$(document).ready(function() {
		// Inicializa o select2 se houver algum nos modais
		$('.sel2').select2({
			dropdownParent: $('#modalForm') // Ajustar se usar em outros modais
		});
	});

	// Função para filtrar a lista de vendas por cliente
	function buscarCliente(id_cliente) {
		// Atualiza o valor do campo oculto para o relatório PDF
		$('#cliente_filtro').val(id_cliente);
		// Chama a função principal de listagem, passando o filtro
		listar(id_cliente);
	}
</script>

<script type="text/javascript">
	// SUBMISSÃO DO FORMULÁRIO DE PAGAMENTO
	$("#form-pagamento").submit(function(event) {
		event.preventDefault();
		var formData = new FormData(this);

		$('#mensagem-pagamento').text('Salvando...');

		$.ajax({
			url: 'paginas/' + pag + "/pagamento.php",
			type: 'POST',
			data: formData,
			success: function(mensagem) {
				$('#mensagem-pagamento').text('');
				if (mensagem.trim() == "Salvo com Sucesso") {
					$('#btn-fechar-pagamento').click();
					listar(); // Atualiza a lista principal
				} else {
					$('#mensagem-pagamento').addClass('text-danger');
					$('#mensagem-pagamento').text(mensagem);
				}
			},
			cache: false,
			contentType: false,
			processData: false,
		});
	});

	// SUBMISSÃO DO FORMULÁRIO DE EDIÇÃO (EXEMPLO)
	$("#form-editar").submit(function(event) {
		event.preventDefault();
		var formData = new FormData(this);

		$('#mensagem-editar').text('Salvando...');

		$.ajax({
			url: 'paginas/' + pag + "/editar.php",
			type: 'POST',
			data: formData,
			success: function(mensagem) {
				$('#mensagem-editar').text('');
				if (mensagem.trim() == "Salvo com Sucesso") {
					$('#btn-fechar-editar').click();
					listar();
				} else {
					$('#mensagem-editar').addClass('text-danger');
					$('#mensagem-editar').text(mensagem);
				}
			},
			cache: false,
			contentType: false,
			processData: false,
		});
	});
</script>