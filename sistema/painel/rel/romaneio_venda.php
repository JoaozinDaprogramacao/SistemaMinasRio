<?php 
@session_start();
$mostrar_registros = @$_SESSION['registros'];
$id_usuario = @$_SESSION['id'];
require_once("../../conexao.php");
require_once("data_formatada.php");

$token_rel = @$_GET['token'];
if($token_rel != 'A5030'){
echo '<script>window.location="../../"</script>';
exit();
}

?>

<!DOCTYPE html>
<html>
<head>

<style>

@import url('https://fonts.cdnfonts.com/css/tw-cen-mt-condensed');
@page { margin: 145px 20px 25px 20px; }
#header { position: fixed; left: 0px; top: -110px; bottom: 100px; right: 0px; height: 35px; text-align: center; padding-bottom: 100px; }
#content {margin-top: 0px;}
#footer { position: fixed; left: 0px; bottom: -60px; right: 0px; height: 80px; }
#footer .page:after {content: counter(page, my-sec-counter);}
body {font-family: 'Tw Cen MT', sans-serif;}

.marca{
	position:fixed;
	left:50;
	top:100;
	width:80%;
	opacity:8%;
}

</style>

</head>
<body>
<?php 
if($marca_dagua == 'Sim'){ ?>
<img class="marca" src="<?php echo $url_sistema ?>img/logo.jpg">	
<?php } ?>

<div id="header" >

	<div style="border-style: solid; font-size: 10px; height: 50px;">
		<table style="width: 100%; border: 0px solid #ccc;">
			<tr>
				<td style="border: 1px; solid #000; width: 7%; text-align: left;">
					<img style="margin-top: 7px; margin-left: 7px;" id="imag" src="<?php echo $url_sistema ?>img/logo.jpg" width="110px">
				</td>
				<td style="width: 30%; text-align: left; font-size: 13px;">
					
				</td>
				<td style="width: 1%; text-align: center; font-size: 13px;">
				
				</td>
				<td style="width: 47%; text-align: right; font-size: 9px;padding-right: 10px;">
						<b><big>RELATÓRIO DE CONTAS À RECEBER <?php echo $texto_pago ?></big></b>
							<br>FILTRO: <?php echo mb_strtoupper($texto_filtro) ?> 
							<br> <?php echo mb_strtoupper($data_hoje) ?>
				</td>
			</tr>		
		</table>
	</div>

<br>

