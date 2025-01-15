<?php 
$tabela = 'itens_compra';
require_once("../../../conexao.php");
@session_start();
$id_usuario = $_SESSION['id'];
$desconto = @$_POST['desconto'];

$tipo_desconto = @$_POST['tipo_desconto'];
$frete = @$_POST['frete'];
$frete = str_replace(',', '.', $frete);

$id_orc = @$_POST['id'];

if($frete == ""){
	$frete = 0;
}

if($desconto == ""){
	$desconto = 0;
}


$total_v = 0;

//buscar o total da venda
if($id_orc == ""){
	$query = $pdo->query("SELECT * from $tabela where funcionario = '$id_usuario' and id_orcamento = '0' order by id asc");	
}else{
	$query = $pdo->query("SELECT * from $tabela where id_orcamento = '$id_orc' order by id asc");	
}

$res = $query->fetchAll(PDO::FETCH_ASSOC);
$linhas = @count($res);
if($linhas > 0){
	for($i=0; $i<$linhas; $i++){	
		$total_das_vendas = $res[$i]['total'];
		$total_v += $total_das_vendas;
	}
}

if($tipo_desconto == '%'){
	if($desconto > 0 and $total_v > 0){
		$total_final = -($total_v * $desconto / 100);
	}else{
		$total_final = 0;
	}
	
}else{
	$total_final = -$desconto;
}

$total_final = $total_final + $frete;

if($id_orc == ""){
	$query = $pdo->query("SELECT * from $tabela where funcionario = '$id_usuario' and id_orcamento = '0' order by id desc");
}else{
	$query = $pdo->query("SELECT * from $tabela where funcionario = '$id_usuario' and id_orcamento = '$id_orc' order by id desc");
}
$res = $query->fetchAll(PDO::FETCH_ASSOC);
$linhas = @count($res);
echo '<div style="overflow:auto; max-height:270px; width:100%; scrollbar-width: thin;">';
echo '<table class="table table-bordered text-nowrap border-bottom dt-responsive ">';
	echo '<small> <thead> 
	<tr> 	
	<th>Produto</th>	
	<th align="center" class="">Quantidade</th>	
	<th align="center" class="esc">Valor Unit</th>	
	<th align="center" class="esc">Total</th>	
	<th align="center" class="esc">Ações</th>		
	</tr> 
	</thead> 
	<tbody>	
	</small>';
	

if($linhas > 0){
	for($i=0; $i<$linhas; $i++){
	$id = $res[$i]['id'];
	$produto = $res[$i]['produto'];
	$valor = $res[$i]['valor'];
	$quantidade = $res[$i]['quantidade'];
	$total = $res[$i]['total'];
	


	$total_final += $total;
	$total_finalF = number_format($total_final, 2, ',', '.');
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

	echo '<tr>';
	echo '<td ><img src="images/produtos/'.$foto_produto.'" width="20px"> <span class="'.$ocultar_quantidades.'">'.$quantidadeF.'</span> '.$nome_produtoF.'</td>';
	echo '<td align="center"> 
	<a class="'.$ocultar_quantidades.'" href="#" onclick="diminuir('.$id.', '.$quantidadeF.')"><big><i class="fa fa-minus-circle text-danger" ></i></big></a> <input style="border:none; border-bottom:1px solid #000; outline:none; background:transparent; width:35px; text-align:center" id="quant_'.$id.'" value="'.$quantidadeF.'" onblur="editarItem('.$id.')">
	'.$sigla_unidade.'
	<a class="'.$ocultar_quantidades.'" href="#" onclick="aumentar('.$id.', '.$quantidadeF.')"><big><i class="fa fa-plus-circle text-success" ></i></big></a>
	</td>';
	echo '<td align="center"> R$ <input style="border:none; border-bottom:1px solid #000; outline:none; background:transparent; width:70px; text-align:center" id="valor_'.$id.'" value="'.$valorF.'" onblur="editarItem('.$id.')"> </td>';
	echo '<td align="center"> R$ '.$totalF.' </td>';
	echo '<td align="center"> 

	<big><a title="Editar Item" href="#" onclick="editarItem('.$id.')"><i class="fa fa-check" style="color:blue"></i></a></big> 

		<big><a title="Remover Item" href="#" onclick="excluirItem('.$id.')"><i class="fa fa-trash" style="color:red"></i></a></big> 

		</td>';
	echo '</tr>'; 	
	
	
	}
}


echo '</table>';

$total_finalF = number_format($total_final, 2, ',', '.');
echo '<div align="right" style="margin-top:10px; font-size:14px; border-top:1px solid #8f8f8f;" >';
echo '<br>';
echo '<span style="margin-right:40px;">Itens: <b>('.$linhas.')</b></span>';
echo '<span>Subtotal: </span>';
echo '<span style="font-weight:bold"> R$ ';
echo $total_finalF;
echo '</span>';

echo '</div>';


?>

<script type="text/javascript">
	var itens = "<?=$linhas?>";
	
	$('#subtotal_venda').val('<?=$total_final?>')
	if(itens > 0){
		$("#btn_limpar").show();
		$("#btn_venda").show();
	}else{
		$("#btn_limpar").hide();
		$("#btn_venda").hide();
	}
	function excluirItem(id){
		 $.ajax({
        url: 'paginas/' + pag + "/excluir-item.php",
        method: 'POST',
        data: {id},
        dataType: "html",

        success:function(mensagem){
            if (mensagem.trim() == "Excluído com Sucesso") {           	
                listarItens();
            } else {
                $('#mensagem-excluir').addClass('text-danger')
                $('#mensagem-excluir').text(mensagem)
            }
        }
    });
	}



	function editarItem(id){

		var valor = $('#valor_'+id).val();
		var quantidade = $('#quant_'+id).val();
		
		 $.ajax({
        url: 'paginas/' + pag + "/editar-item.php",
        method: 'POST',
        data: {id, valor, quantidade},
        dataType: "html",

        success:function(mensagem){
        	
            if (mensagem.trim() == "Editado com Sucesso") {  

                listarItens();
            } else {
                $('#mensagem-excluir').addClass('text-danger')
                $('#mensagem-excluir').text(mensagem)
            }
        }
    });
	}

	function diminuir(id, quantidade){
		 $.ajax({
        url: 'paginas/' + pag + "/diminuir.php",
        method: 'POST',
        data: {id, quantidade},
        dataType: "html",

        success:function(mensagem){

            if (mensagem.trim() == "Excluído com Sucesso") {           	
                listarItens();
            } else {
                $('#mensagem-excluir').addClass('text-danger')
                $('#mensagem-excluir').text(mensagem)
            }
        }
    });
	}


	function aumentar(id, quantidade){
		 $.ajax({
        url: 'paginas/' + pag + "/aumentar.php",
        method: 'POST',
        data: {id, quantidade},
        dataType: "html",

        success:function(mensagem){
        	
            if (mensagem.trim() == "Excluído com Sucesso") {           	
                listarItens();
            } else {
            	alert(mensagem)
                $('#mensagem-excluir').addClass('text-danger')
                $('#mensagem-excluir').text(mensagem)
            }
        }
    });
	}

	
</script>