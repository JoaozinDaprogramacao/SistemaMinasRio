<?php 
include('../../conexao.php');
include('data_formatada.php');

$id = $_GET['id'];

$query = $pdo->query("SELECT * from orcamentos where id = '$id'");
$res = $query->fetchAll(PDO::FETCH_ASSOC);
$cliente = $res[0]['cliente'];
$valor = $res[0]['valor'];
$data = $res[0]['data'];
$dias_validade = $res[0]['dias_validade'];	
$desconto = $res[0]['desconto'];
$tipo_desconto = $res[0]['tipo_desconto'];
$subtotal = $res[0]['subtotal'];
$obs = $res[0]['obs'];
$usuario = $res[0]['usuario'];
$status = $res[0]['status'];
$frete = $res[0]['frete'];
$forma_pgto = $res[0]['forma_pgto'];
$hora = $res[0]['hora'];
$tipo = $res[0]['tipo'];

if($status == 'Pendente'){
	$img_marca = 'pendente.jpg';
}else{
	if($tipo == 'Pedido'){
		$img_marca = 'concluido.jpg';
	}else{
		$img_marca = 'aprovado.jpg';
	}
}

//buscar o total dos produtos
$total_dos_produtos = 0;
$query = $pdo->query("SELECT * from itens_orc where id_orcamento = '$id' order by id asc");	

$res = $query->fetchAll(PDO::FETCH_ASSOC);
$linhas = @count($res);
if($linhas > 0){
	for($i=0; $i<$linhas; $i++){	
		$total_das_vendas = $res[$i]['total'];
		$total_dos_produtos += $total_das_vendas;
	}
}

$dataF = implode('/', array_reverse(@explode('-', $data)));

//formatar os valores
$valorF = number_format($valor, 2, ',', '.');
$subtotalF = number_format($subtotal, 2, ',', '.');
$freteF = number_format($frete, 2, ',', '.');
$descontoF = number_format($desconto, 2, ',', '.');

$query2 = $pdo->query("SELECT * FROM usuarios where id = '$usuario'");
$res2 = $query2->fetchAll(PDO::FETCH_ASSOC);
if(@count($res2) > 0){
	$nome_usu_lanc = $res2[0]['nome'];
}else{
	$nome_usu_lanc = 'Sem Usuário';
}


$query2 = $pdo->query("SELECT * FROM formas_pgto where id = '$forma_pgto'");
$res2 = $query2->fetchAll(PDO::FETCH_ASSOC);
if(@count($res2) > 0){
	$nome_forma_pgto = $res2[0]['nome'];
}else{
	$nome_forma_pgto = '';
}


$query2 = $pdo->query("SELECT * FROM clientes where id = '$cliente'");
$res2 = $query2->fetchAll(PDO::FETCH_ASSOC);
if(@count($res2) > 0){
	$nome_cliente = $res2[0]['nome'];	
	$tel_cliente = $res2[0]['telefone'];
	$email_cliente = $res2[0]['email'];
	$endereco_cliente = $res2[0]['endereco'];
}

$total_do_desc = '';
if($tipo_desconto == "%"){
	$valor_porcent = '%';
	$valor_reais = '';
	$total_do_desc = $total_dos_produtos * $desconto / 100;
	$total_do_descF = number_format($total_do_desc, 2, ',', '.');
	$descontoF = number_format($desconto, 0, ',', '.').$valor_porcent. ' R$'.$total_do_descF;;
}else{
	$valor_porcent = '';
	$valor_reais = 'R$';
	$descontoF = number_format($desconto, 2, ',', '.');
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
	opacity:10%;
	transform: rotate(-30deg);
}

</style>

</head>
<body>
<?php 
if($marca_dagua == 'Sim'){ ?>
<img class="marca" src="<?php echo $url_sistema ?>img/<?php echo $img_marca ?>">	
<?php } ?>



<div id="header" >

	<div style="border-style: solid; font-size: 10px; height: 55px;">
		<table style="width: 100%; border: 0px solid #ccc;">
			<tr>
				<td style="border: 1px; solid #000; width: 25%; text-align: left;">
					<img style="margin-top: 0px; margin-left: 7px;" id="imag" src="<?php echo $url_sistema ?>img/logo.jpg" width="180px">
				</td>
		
				<td style="text-align: center; font-size: 10px;">
				
                   <b><?php echo @mb_strtoupper($nome_sistema) ?></b><br>
                   	<?php echo @mb_strtoupper($cnpj_sistema) ?><br>
                   	INSTAGRAM: <b><?php echo @mb_strtoupper($instagram_sistema) ?></b><br>
                   	<?php echo @mb_strtoupper($endereco_sistema) ?>

				</td>
				<td style="width: 28%; text-align: right; font-size: 9px;padding-right: 10px;">
						<b><big>NÚMERO DO <?php echo @mb_strtoupper($tipo) ?>: <?php echo $id ?></big></b><br>CONTATO: <?php echo $telefone_sistema ?><br> <?php echo @mb_strtoupper($data_hoje) ?>
				</td>
			</tr>		
		</table>
	</div>

