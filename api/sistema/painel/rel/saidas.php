<?php 
include('../../conexao.php');

$dataInicial = $_GET['dataInicial'];
$dataFinal = $_GET['dataFinal'];

include('data_formatada.php');

$dataInicialF = implode('/', array_reverse(@explode('-', $dataInicial)));
$dataFinalF = implode('/', array_reverse(@explode('-', $dataFinal)));	
$datas = "";
if($dataInicial == $dataFinal){
	$datas = $dataInicialF;
}else{
	$datas = $dataInicialF.' à '.$dataFinalF;
}

$texto_filtro = 'Apurado em: '.$datas;

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
<img class="marca" src="<?php echo $url_sistema ?>img/logo.jpg">	
<?php } ?>


<div id="header" >

	<div style="border-style: solid; font-size: 10px; height: 55px;">
		<table style="width: 100%; border: 0px solid #ccc;">
			<tr>
				<td style="border: 1px; solid #000; width: 25%; text-align: left;">
					<img style="margin-top: 0px; margin-left: 7px;" id="imag" src="<?php echo $url_sistema ?>img/logo.jpg" width="180px">
				</td>
				
				<td style="text-align: center; font-size: 9px; width: 40%;">
				
                   <b><?php echo mb_strtoupper($nome_sistema) ?></b><br>
                   	<?php echo mb_strtoupper($cnpj_sistema) ?><br>
                   	INSTAGRAM: <b><?php echo mb_strtoupper($instagram_sistema) ?></b><br>
                   	<?php echo mb_strtoupper($endereco_sistema) ?>
 
				</td>
				<td style="width: 40%; text-align: right; font-size: 9px;padding-right: 10px;">
						<b><big>RELATÓRIO DE ENTRADAS DE PRODUTOS</big></b><br> <?php echo mb_strtoupper($texto_filtro) ?> <br> <?php echo mb_strtoupper($data_hoje) ?>
				</td>
			</tr>		
		</table>
	</div>

<br>


		<table id="cabecalhotabela" style="border-bottom-style: solid; font-size: 10px; margin-bottom:10px; width: 100%; table-layout: fixed;">
			<thead>
				
				<tr id="cabeca" style="margin-left: 0px; background-color:#CCC">
					<td style="width:25%">PRODUTO</td>
					<td style="width:12%">QUANTIDADE</td>
					<td style="width:35%">MOTIVO</td>
					<td style="width:16%">USUÁRIO</td>
					<td style="width:12%">DATA</td>
					
					
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
$produtos_ativos = 0;
$produtos_inativos = 0;
$estoque_baixo = 0;
$query = $pdo->query("SELECT * from saidas where data >= '$dataInicial' and data <= '$dataFinal' order by id asc");
$res = $query->fetchAll(PDO::FETCH_ASSOC);
$linhas = @count($res);
if($linhas > 0){
for($i=0; $i<$linhas; $i++){
	$id = $res[$i]['id'];
	$produto = $res[$i]['produto'];	
	$quantidade = $res[$i]['quantidade'];	
	$motivo = $res[$i]['motivo'];
	$usuario = $res[$i]['usuario'];
	$data = $res[$i]['data'];
	
	
	$dataF = implode('/', array_reverse(@explode('-', $data)));

	$query2 = $pdo->query("SELECT * from produtos where id = '$produto'");
	$res2 = $query2->fetchAll(PDO::FETCH_ASSOC);
	$nome_produto = @$res2[0]['nome'];
	$estoque = @$res2[0]['estoque'];
	$unidade = @$res2[0]['unidade'];

	$query2 = $pdo->query("SELECT * from usuarios where id = '$usuario'");
	$res2 = $query2->fetchAll(PDO::FETCH_ASSOC);
	$nome_usuario = @$res2[0]['nome'];

	$query2 = $pdo->query("SELECT * from unidade_medida where id = '$unidade'");
	$res2 = $query2->fetchAll(PDO::FETCH_ASSOC);
	$nome_unidade = @$res2[0]['nome'];

	//tratamento separa string
	$quant = explode(".", $quantidade);
	if($quant[1] > 0){
		$quantidadeF = $quantidade;		
	}else{
		$quantidadeF = $quant[0];
	}
	
	
  	 ?>

  	 
      <tr>
<td style="width:25%"><?php echo $nome_produto ?></td>
<td style="width:12%"><?php echo $quantidadeF ?> <small><span style="color:blue; font-size:7px">(<?php echo $nome_unidade ?>)</span></small></td>
<td style="width:35%"><?php echo $motivo ?></td>
<td style="width:16%"><?php echo $nome_usuario ?></td>
<td style="width:12%;"><?php echo $dataF ?></td>


    </tr>

<?php } } ?>
				</tbody>
	
			</thead>
		</table>
	


</div>
<hr>
		<table>
			<thead>
				<tbody>
					<tr>
						<td style="font-size: 10px; width:730px; text-align: right;"><b>Total Registros:<span> <?php echo $linhas ?></span> </td>

						
						
					</tr>
				</tbody>
			</thead>
		</table>

</body>

</html>


