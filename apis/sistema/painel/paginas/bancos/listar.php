<?php
@session_start();
$mostrar_registros = @$_SESSION['registros'];
$id_usuario = @$_SESSION['id'];
$tabela = 'bancos';
require_once("../../../conexao.php");
require_once("../../verificar.php");

if ($mostrar_registros == 'Não') {
	$sql_usuario_lanc = " WHERE usuario_lanc = '$id_usuario '";
} else {
	$sql_usuario_lanc = " ";
}


$query = $pdo->query("SELECT * from $tabela $sql_usuario_lanc order by id desc");



$res = $query->fetchAll(PDO::FETCH_ASSOC);
$linhas = @count($res);
if ($linhas > 0) {
	echo <<<HTML
<small>
	<table class="table table-bordered text-nowrap border-bottom dt-responsive " id="tabela">
	<thead> 
	<tr> 
	<th align="center" width="5%" class="text-center">Selecionar</th>
	<th>Descrição</th>	
	<th>Ações</th>
	</tr> 
	</thead> 
	<tbody>	
	<small>
HTML;


	for ($i = 0; $i < $linhas; $i++) {
		$id = $res[$i]['id'];
		$descricao = $res[$i]['descricao'];

		echo <<<HTML

<tr>
	<td align="center">
	<div class="custom-checkbox custom-control">
	<input type="checkbox" class="custom-control-input" id="seletor-{$id}" onchange="selecionar('{$id}')">
	<label for="seletor-{$id}" class="custom-control-label mt-1 text-dark"></label>
	</div>
	</td>
	<td><i class="fa fa-square mr-1"></i> {$descricao}</td>

	<td>
		<big><a class="icones_mobile" href="#" onclick="editar('{$id}','{$descricao}')" title="Editar Dados"><i class="fa fa-edit text-primary"></i></a></big>

		<div class="icones_mobile" class="dropdown" style="display: inline-block;">                      
							<a href="#" aria-expanded="false" aria-haspopup="true" data-bs-toggle="dropdown" class="dropdown"><i class="fa fa-trash text-danger"></i> </a>
							<div  class="dropdown-menu tx-13">
							<div class="dropdown-item-text botao_excluir">
							<p>Confirmar Exclusão? <a href="#" onclick="excluir('{$id}')"><span class="text-danger">Sim</span></a></p>
							</div>
							</div>
							</div>


	</td>
</tr>
HTML;
	}


	echo <<<HTML
</small>
</tbody>
<small><div align="center" id="mensagem-excluir"></div></small>

</table>
</small>
HTML;
} else {
	echo 'Nenhum Registro Encontrado!';
}
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


		$('#total_itens').text('R$ <?= $total_valorF ?>');
		$('#total_total').text('R$ <?= $total_totalF ?>');
		$('#total_vencidas').text('R$ <?= $total_vencidasF ?>');
		$('#total_hoje').text('R$ <?= $total_hojeF ?>');
		$('#total_amanha').text('R$ <?= $total_amanhaF ?>');
		$('#total_recebidas').text('R$ <?= $total_recebidasF ?>');
	});
</script>


<script type="text/javascript">
	function editar(id, descricao) {
		$('#mensagem').text('');
		$('#titulo_inserir').text('Editar Registro');

		$('#id').val(id);
		$('#descricao').val(descricao);


		$('#modalForm').modal('show');
	}


	function mostrar(descricao) {

		$('#titulo_dados').text(descricao);
		$('#modalDados').modal('show');

	}

	function limparCampos() {
		$('#id').val('');
		$('#descricao').val('');
	
		
		$('#ids').val('');
		$('#btn-deletar').hide();
		$('#btn-baixar').hide();
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
			$('#btn-baixar').hide();
		} else {
			$('#btn-deletar').show();
			$('#btn-baixar').show();
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


	function deletarSelBaixar() {
		var ids = $('#ids').val();
		var id = ids.split("-");

		for (i = 0; i < id.length - 1; i++) {
			var novo_id = id[i];
			$.ajax({
				url: 'paginas/' + pag + "/baixar_multiplas.php",
				method: 'POST',
				data: {
					novo_id
				},
				dataType: "html",

				success: function(result) {
					//alert(result)

				}
			});
		}

		setTimeout(() => {
			buscar();
			limparCampos();
		}, 1000);


	}


	function permissoes(id, nome) {

		$('#id_permissoes').val(id);
		$('#nome_permissoes').text(nome);

		$('#modalPermissoes').modal('show');
		listarPermissoes(id);
	}


	function baixar(id, valor, descricao, pgto, taxa, multa, juros) {
		$('#id-baixar').val(id);
		$('#descricao-baixar').text(descricao);
		$('#valor-baixar').val(valor);
		$('#saida-baixar').val(pgto).change();
		$('#subtotal').val(valor);


		$('#valor-juros').val(juros);
		$('#valor-desconto').val('');
		$('#valor-multa').val(multa);
		$('#valor-taxa').val(taxa);

		totalizar()

		$('#modalBaixar').modal('show');
		$('#mensagem-baixar').text('');
	}


	function mostrarResiduos(id) {

		$.ajax({
			url: 'paginas/' + pag + "/listar-residuos.php",
			method: 'POST',
			data: {
				id
			},
			dataType: "html",

			success: function(result) {
				$("#listar-residuos").html(result);
			}
		});
		$('#modalResiduos').modal('show');


	}

	function arquivo(id, nome) {
		$('#id-arquivo').val(id);
		$('#nome-arquivo').text(nome);
		$('#modalArquivos').modal('show');
		$('#mensagem-arquivo').text('');
		$('#arquivo_conta').val('');
		listarArquivos();
	}


	function cobrar(id) {
		$.ajax({
			url: 'paginas/' + pag + "/cobrar.php",
			method: 'POST',
			data: {
				id
			},
			dataType: "html",

			success: function(result) {
				alert(result);
			}
		});
	}
</script>