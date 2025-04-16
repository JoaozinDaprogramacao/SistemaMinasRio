<?php 
include('../../conexao.php');

$id = $_GET['id'];

//BUSCAR AS INFORMAÇÕES DO PEDIDO
$query = $pdo->query("SELECT * from receber where id = '$id' ");
$res = $query->fetchAll(PDO::FETCH_ASSOC);

		$id = $res[0]['id'];
		$descricao = $res[0]['descricao'];
		$cliente = $res[0]['cliente'];
		$valor = $res[0]['valor'];
		$data_lanc = $res[0]['data_lanc'];
		$data_venc = $res[0]['vencimento'];
		$data_pgto = $res[0]['data_pgto'];
		$usuario_lanc = $res[0]['usuario_lanc'];
		$usuario_pgto = $res[0]['usuario_pgto'];
		$frequencia = $res[0]['frequencia'];
		$saida = $res[0]['forma_pgto'];
		$arquivo = $res[0]['arquivo'];
		$pago = $res[0]['pago'];
		$obs = $res[0]['obs'];
		$desconto = $res[0]['desconto'];
		$troco = $res[0]['troco'];
		$hora = $res[0]['hora'];
		$cancelada = $res[0]['cancelada'];
		$garantia_venda = '';
		$tipo_desconto = $res[0]['tipo_desconto'];
		$total_venda = $res[0]['subtotal'];
		$valor_restante = $res[0]['valor_restante'];
		$forma_pgto_restante = $res[0]['forma_pgto_restante'];
		$data_restante = $res[0]['data_restante'];
		$id_ref = $res[0]['id_ref'];
		$referencia = $res[0]['referencia'];
		$frete = $res[0]['frete'];

		$data_venc_1 = '';
		if(@strtotime($data_venc) > @strtotime($data_lanc)){
			$data_venc_1 = $data_venc;
		}

		$data_venc_2 = '';
		if(@strtotime($data_restante) > @strtotime($data_lanc)){
			$data_venc_2 = $data_restante;
		}

if($troco > 0){
	$total_troco = $troco - $valor;
}else{
	$total_troco = 0;
}


$data_venc_1F = implode('/', array_reverse(@explode('-', $data_venc_1)));
$data_venc_2F = implode('/', array_reverse(@explode('-', $data_venc_2)));
$data_lancF = implode('/', array_reverse(@explode('-', $data_lanc)));
$data_vencF = implode('/', array_reverse(@explode('-', $data_venc)));
$data_pgtoF = implode('/', array_reverse(@explode('-', $data_pgto)));
$valorF = @number_format($valor, 2, ',', '.');

$trocoF = @number_format($troco, 2, ',', '.');
$total_trocoF = @number_format($total_troco, 2, ',', '.');

$total_vendaF = @number_format($total_venda, 2, ',', '.');
$valor_restanteF = @number_format($valor_restante, 2, ',', '.');

$descontoFP = @number_format($desconto, 0, ',', '.');

$freteF = @number_format($frete, 2, ',', '.');

$query2 = $pdo->query("SELECT * FROM usuarios where id = '$usuario_lanc'");
$res2 = $query2->fetchAll(PDO::FETCH_ASSOC);
if(@count($res2) > 0){
	$nome_usu_lanc = $res2[0]['nome'];
}else{
	$nome_usu_lanc = 'Sem Usuário';
}


$query2 = $pdo->query("SELECT * FROM clientes where id = '$cliente'");
$res2 = $query2->fetchAll(PDO::FETCH_ASSOC);
if(@count($res2) > 0){
	$nome_cliente = $res2[0]['nome'];	
	$tel_cliente = $res2[0]['telefone'];
}else{
	$nome_cliente = 'Não Informado';	
	$tel_cliente = '';
}


if($id_ref != "" and $referencia == 'Venda'){
	$id = $id_ref;
}



$query2 = $pdo->query("SELECT * FROM formas_pgto where id = '$saida'");
$res2 = $query2->fetchAll(PDO::FETCH_ASSOC);
if(@count($res2) > 0){
	$saida = $res2[0]['nome'];
	
}else{
	$saida = '';

}


$query2 = $pdo->query("SELECT * FROM formas_pgto where id = '$forma_pgto_restante'");
$res2 = $query2->fetchAll(PDO::FETCH_ASSOC);
if(@count($res2) > 0){
	$forma_pgto_restante = $res2[0]['nome'];
	
}else{
	$forma_pgto_restante = '';

}


?>


<script src="//cdnjs.cloudflare.com/ajax/libs/jquery/3.2.1/jquery.min.js"></script>

