<?php 
require_once("sistema/conexao.php");

$busca = @$_POST['buscar'];
$categoria = @$_POST['cat'];

if($categoria == ""){
	$query = $pdo->query("SELECT * from categorias where ativo = 'Sim' order by nome asc limit 1");
	$res = $query->fetchAll(PDO::FETCH_ASSOC);
	$categoria = $res[0]['id'];
}

if($busca != ""){
	$query = $pdo->query("SELECT * from produtos where ativo = 'Sim' and nome like '%$busca%' order by nome asc");
}else{
	$query = $pdo->query("SELECT * from produtos where ativo = 'Sim' and categoria = '$categoria' order by nome asc");
}

$res = $query->fetchAll(PDO::FETCH_ASSOC);
$linhas = @count($res);
if($linhas > 0){

	for($i=0; $i<$linhas; $i++){
	$id = $res[$i]['id'];
	$nome = $res[$i]['nome'];
	$categoria = $res[$i]['categoria'];
	$obs = $res[$i]['obs'];
	$valor_compra = $res[$i]['valor_compra'];
	$valor_venda = $res[$i]['valor_venda'];
	$tem_estoque = $res[$i]['tem_estoque'];
	$estoque = $res[$i]['estoque'];
	$unidade = $res[$i]['unidade'];
	$fornecedor = $res[$i]['fornecedor'];
	$estoque_minimo = $res[$i]['estoque_minimo'];
	$ativo = $res[$i]['ativo'];
	$foto = $res[$i]['foto'];
	$valor_sem_promocao = $res[$i]['valor_sem_promocao'];

	$dataF = implode('/', array_reverse(@explode('-', $data)));
	$valorF = @number_format($valor_venda, 2, ',', '.');
	$valor_compraF = @number_format($valor_compra, 2, ',', '.');
	$valor_vendaF = @number_format($valor_venda, 2, ',', '.');
	$valor_sem_promocaoF = @number_format($valor_sem_promocao, 2, ',', '.');


	$query2 = $pdo->query("SELECT * FROM categorias where id = '$categoria'");
	$res2 = $query2->fetchAll(PDO::FETCH_ASSOC);
	if(@count($res2) > 0){
		$nome_cat = $res2[0]['nome'];
	}else{
		$nome_cat = 'Sem Categoria';
	}


	$query3 = $pdo->query("SELECT * FROM unidade_medida where id = '$unidade'");
	$res3 = $query3->fetchAll(PDO::FETCH_ASSOC);
	if(@count($res3) > 0){
		$nome_unidade = $res3[0]['nome'];
	}else{
		$nome_unidade = 'Sem Unidade';
	}

	$sigla_unidade = '';
	if($nome_unidade == 'Quilogramas' or $nome_unidade == 'Quilo' or $nome_unidade == 'Quilograma' or $nome_unidade == 'KG'){
		$sigla_unidade = ' (KG)';		
	}

	if($nome_unidade == 'Metros' or $nome_unidade == 'Metro' or $nome_unidade == 'M' or $nome_unidade == 'm'){
		$sigla_unidade = ' (m)';
	}

	if($nome_unidade == 'Litro' or $nome_unidade == 'Litros' or $nome_unidade == 'L'){
		$sigla_unidade = ' (L)';		
	}

	$area_promocao = 'ocultar';
	if($valor_sem_promocao > 0){
		$area_promocao = '';
	}

	echo '
	<a href="http://api.whatsapp.com/send?1=pt_BR&phone='.@$tel_whats.'&text=Tenho interesse no item: '.$nome.'" title="Ir para o Whatsapp" target="_blank">
	<div class="col-md-4 col-xs-6">															
	<div class="product-widget">
	<div class="product-img">
	<img src="sistema/painel/images/produtos/'.$foto.'" alt="">
	</div>
	<div class="product-body">
	<p class="product-category">'.$nome_cat.'</p>
	<h3 class="product-name"><a href="http://api.whatsapp.com/send?1=pt_BR&phone='.@$tel_whats.'&text=Tenho interesse no item: '.$nome.'" title="Ir para o Whatsapp" target="_blank">'.$nome.'</a></h3>
	<h4 class="product-price">R$ '.$valorF.' '.$sigla_unidade.' <del class="product-old-price '.$area_promocao.'">R$ '.$valor_sem_promocaoF.'</del></h4>
	</div>
	</div>
	</div>
	</a>
	';



	}
}
	?>