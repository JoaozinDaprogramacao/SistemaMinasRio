<?php
$tabela = 'detalhes_materiais';
require_once("../../../conexao.php");


$cat = @$_POST['p1'];

if ($cat == "") {
	$filtrar = "";
} else {
	$filtrar = " where material_id = '$cat'";
}

$query = $pdo->query("SELECT * from $tabela $filtrar order by id desc");
$res = $query->fetchAll(PDO::FETCH_ASSOC);
$linhas = @count($res);

if ($linhas > 0) {
	echo <<<HTML

	<table class="table table-bordered text-nowrap border-bottom dt-responsive" id="tabela">
	<thead> 
	<tr> 
	<th align="center" width="5%" class="text-center">Selecionar</th>
	<th>Data</th>
	<th>Descrição</th>
	<th>Compra</th>
	<th>Venda</th>
	<th>Preco-R$ Und.</th>
	<th>Valor-R$ Compra</th>
	<th>Valor-R$ Venda</th>
	<th>Saída Estq.</th>
	<th>Entrada Estq.</th>
	<th>Estoque</th>
	<th>Ações</th>
	</tr> 
	</thead> 
	<tbody>	
HTML;

	for ($i = 0; $i < $linhas; $i++) {
		$id = $res[$i]['id'];
		$material_id = $res[$i]['material_id'];
		$descricao = $res[$i]['descricao'];
		$data = $res[$i]['data'];
		$compra = $res[$i]['compra'];
		$venda = $res[$i]['venda'];
		$valor_compra = $res[$i]['valor_compra'];
		$valor_venda = $res[$i]['valor_venda'];
		$preco_unidade = $res[$i]['preco_unidade'];
		$saida_estoque = $res[$i]['saida_estoque'];
		$entrada_estoque = $res[$i]['entrada_estoque'];
		$estoque = $res[$i]['estoque'];


		$query2 = $pdo->query("SELECT * FROM materiais where id = '$material_id'");
		$res2 = $query2->fetchAll(PDO::FETCH_ASSOC);
		if (@count($res2) > 0) {
			$nome_cat = $res2[0]['nome'];
		} else {
			$nome_cat = 'Sem Categoria';
		}



		echo <<<HTML
<tr style="">
<td align="center">
<div class="custom-checkbox custom-control">
<input type="checkbox" class="custom-control-input" id="seletor-{$id}" onchange="selecionar('{$id}')">
<label for="seletor-{$id}" class="custom-control-label mt-1 text-dark"></label>
</div>
</td>
    <td>{$data}</td>
    <td>{$descricao}</td>
    <td>{$compra}</td>
    <td>{$venda}</td>
    <td>{$valor_compra}</td>
    <td>{$valor_venda}</td>
    <td>{$preco_unidade}</td>
    <td>{$saida_estoque}</td>
    <td>{$entrada_estoque}</td>
    <td>{$estoque}</td>

<td>
	<big><a class="btn btn-info btn-sm" href="#" onclick="editar('{$id}','{$descricao}', '{$preco_unidade}')" title="Editar Dados"><i class="fa fa-edit "></i></a></big>

	<div class="dropdown" style="display: inline-block;">                      
                        <a class="btn btn-danger btn-sm" href="#" aria-expanded="false" aria-haspopup="true" data-bs-toggle="dropdown" class="dropdown"><i class="fa fa-trash "></i> </a>
                        <div  class="dropdown-menu tx-13">
                        <div style="width: 240px; padding:15px 5px 0 10px;" class="dropdown-item-text">
                        <p>Confirmar Exclusão? <a href="#" onclick="excluir('{$id}')"><span class="text-danger">Sim</span></a></p>
                        </div>
                        </div>
                        </div>

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
	function editar(id, descricao, preco) {

		$('#mensagem').text('');
		$('#titulo_inserir').text('Editar Registro');

		$('#id').val(id);
		$('#descricao').val(descricao);
		$('#preco_unidade').val(preco);

		$('#modalForm').modal('show');
	}

	function limparCampos() {
		$('#id').val('');
		$('#nome').val('');

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