<?php if(@$impressao_automatica == 'Sim' and @$_GET['imprimir'] != 'Não'){ ?>
<script type="text/javascript">
	$(document).ready(function() {    		
		window.print();
		window.close(); 
	} );
</script>
<?php } ?>


<style type="text/css">
	*{
	margin:0px;

	/*Espaçamento da margem da esquerda e da Direita*/
	padding:0px;
	background-color:#ffffff;
	
	font-color:#000;	
	font-family: TimesNewRoman, Geneva, sans-serif; 

}
.text {
	&-center { text-align: center; }
}
.ttu { text-transform: uppercase;
	font-weight: bold;
	font-size: 1.2em;
 }

.printer-ticket {
	display: table !important;
	width: 100%;

	/*largura do Campos que vai os textos*/
	max-width: 400px;
	font-weight: light;
	line-height: 1.3em;

	/*Espaçamento da margem da esquerda e da Direita*/
	padding: 0px;
	font-family: TimesNewRoman, Geneva, sans-serif; 

	/*tamanho da Fonte do Texto*/
	font-size: 11px; 
	font-color:#000;
	
	
	}
	
	th { 
		font-weight: inherit;

		/*Espaçamento entre as uma linha para outra*/
		padding:5px;
		text-align: center;

		/*largura dos tracinhos entre as linhas*/
		border-bottom: 1px dashed #000000;
	}


	

	
	
		
	.cor{
		color:#000000;
	}
	
	
	

	/*margem Superior entre as Linhas*/
	.margem-superior{
		padding-top:5px;
	}
	
	
} 
</style>



<table class="printer-ticket">

		<td>
		<img style="margin-top: 10px; margin-left: 40px;" id="imag" src="<?php echo $url_sistema ?>img/logo.jpg" width="220px">
	</td>

	<tr>
		<th class="ttu" class="title" colspan="3"></th>
	</tr>
	<tr style="font-size: 10px">
		<th colspan="3">
			<?php echo $endereco_sistema ?> <br />
			<?php if($cnpj_sistema != ""){ ?> CNPJ <?php echo  $cnpj_sistema  ?><?php } ?><br />
			Contato: <?php echo $telefone_sistema ?> 
		</th>
	</tr>

	<tr >
		<th colspan="3">Cliente <?php echo $nome_cliente ?> - Data: <?php echo $data_lancF ?>			
			<br>
			Venda: <?php echo $id ?> - <?php if($cancelada == 'Sim'){
				echo 'CANCELADA';
			}else{ ?>Pago : <?php echo $pago ?> <?php } ?>
			
			
		</th>
	</tr>
	
	<tr>
		<th class="ttu margem-superior" colspan="3">
			Comprovante de Venda
			
		</th>
	</tr>
	<tr>
		<?php if($garantia_venda != ''){ ?>
			<th colspan="3">
			Garantia de <?php echo $garantia_venda ?> Dias
			</th>
		<?php }else{ ?>
		<th colspan="3">
			CUMPOM NÃO FISCAL
			
		</th>
	<?php } ?>
	</tr>
	
	<tbody>

		<?php 

		$res = $pdo->query("SELECT * from itens_venda where id_venda = '$id' order by id asc");
		$dados = $res->fetchAll(PDO::FETCH_ASSOC);
		$linhas = count($dados);

		$sub_tot;
		$total_itens = 0;
		for ($i=0; $i < count($dados); $i++) { 
			foreach ($dados[$i] as $key => $value) {
			}

			$id_produto = $dados[$i]['material']; 
			$quantidade = $dados[$i]['quantidade'];
			$valor = $dados[$i]['valor'];
			$total= $dados[$i]['total'];
			$valor = $dados_p[0]['valor'];
			$unidade = $dados_p[0]['unidade'];				
			$total_item = $valor * $quantidade;	

		
			
			$res_p = $pdo->query("SELECT * from materiais where id = '$id_produto' ");
				$dados_p = $res_p->fetchAll(PDO::FETCH_ASSOC);
				$nome_produto = $dados_p[0]['nome'];				

				$query3 = $pdo->query("SELECT * FROM unidade_medida where id = '$unidade'");
	$res3 = $query3->fetchAll(PDO::FETCH_ASSOC);
	if(@count($res3) > 0){
		$nome_unidade = $res3[0]['nome'];
	}else{
		$nome_unidade = 'Sem Unidade';
	}

	$sigla_unidade = '';
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
	$qt = explode(".", $quantidade);
	if($qt[1] > 0){
		$quantidadeF = $quantidade;		
	}else{
		$quantidadeF = $qt[0];
	}



			?>

			<tr>
				
					<td colspan="2" width="70%"> <?php echo $quantidadeF ?> <?php echo $sigla_unidade ?> - <?php echo $nome_produto ?>
					</td>
				

				<td align="right">R$ <?php
				@$total_item;
				@$sub_tot = @$valor;
				@$sub_total = $sub_tot - $desconto;

				$total_itens += $total_item;
				
				$sub_tot = @number_format( $sub_tot , 2, ',', '.');
				$sub_total = @number_format( $sub_total , 2, ',', '.');
				$total_item = @number_format( $total_item , 2, ',', '.');
				$total_itensF = @number_format( $total_itens , 2, ',', '.');
				// $total = @number_format( $cp1 , 2, ',', '.');


				

				echo $total_item ;
				?></td>
			</tr>

		<?php } ?>

