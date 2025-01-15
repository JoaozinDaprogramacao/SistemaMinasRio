<?php 
require_once("../../../conexao.php");
$pagina = 'produtos';

$produto = @$_POST['produto'];

$query = $pdo->query("SELECT * FROM produtos where id = '$produto' order by nome asc");
$res = $query->fetchAll(PDO::FETCH_ASSOC);

	$id_produto = @$res[0]['id'];
	$valor_venda = @$res[0]['valor_venda'];
	$estoque = @$res[0]['estoque'];
	$unidade = @$res[0]['unidade'];
	$nome = @$res[0]['nome'];

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

	echo $estoque_unit;


?>