</div>


		<table id="cabecalhotabela" style="border-bottom-style: solid; font-size: 10px; margin-bottom:10px; width: 100%; table-layout: fixed; margin-top: -20px">
			<thead>
				<?php if($tipo == 'Orçamento'){ ?>
				<tr id="cabeca" style="margin-left: 0px; background-color:#f2f0f0">
					<td style="width:14%"><b>DATA: <?php echo $dataF ?></b> </td>
					<td style="width:24%"><b>VALIDADE ORÇAMENTO: <?php echo $dias_validade ?> DIAS </b></td>
					<td style="width:27%"><b>FORMA PGTO: <?php echo mb_strtoupper($nome_forma_pgto) ?></b></td>
					<td style="width:35%"><b>VENDEDOR: <?php echo mb_strtoupper($nome_usu_lanc) ?></b></td>	
				</tr>
			<?php }else{ ?>
				<tr id="cabeca" style="margin-left: 0px; background-color:#f2f0f0">
					<td style="width:30%"><b>DATA: <?php echo $dataF ?></b> </td>
					<<td style="width:30%"><b>FORMA PGTO: <?php echo mb_strtoupper($nome_forma_pgto) ?></b></td>
					<td style="width:40%"><b>VENDEDOR: <?php echo mb_strtoupper($nome_usu_lanc) ?></b></td>	
				</tr>
			<?php } ?>
			</thead>
		</table>




		<table id="cabecalhotabela" style="border-style: solid; font-size: 9px; margin-bottom:10px; width: 100%; table-layout: fixed; margin-top:10px;">
			<thead>
				
				<tr id="cabeca" style="margin-left: 0px; background-color:#f2f0f0">
					<td colspan="4" style="width:100%; font-size: 10px"><b>DADOS DO CLIENTE</b> </td>					
				</tr>
				<tr >
					<td style="width:10%; border-right: 1px solid #000;border-bottom: : 1px solid #000;">NOME: </td>
					<td style="width:40%; border-right: : 1px solid #000; border-bottom: : 1px solid #000;">
						<?php echo @mb_strtoupper($nome_cliente) ?>
					</td>
					
					<td style="width:10%; border-right: 1px solid #000;border-bottom: : 1px solid #000;">ENDEREÇO: </td>
					<td style="width:40%; border-bottom: : 1px solid #000;">
						<?php echo @mb_strtoupper($endereco_cliente) ?>

					</td>
    			</tr>
    			<tr >
					<td style="width:10%; border-right: 1px solid #000;">TELEFONE: </td>
					<td style="width:40%; border-right: : 1px solid #000; ">
						<?php echo @mb_strtoupper($tel_cliente) ?>
					</td>
					
					<td style="width:10%; border-right: 1px solid #000;">EMAIL: </td>
					<td style="width:40%; ">
						<?php echo @mb_strtoupper($email_cliente) ?>
					</td>
    			</tr>
			</thead>
		</table>