<?php 
	if($tipo_desconto == '%' and $desconto > 0){
		$desconto = $total_itens * $desconto / 100;
	}
	$descontoF = @number_format($desconto, 2, ',', '.');
 ?>
				
	</tbody>
	<tfoot>

		<tfoot>

		<tr>
			<th class="ttu"  colspan="3" class="cor">
			<!-- _ _	_ _ _ _ _ _ _ _ _ _ _ _ _ _ _ -->
			</th>
		</tr>	
		
		<?php if($desconto != 0 and $desconto != ""){ ?>
		<tr>
			<td colspan="2">Total</td>
			<td align="right">R$ <?php echo $total_itensF ?></td>
		</tr>
		<?php } ?>

		<?php if($desconto != 0 and $desconto != ""){ ?>
		<tr>
			<?php if($tipo_desconto == '%'){ ?>
				<td colspan="2">Desconto <?php echo $descontoFP ?>%</td>
			<?php }else{ ?>
			<td colspan="2">Desconto</td>
		<?php } ?>			
			
			<td align="right">R$ <?php echo $descontoF ?></td>
		
		</tr>
		<?php } ?>



			<?php if($frete != 0 and $frete != ""){ ?>
		<tr>
			<td colspan="2">Frete</td>
			<td align="right">R$ <?php echo $freteF ?></td>
		</tr>
		<?php } ?>

		
		</tr>

			<tr>
			<td colspan="2"><b>SubTotal</b></td>
			<?php if($valor_restante > 0){ ?>
			<td align="right"><b>R$ <?php echo $total_vendaF ?></b></td>
		<?php }else{ ?>
			<td align="right"><b>R$ <?php echo $valorF ?></b></td>
		<?php } ?>
		</tr>	

		<?php if($troco != 0 and $troco != ""){ ?>
		<tr>
			<td colspan="2">Valor Recebido</td>
			<td align="right">R$ <?php echo $trocoF ?></td>
		</tr>
		<?php } ?>

		<?php if($total_troco != 0){ ?>
		<tr>
			<td colspan="2">Troco</td>
			<td align="right">R$ <?php echo $total_trocoF ?></td>
		</tr>
		<?php } ?>	

		<tr>
			<th class="ttu" colspan="3" class="cor">
			<!-- _ _	_ _ _ _ _ _ _ _ _ _ _ _ _ _ _ -->
			</th>
		</tr>	


		<?php if($valor_restante > 0){ ?>
		
		<tr>
			<td colspan="2">Pgto (R$ <?php echo $valorF ?>)</td>
			<td align="right"> <?php echo $saida ?> <?php echo $data_venc_1F ?></td>
		</tr>

		<tr>
			<td colspan="2">Restante (R$ <?php echo $valor_restanteF ?>)</td>
			<td align="right"> <?php echo $forma_pgto_restante ?> <?php echo $data_venc_2F ?></td>
		</tr>
	
	

	<?php }else{ ?>

		<tr>
			<td colspan="2">Forma de Pagamento</td>
			<td align="right"><?php echo $saida ?></td>
		</tr>

		<?php if($pago == 'Não'){ ?>
		<tr>
			<td colspan="2">Data de Pagamento</td>
			<td align="right"><?php echo $data_vencF ?></td>
		</tr>
		<?php } ?>

	<?php } ?>


		<tr>
			<td colspan="2">Vendedor</td>
			<td align="right"><?php echo $nome_usu_lanc ?></td>
		</tr>

		

	</tfoot>
</table>

<?php if($pago == 'Não'){ ?>
<br><br>
<div align="center">__________________________</div>
<div align="center"><small>Assinatura do Cliente</small></div>
<?php } ?>