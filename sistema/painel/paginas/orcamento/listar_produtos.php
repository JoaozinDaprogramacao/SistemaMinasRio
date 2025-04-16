<?php 
require_once("../../../conexao.php");
$pagina = 'produtos';

$categoria = @$_POST['categoria'];

echo '<select class="sel2" name="produto" id="produto" style="width:100%;" onchange="mudarProduto()">';

if($categoria == ""){
	$query = $pdo->query("SELECT * from categorias where ativo = 'Sim' order by nome asc limit 1");
	$res = $query->fetchAll(PDO::FETCH_ASSOC);
	$categoria = $res[0]['id'];
}
$query = $pdo->query("SELECT * FROM produtos where categoria = '$categoria' and ativo = 'Sim' order by nome asc");
$res = $query->fetchAll(PDO::FETCH_ASSOC);
for($i=0; $i < @count($res); $i++){
	foreach ($res[$i] as $key => $value){}
	$id_produto = $res[$i]['id'];
	$valor_venda = $res[$i]['valor_venda'];
	$estoque = $res[$i]['estoque'];
	$unidade = $res[$i]['unidade'];
	$nome = $res[$i]['nome'];

	$valor_vendaF = number_format($valor_venda, 2, ',', '.'); 

		$query3 = $pdo->query("SELECT * FROM unidade_medida where id = '$unidade'");
	$res3 = $query3->fetchAll(PDO::FETCH_ASSOC);
	if(@count($res3) > 0){
		$nome_unidade = $res3[0]['nome'];
	}else{
		$nome_unidade = 'Sem Unidade';
	}


	$sigla_unidade = ' Itens';
	$estoque_unit = '';
	if($nome_unidade == 'Quilogramas' or $nome_unidade == 'Quilo' or $nome_unidade == 'Quilograma' or $nome_unidade == 'KG'){
		$sigla_unidade = ' (KG)';
		$estoque_unit = 'Não';
	}

	if($nome_unidade == 'Metros' or $nome_unidade == 'Metro' or $nome_unidade == 'M' or $nome_unidade == 'm'){
		$sigla_unidade = ' (m)';
		$estoque_unit = 'Não';
	}

	if($nome_unidade == 'Litro' or $nome_unidade == 'Litros' or $nome_unidade == 'L'){
		$sigla_unidade = ' (L)';
		$estoque_unit = 'Não';
	}


	//tratamento separa string
	$est = explode(".", $estoque);
	if($est[1] > 0){
		$estoqueF = $estoque;		
	}else{
		$estoqueF = $est[0];
	}

	echo '<option value="'.$res[$i]['id'].'">'.$res[$i]['nome'].' / R$ '.$valor_vendaF.' / '.$estoqueF.' '.$sigla_unidade.'</option>';

}

echo '</select>';

?>


	<script type="text/javascript">
			$(document).ready(function() {	

				var id_produto = "<?=$id_produto?>";	
				var estoque_unit = "<?=$estoque_unit?>";
				var nome = "<?=$nome?>";
				var nome_unidade = "<?=$nome_unidade?>";

				$('#id_produto_input').val(id_produto);
				$('#estoque_unit_input').val(estoque_unit);
				$('#nome_input').val(nome);
				$('#nome_unidade_input').val(nome_unidade);
			
				$('.sel2').select2({
				dropdownParent: $('#modalForm')
			});
				
			});

			
	</script>