<?php 
$total_produtos = 0;
$total_produtosF = 0;
$query = $pdo->query("SELECT * from itens_orc where id_orcamento = '$id'");
$res = $query->fetchAll(PDO::FETCH_ASSOC);
$linhas = @count($res);
if($linhas > 0){
 ?>
		<table id="cabecalhotabela" style="border-bottom-style: solid; font-size: 10px; margin-bottom:10px; width: 100%; table-layout: fixed; margin-top: 10px">
			<thead>
				
				<tr id="cabeca" style="margin-left: 0px; background-color:#CCC">
					<td style="width:45%">PRODUTO</td>
					<td style="width:15%">QUANTIDADE</td>
					<td style="width:20%">VALOR UNITÁRIO</td>
					<td style="width:20%">TOTAL</td>
					
					
				</tr>
			</thead>
		</table>

		


		<table style="width: 100%; table-layout: fixed; font-size:10px; text-transform: uppercase; margin-top: -5px">
			<thead>
				<tbody>
					<?php 
					$total_final = 0;
for($i=0; $i<$linhas; $i++){
	
	$produto = $res[$i]['produto'];
	$valor = $res[$i]['valor'];
	$quantidade = $res[$i]['quantidade'];
	$total = $res[$i]['total'];
	


	$total_final += $total;

	$valorF = number_format($valor, 2, ',', '.');
	$totalF = number_format($total, 2, ',', '.');
	
		

	$query2 = $pdo->query("SELECT * from produtos where id = '$produto'");
	$res2 = $query2->fetchAll(PDO::FETCH_ASSOC);
	$nome_produto = $res2[0]['nome'];
	$foto_produto = $res2[0]['foto'];
	$unidade = $res2[0]['unidade'];

	$query3 = $pdo->query("SELECT * FROM unidade_medida where id = '$unidade'");
	$res3 = $query3->fetchAll(PDO::FETCH_ASSOC);
	if(@count($res3) > 0){
		$nome_unidade = $res3[0]['nome'];
	}else{
		$nome_unidade = 'Sem Unidade';
	}

	$ocultar_quantidades = '';
	$sigla_unidade = '';
	if($nome_unidade == 'Quilogramas' or $nome_unidade == 'Quilo' or $nome_unidade == 'Quilograma' or $nome_unidade == 'KG'){
		$sigla_unidade = ' (KG)';
		$ocultar_quantidades = 'ocultar';
	}

	if($nome_unidade == 'Metros' or $nome_unidade == 'Metro' or $nome_unidade == 'M' or $nome_unidade == 'm'){
		$sigla_unidade = ' (m)';
		$ocultar_quantidades = 'ocultar';
	}

	if($nome_unidade == 'Litro' or $nome_unidade == 'Litros' or $nome_unidade == 'L'){
		$sigla_unidade = ' (L)';
		$ocultar_quantidades = 'ocultar';
	}

	//tratamento separa string
	$qt = explode(".", $quantidade);
	if($qt[1] > 0){
		$quantidadeF = $quantidade;		
	}else{
		$quantidadeF = $qt[0];
	}

	$nome_produtoF = mb_strimwidth($nome_produto, 0, 24, "...");
	
  	 ?>

  	 
<tr>
	<td style="width:45%"><?php echo $nome_produtoF ?></td>
	<td style="width:15%"><?php echo $quantidadeF ?> <?php echo $sigla_unidade ?></td>
	<td style="width:20%">R$ <?php echo $valorF ?> <?php echo $sigla_unidade ?></td>
	<td style="width:20%"><b>R$ <?php echo $totalF ?></b></td>
 </tr>

<?php } ?>
				</tbody>
	
			</thead>
		</table>

		<?php
		$total_finalF = number_format($total_final, 2, ',', '.');
		 } 

		 ?>

		
		<div style="background:#f2f0f0; padding:5px; margin-top: 20px;">
		<?php if($total_final > 0){ ?>
		<div align="right" style="margin-top: 4px; font-size:10px;">
			<span><b>TOTAL PRODUTOS:</b> R$ <?php echo $total_finalF ?></span>
		</div>
		<?php } ?>


		<?php if($desconto > 0){ ?>
		<div align="right" style="margin-top: 4px; font-size:10px;">
			<span><b>DESCONTO:</b> <?php echo $valor_reais ?> <?php echo $descontoF ?></span>
		</div>
		<?php } ?>

		<?php if($frete > 0){ ?>
		<div align="right" style="margin-top: 4px; font-size:10px;">
			<span><b>FRETE: </b>R$ <?php echo $freteF ?></span>
		</div>
		<?php } ?>

		

		</div>

		<hr>
		<div align="right" style="margin-top: 4px; font-size:11px; background:#d9dbdb; padding:5px">
			<span><b>TOTAL: R$ <?php echo $subtotalF ?></b></span>
		</div>


		<?php if($obs != ""){ ?>
		<div align="" style="margin-top: 20px; font-size:10px;">
			<span><b>OBS: </b> <?php echo $obs ?></span>
		</div>
		<?php } ?>



		<div align="center" style="margin-top: 35px; font-size:10px; padding-top:15px; padding-bottom:5px">
			<span>_________________________________________________________________<br>
			ASSINATURA DO CLIENTE</span>
		</div>






<div id="footer" class="row">
<hr style="margin-bottom: 0;">
	<table style="width:100%;">
		<tr style="width:100%;">
			<td style="width:60%; font-size: 10px; text-align: left;"><?php echo $nome_sistema ?> Telefone: <?php echo $telefone_sistema ?></td>
			<td style="width:40%; font-size: 10px; text-align: right;"><p class="page">Página  </p></td>
		</tr>
	</table>
</div>


	
</body>

</html>


