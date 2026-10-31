<?php
$tabela = 'materiais';
require_once("../../../conexao.php");
require_once("../../verificar.php");

$query = $pdo->query("SELECT * from $tabela order by id desc");
$res = $query->fetchAll(PDO::FETCH_ASSOC);
$linhas = @count($res);
if ($linhas > 0) {
	echo <<<HTML

	<table class="table table-bordered text-nowrap border-bottom dt-responsive" id="tabela">
	<thead>
	<tr>
	<th align="center" width="5%" class="text-center">Selecionar</th>
	<th>Nome</th>
	<th class="text-end">Estoque</th>
	<th class="text-end">Estoque Mínimo</th>
	<th>Controla Estoque</th>
	<th>Ações</th>
	</tr>
	</thead>
	<tbody>
HTML;

	for ($i = 0; $i < $linhas; $i++) {
		$id = (int) $res[$i]['id'];
		$nome = htmlspecialchars($res[$i]['nome'], ENT_QUOTES);
		$estoque = (int) $res[$i]['estoque'];
		$estoque_minimo = (int) $res[$i]['estoque_minimo'];
		$tem_estoque = $res[$i]['tem_estoque'] == 'Sim' ? 'Sim' : 'Não';

		$estoqueF = number_format($estoque, 0, ',', '.');
		$estoque_minimoF = number_format($estoque_minimo, 0, ',', '.');

		// Abaixo do mínimo só importa para material com estoque controlado
		$classe_estoque = ($tem_estoque == 'Sim' and $estoque < $estoque_minimo) ? 'text-danger fw-bold' : '';

		echo <<<HTML
<tr style="">
<td align="center">
<div class="custom-checkbox custom-control">
<input type="checkbox" class="custom-control-input" id="seletor-{$id}" onchange="selecionar('{$id}')">
<label for="seletor-{$id}" class="custom-control-label mt-1 text-dark"></label>
</div>
</td>
<td>{$nome}</td>
<td class="text-end {$classe_estoque}">{$estoqueF}</td>
<td class="text-end">{$estoque_minimoF}</td>
<td>{$tem_estoque}</td>

<td>
	<big><a class="btn btn-info btn-sm" href="#" onclick="editar(this)" data-id="{$id}" data-nome="{$nome}" data-estoque="{$estoque}" data-estoque_minimo="{$estoque_minimo}" data-tem_estoque="{$tem_estoque}" title="Editar Dados"><i class="fa fa-edit "></i></a></big>

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
	// Os dados vêm dos atributos data-* do botão: nome com apóstrofo não quebra o onclick
	function editar(botao) {
		var b = $(botao);

		$('#mensagem').text('');
		$('#titulo_inserir').text('Editar Registro');

		$('#id').val(b.attr('data-id'));
		$('#nome').val(b.attr('data-nome'));
		$('#estoque').val(b.attr('data-estoque'));
		$('#estoque_minimo').val(b.attr('data-estoque_minimo'));
		$('#tem_estoque').val(b.attr('data-tem_estoque'));

		$('#modalForm').modal('show');
	}

	function limparCampos() {
		$('#id').val('');
		$('#nome').val('');
		$('#estoque').val('');
		$('#estoque_minimo').val('');
		$('#tem_estoque').val('Sim');

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
