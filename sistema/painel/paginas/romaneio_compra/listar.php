<?php
$tabela = 'romaneio_compra';
require_once("../../../conexao.php");

// Variáveis de filtro
$dataInicial = @$_POST['p1'];
$dataFinal = @$_POST['p2'];
$fornecedor = @$_POST['p3'];

// Inicializa a cláusula WHERE
$where = [];
$params = [];

// Adiciona filtro de data
if (!empty($dataInicial) && !empty($dataFinal)) {
	$where[] = "data >= :dataInicial AND data <= :dataFinal";
	$params[':dataInicial'] = $dataInicial;
	$params[':dataFinal'] = $dataFinal;
}

// Adiciona filtro de fornecedor
if (!empty($fornecedor)) {
	$where[] = "fornecedor = :fornecedor";
	$params[':fornecedor'] = $fornecedor;
}

// Combina as condições do WHERE
$filtrar = '';
if (count($where) > 0) {
	$filtrar = ' WHERE ' . implode(' AND ', $where);
}

// Consulta SQL com filtros
$query = $pdo->prepare("SELECT rc.*, f.nome_atacadista as nome_fornecedor 
	FROM $tabela rc
	LEFT JOIN fornecedores f ON rc.fornecedor = f.id" . 
	$filtrar . " ORDER BY rc.id DESC");

$query->execute($params);
$res = $query->fetchAll(PDO::FETCH_ASSOC);
$linhas = @count($res);
if ($linhas > 0) {
	echo <<<HTML

	<table class="table table-bordered text-nowrap border-bottom dt-responsive" id="tabela">
	<thead> 
	<tr> 
	<th align="center" width="5%" class="text-center">Selecionar</th>
	<th>Room N°</th>	
	<th>Fornecedor</th>	
	<th>Data</th>	
	<th>Ações</th>
	</tr> 
	</thead> 
	<tbody>	
HTML;

	for ($i = 0; $i < $linhas; $i++) {
		$id = $res[$i]['id'];
		$fornecedor = $res[$i]['fornecedor'];
		$data = $res[$i]['data'];

		$dataF = implode('/', array_reverse(@explode('-', $data)));

		// Consulta para pegar o nome do fornecedor
		$query_nome_fornecedor = $pdo->query("SELECT nome_atacadista FROM fornecedores WHERE id = '$fornecedor'");

		// Fetch o resultado da consulta
		$fornecedor_nome_array = $query_nome_fornecedor->fetch(PDO::FETCH_ASSOC);

		// Verifique se o resultado foi encontrado e extraia o nome do fornecedor
		if ($fornecedor_nome_array) {
			$fornecedor_nome = $fornecedor_nome_array['nome_atacadista'];
		} else {
			// Caso o fornecedor não seja encontrado
			$fornecedor_nome = "Fornecedor não encontrado";
		}

		echo <<<HTML
<tr style="">
<td align="center">
<div class="custom-checkbox custom-control">
<input type="checkbox" class="custom-control-input" id="seletor-{$id}" onchange="selecionar('{$id}')">
<label for="seletor-{$id}" class="custom-control-label mt-1 text-dark"></label>
</div>
</td>
<td>{$id}</td>
<td>{$fornecedor_nome}</td>
<td>{$data}</td>

<td>
	<big><a class="btn btn-info btn-sm" href="#" onclick="editar('{$id}','{$fornecedor}','{$dataF}')" title="Editar Dados"><i class="fa fa-edit "></i></a></big>

	<div class="dropdown" style="display: inline-block;">                      
                        <a class="btn btn-danger btn-sm" href="#" aria-expanded="false" aria-haspopup="true" data-bs-toggle="dropdown" class="dropdown"><i class="fa fa-trash "></i> </a>
                        <div  class="dropdown-menu tx-13">
                        <div style="width: 240px; padding:15px 5px 0 10px;" class="dropdown-item-text">
                        <p>Confirmar Exclusão? <a href="#" onclick="excluir('{$id}')"><span class="text-danger">Sim</span></a></p>
                        </div>
                        </div>
                        </div>


<button type="button" class="btn btn-info btn-sm" onclick="mostrar('{$id}')" title="Ver Dados">
    <i class="bi bi-info-circle"></i>
</button>
</td>
</tr>
HTML;
	}
} else {
	echo 'Não possui nenhum cadastro!';
}


