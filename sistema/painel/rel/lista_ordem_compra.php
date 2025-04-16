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

$dataInicial = $_GET['dataInicial'];
$dataFinal = $_GET['dataFinal'];
$tipo_busca = $_GET['tipo_busca'];

$mostrar_registros = $_GET['mostrar_registros'];
$id_usuario = $_GET['id_usuario'];

$dataInicialF = implode('/', array_reverse(@explode('-', $dataInicial)));
$dataFinalF = implode('/', array_reverse(@explode('-', $dataFinal)));


$datas = "";
if($dataInicial == $dataFinal){
	$datas = $dataInicialF;
}else{
	$datas = $dataInicialF.' à '.$dataFinalF;
}

$texto_filtro = $datas;

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
					<img style="margin-top: 7px; margin-left: 7px;" id="imag" src="<?php echo $url_sistema ?>img/logo.jpg" width="160px">
				</td>
				<td style="width: 30%; text-align: left; font-size: 13px;">
					
				</td>
				<td style="width: 1%; text-align: center; font-size: 13px;">
				
				</td>
				<td style="width: 47%; text-align: right; font-size: 9px;padding-right: 10px;">
						<b><big>ORDENS DE COMPRAS </big></b>
							<br>FILTRO: <?php echo mb_strtoupper($texto_filtro) ?> 
							<br> <?php echo mb_strtoupper($data_hoje) ?>
				</td>
			</tr>		
		</table>
	</div>

<br>


		<table id="cabecalhotabela" style="border-bottom-style: solid; font-size: 10px; margin-bottom:10px; width: 100%; table-layout: fixed;">
			<thead>
				
				<tr id="cabeca" style="margin-left: 0px; background-color:#CCC">
					<td style="width:10%">CÓDIGO</td>
					<td style="width:10%">VALOR</td>
					<td style="width:30%">FORNECEDOR</td>
					<td style="width:10%">STATUS</td>
					<td style="width:10%">DATA</td>						
					<td style="width:30%">COMPRADOR</td>	
				</tr>
			</thead>
		</table>
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

<div id="content" style="margin-top: 0;">



		<table style="width: 100%; table-layout: fixed; font-size:9px; text-transform: uppercase;">
			<thead>
				<tbody>
					<?php

$total_orc = 0;
$total_orcF = 0;
$total_pedidos = 0;
$total_pedidosF = 0;
$itens_pedidos = 0;
$itens_orc = 0;

if($mostrar_registros == 'Não'){	
	$query = $pdo->query("SELECT * from ordem_compra where usuario = '$id_usuario' and data >= '$dataInicial' and data <= '$dataFinal' and status like '%$tipo_busca%' order by id desc");	
}else{	
	$query = $pdo->query("SELECT * from ordem_compra where data >= '$dataInicial' and data <= '$dataFinal' and status like '%$tipo_busca%' order by id desc");
	
}

$res = $query->fetchAll(PDO::FETCH_ASSOC);
$linhas = @count($res);
if($linhas > 0){
for($i=0; $i<$linhas; $i++){
	$id = $res[$i]['id'];
	
	$valor = $res[$i]['valor'];
	$data = $res[$i]['data'];

	$desconto = $res[$i]['desconto'];
	$tipo_desconto = $res[$i]['tipo_desconto'];
	$subtotal = $res[$i]['subtotal'];
	$obs = $res[$i]['obs'];
	$usuario = $res[$i]['usuario'];
	$status = $res[$i]['status'];
	$frete = $res[$i]['frete'];
	$forma_pgto = $res[$i]['forma_pgto'];
	$hora = $res[$i]['hora'];
	$fornecedor = $res[$i]['fornecedor'];


	$dataF = implode('/', array_reverse(@explode('-', $data)));
	
	$valorF = @number_format($valor, 2, ',', '.');	
	$descontoF = @number_format($desconto, 2, ',', '.');
	$freteF = @number_format($frete, 2, ',', '.');
	$subtotalF = @number_format($subtotal, 2, ',', '.');

	

$query2 = $pdo->query("SELECT * FROM usuarios where id = '$usuario'");
$res2 = $query2->fetchAll(PDO::FETCH_ASSOC);
if(@count($res2) > 0){
	$nome_vendedor = $res2[0]['nome'];
}else{
	$nome_vendedor = 'Sem Usuário';
}


$query2 = $pdo->query("SELECT * FROM formas_pgto where id = '$forma_pgto'");
$res2 = $query2->fetchAll(PDO::FETCH_ASSOC);
if(@count($res2) > 0){
	$nome_pgto = $res2[0]['nome'];
	$taxa_pgto = $res2[0]['taxa'];
}else{
	$nome_pgto = 'Sem Registro';
	$taxa_pgto = 0;
}


$query2 = $pdo->query("SELECT * FROM fornecedores where id = '$fornecedor'");
$res2 = $query2->fetchAll(PDO::FETCH_ASSOC);
if(@count($res2) > 0){
	$nome_forn = $res2[0]['nome'];
}else{
	$nome_forn = '';
}

if($status == 'Aprovada'){
	$classe_pago = 'green';
	$ocultar = 'ocultar';
	$ocultar_pendentes = '';
	$classe_tipo = 'verde.jpg';
}else{
	$classe_pago = 'red';
	$ocultar_pendentes = 'ocultar';
	$ocultar = '';
	$classe_tipo = 'vermelho.jpg';
	
}	

$total_orc += $subtotal;
	$itens_orc += 1;

  	 ?>

  	 
      <tr>
<td style="width:10%">
<img style="margin-top: 0px" src="<?php echo $url_sistema ?>painel/images/<?php echo $classe_tipo ?>" width="8px">
	 Nº <?php echo $id ?></td>
<td style="width:10%">R$ <?php echo $subtotalF ?></td>
<td style="width:30%"><?php echo $nome_forn ?></td>
<td style="width:10%; color:<?php echo $classe_pago ?>"><?php echo $status ?></td>
<td style="width:10%"><?php echo $dataF ?></td>
<td style="width:30%"><?php echo $nome_vendedor ?></td>

    </tr>

<?php } }

$total_orcF = @number_format($total_orc, 2, ',', '.');
$total_pedidosF = @number_format($total_pedidos, 2, ',', '.');

 ?>
				</tbody>
	
			</thead>
		</table>
	


</div>
<hr>
		<table>
			<thead>
				<tbody>
					<tr>

						<td style="font-size: 10px; width:300px; text-align: right;"></td>

						

						<td style="font-size: 10px; width:50px; text-align: right;"></td>

							<td style="font-size: 10px; width:50px; text-align: right;"></td>
								
								<td style="font-size: 10px; width:120px; text-align: right;"></td>
								
									<td style="font-size: 10px; width:180px; text-align: right;"><b><?php echo $itens_orc ?> Ordem de Compras: <span style="color:blue">R$ <?php echo $total_orcF ?></span></td>
									
						
					</tr>
				</tbody>
			</thead>
		</table>

</body>

</html>




 