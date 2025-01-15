<?php
$tabela = 'materiais';
require_once("../../../conexao.php");

$query = $pdo->query("SELECT * from $tabela order by id desc");
$res = $query->fetchAll(PDO::FETCH_ASSOC);
$linhas = @count($res);
if ($linhas > 0) {
	echo <<<HTML

	<table class="table table-bordered text-nowrap border-bottom dt-responsive" id="tabela">
	<thead> 
	<tr> 
	
	<th>Nome</th>	
	<th>Estoque</th>		
	<th>Ações</th>
	</tr> 
	</thead> 
	<tbody>	
HTML;

	for ($i = 0; $i < $linhas; $i++) {
		$id = $res[$i]['id'];
		$nome = $res[$i]['nome'];
		$tem_estoque = $res[$i]['tem_estoque'];
		$estoque = $res[$i]['estoque'];
		$fornecedor = $res[$i]['fornecedor'];
		$estoque_minimo = $res[$i]['estoque_minimo'];

		if ($estoque == "") {
			$estoque == 'Não tem estoque';
		}

		$estoque_minimo_f = "";

		if ($tem_estoque = "Não") {
			if ($estoque < $estoque_minimo) {
				$classe_estoque = 'red';
				$estoque_minimo_f = ' / <span style="color:green">(' . $estoque_minimo . ')</span>';
			} else {
				$classe_estoque = '';
				$estoque_minimo = '';
			}
		}



		echo <<<HTML
<tr style="">

<td>{$nome}</td>
<td style="color:{$classe_estoque}">{$estoque} {$estoque_minimo_f}</td>

<td>


<big><a class="btn btn-primary btn-sm" href="#" onclick="mostrar('{$nome}', '{$estoque}', '{$estoque_minimo}')" title="Mostrar Dados"><i class="fa fa-info-circle "></i></a></big>


<a class="btn btn-danger btn-sm" href="#" onclick="saida('{$id}','{$nome}', '{$estoque}')" title="Saída de Produto"><i class="fa fa-sign-out" style="transform: scaleX(-1); display: inline-block;"></i>
</a>

	<big><a class="btn btn-success btn-sm" href="#" onclick="entrada('{$id}','{$nome}', '{$estoque}')" title="Entrada de Produto"><i class="fa fa-sign-in"></i></a></big>



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
	function mostrar(nome, estoque, estoque_minimo) {

		$('#titulo_dados').text(nome);
		$('#estoque_dados').text(estoque);
		$('#estoque_minimo_dados').text(estoque_minimo);


		$('#modalDados').modal('show');
	}
</script>




<script type="text/javascript">
	function saida(id, nome, estoque) {

		$('#nome_saida').text(nome);
		$('#estoque_saida').val(estoque);
		$('#id_saida').val(id);

		$('#quantidade_saida').val('');
		$('#motivo_saida').val('');

		$('#modalSaida').modal('show');
	}
</script>


<script type="text/javascript">
	function entrada(id, nome, estoque) {

		$('#nome_entrada').text(nome);
		$('#estoque_entrada').val(estoque);
		$('#id_entrada').val(id);

		$('#quantidade_entrada').val('');
		$('#motivo_entrada').val('');

		$('#modalEntrada').modal('show');
	}
</script>