echo <<<HTML
</tbody>
<small><div align="center" id="mensagem-excluir"></div></small>
</table>

<br>		
			<p align="right" style="margin-top: -10px">
				<span style="margin-right: 10px">Total Itens  <span > {$linhas} </span></span>
				
			</p>

HTML;
?>



<script type="text/javascript">
	$(document).ready(function() {
		$('#tabela').DataTable({
			"language": {
				//"url" : '//cdn.datatables.net/plug-ins/1.13.2/i18n/pt-BR.json'
			},
			"ordering": false,
			"stateSave": true
		});
	});
</script>

<script type="text/javascript">
	function editar(id, fornecedor, data) {
		$('#mensagem').text('');
		$('#titulo_inserir').text('Editar Registro');

		$('#id').val(id);
		$('#fornecedor').val(fornecedor);
		$('#data').val(data)

		$('#modalForm').modal('show');
	}

	function formatarData(data) {
		if (!data) return '-';
		return new Date(data).toLocaleDateString('pt-BR');
	}

	function formatarNumero(valor) {
		if (!valor) return '0,00';
		return parseFloat(valor).toFixed(2).replace('.', ',');
	}

	function mostrar(id) {
		$.ajax({
			url: 'paginas/romaneio_compra/buscar_dados.php',
			method: 'POST',
			data: {id: id},
			dataType: 'json',
			success: function(dados) {
				// Preencher dados do cabeçalho
				$('#fornecedor_modal').text(dados.romaneio.nome_fornecedor);
				$('#data_modal').text(formatarData(dados.romaneio.data));
				$('#nota_modal').text(dados.romaneio.nota_fiscal);
				$('#plano_modal').text(dados.romaneio.nome_plano);
				$('#vencimento_modal').text(formatarData(dados.romaneio.vencimento));
				$('#quant_dias_modal').text(dados.romaneio.quant_dias);
				
				// Preencher tabela de produtos
				let htmlProdutos = '';
				dados.produtos.forEach(function(item) {
					htmlProdutos += `<tr>
						<td>${item.nome_produto || '-'}</td>
						<td>${item.tipo_caixa || '-'}</td>
						<td>${item.quant || '0'}</td>
						<td>R$ ${formatarNumero(item.preco_kg)}</td>
						<td>R$ ${formatarNumero(item.preco_unit)}</td>
						<td>R$ ${formatarNumero(item.valor)}</td>
					</tr>`;
				});
				$('#produtos_modal').html(htmlProdutos);

				// Preencher valores totais
				$('#desc_funrural_modal').text('R$ ' + formatarNumero(dados.romaneio.desc_funrural));
				$('#desc_ima_aban_modal').text('R$ ' + formatarNumero(dados.romaneio.desc_ima_aban));
				$('#total_liquido_modal').text('R$ ' + formatarNumero(dados.romaneio.total_liquido));

				// Exibir o modal
				$('#modalMostrarDados').modal('show');
			}
		});
	}

	function limparCampos() {
		$('#id').val('');
		$('#fornecedor').val('');
		$('#data').val('00-00-00');

		$('#ids').val('');
		$('#btn-deletar').hide();
	}

	function selecionar(id) {
		var ids = $('#ids').val();

		if ($('#seletor-' + id).is(":checked") == true) {
			var novo_id = ids + id + '-';
			$('#ids').val(novo_id);
		} else {
			var retirar = ids.replace(id + '-', '');
			$('#ids').val(retirar);
		}

		var ids_final = $('#ids').val();
		if (ids_final == "") {
			$('#btn-deletar').hide();
		} else {
			$('#btn-deletar').show();
		}
	}

	function deletarSel() {
		var ids = $('#ids').val();
		var id = ids.split("-");

		for (i = 0; i < id.length - 1; i++) {
			excluirMultiplos(id[i]);
		}

		setTimeout(() => {
			listar();
		}, 1000);

		limparCampos();
	}
</script>


</script>