<div class="modal fade" id="modalForm" tabindex="-1" aria-labelledby="exampleModalLabel" aria-hidden="true">
	<div class="modal-dialog modal-lg">
		<div class="modal-content">
			<div class="modal-header bg-primary text-white">
				<h4 class="modal-title" id="exampleModalLabel"><span id="titulo_inserir"></span></h4>
				<button id="btn-fechar" aria-label="Close" class="btn-close" data-bs-dismiss="modal" type="button"><span class="text-white" aria-hidden="true">&times;</span></button>
			</div>
			<h3 class="fs-3 text-center mt-3">Romaneio de Vendas</h3>
			<form id="form_itens">
				<div class="container-superior">
					<div class="linha-superior">
						<div class="coluna_romaneio">
							<label for="data_atual">Data</label>
							<input type="date" class="data_atual" name="data" value="<?= date('Y-m-d'); ?>">
						</div>
						<div class="coluna_romaneio">
							<label for="plano_pgto">Plano Pgto</label>
							<select id="plano_pgto" name="plano_pgto" class="plano_pgto" onchange="(calculaTotais())">
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
						<div class="coluna_romaneio">
							<label for="quant_dias">Dias</label>
							<input type="number" class="quant_dias" name="quant_dias" placeholder="Quant. Dias">
						</div>
						<div class="coluna_romaneio">
							<label for="nota_fiscal">Nota Fiscal</label>
							<input type="text" class="nota_fiscal" name="nota_fiscal" placeholder="Número NF">
						</div>
						<div class="coluna_romaneio">
							<label for="vencimento">Vencimento</label>
							<input type="date" class="data_atual" name="vencimento" value="<?= date('Y-m-d'); ?>">
						</div>
						<div class="coluna_romaneio">
							<label for="cliente">Cliente Atacadista</label>
							<select id="cliente" name="cliente" class="cliente" onchange="(calculaTotais())">
								<option value="0">Escolher Atacadista</option>
								<?php
								$query = $pdo->query("SELECT * from clientes order by id asc");
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
				<div id="linha-template_1" class="linha_1" style="display: none;">
					<!-- Bloco Superior (2x2) -->

					<!-- Bloco Inferior (em linha) -->
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
								$query = $pdo->query("SELECT * from produtos order by id asc");
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
							<label for="preco_kg_1">Preço KG</label>
							<input type="text" class="preco_kg_1" id="preco_kg_1" name="preco_kg_1[]" onkeyup="mascara_decimal('preco_kg_1'); handleInput(this); calcularValores(this.closest('.linha_1'));">
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
					<!-- Bloco Superior (2x2) -->

					<!-- Bloco Inferior (em linha) -->
					<div class="linha-inferior">
						<div class="coluna_romaneio">
							<label for="desc_2">Descrição</label>
							<select name="desc_2[]" class="desc_2" onchange="handleInput2(this); calcularValores2(this.closest('.linha_2'));">
								<option value="">Selecione Descrição</option>
								<?php
								$query = $pdo->query("SELECT * from descricao order by id asc");
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
							<input type="text" class="preco_kg_2" name="preco_kg_2[]" onkeyup="mascara_decimal('preco_kg_2'); handleInput2(this); calcularValores2(this.closest('.linha_2'));">
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
									for ($i = 0; $i < $linhas; $i++) { ?>
										<option value="<?php echo $res[$i]['tipo'] ?>"><?php echo $res[$i]['tipo'] ?></option>
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
					<!-- Bloco Superior (2x2) -->

					<!-- Bloco Inferior (em linha) -->
					<div class="linha-inferior" style="grid-template-columns: repeat(5, 1fr);">
						<div class="coluna_romaneio">
							<label for="obs_3">Observação</label>
							<input type="text" name="obs_3[]" class="obs_3" onchange="handleInput3(this); calcularValores3(this.closest('.linha_3'));">

						</div>
						<div class="coluna_romaneio">
							<label for="material">Descrição</label>
							<select name="material[]"  class="material" onchange="handleInput(this); calcularValores(this.closest('.linha_3'));">
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
							<input type="text" class="preco_unit_3" name="preco_unit_3[]" onkeyup="handleInput3(this); calcularValores3(this.closest('.linha_3'));">
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
        <!-- Linha 1: Adicional -->
        <div style="display: flex; gap: 10px; align-items: center;">
            <label>
                <input type="checkbox" name="adicional_ativo" id="adicional_ativo">
                Adicional
            </label>
            <input type="text" placeholder="Descrição do Adicional" name="descricao_adicional" id="descricao_adicional">
            <input type="text" placeholder="Valor do Adicional" name="valor_adicional" id="valor_adicional" step="0.01"
                onkeyup="mascara_decimal('valor_adicional'); calcularValorDA();">
        </div>

        <!-- Quebra de linha -->
        <br>

        <!-- Linha 2: Desconto -->
        <div style="display: flex; gap: 10px; align-items: center;">
            <label>
                <input type="checkbox" name="desconto_ativo" id="desconto_ativo">
                Desconto
            </label>
            <input type="text" placeholder="Descrição do Desconto" name="descricao_desconto" id="descricao_desconto">
            <input type="text" placeholder="Valor do Desconto" name="valor_desconto" id="valor_desconto" step="0.01"
                onkeyup="mascara_decimal('valor_desconto'); calcularValorDA();">
        </div>
    </div>
</div>

<!-- Valor líquido a receber -->
<div class="resumo-linha">
    <div class="resumo-celula">VALOR LÍQUIDO A RECEBER</div>
    <div class="resumo-celula" style="display: flex; gap: 5px;">R$ <p id="total_liquido">0,00</p></div>
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
