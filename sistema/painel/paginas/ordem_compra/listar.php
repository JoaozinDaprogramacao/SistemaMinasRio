<?php 
@session_start();
$mostrar_registros = @$_SESSION['registros'];
$id_usuario = @$_SESSION['id'];
$tabela = 'ordem_compra';
require_once("../../../conexao.php");
require_once("../../verificar.php");


if($mostrar_registros == 'Não'){	
	$sql_usuario_lanc = " and usuario_lanc = '$id_usuario '";
}else{	
	$sql_usuario_lanc = " ";
}

$data_hoje = date('Y-m-d');
$data_atual = date('Y-m-d');
$mes_atual = Date('m');
$ano_atual = Date('Y');
$data_inicio_mes = $ano_atual."-".$mes_atual."-01";
$data_inicio_ano = $ano_atual."-01-01";

$data_ontem = date('Y-m-d', @strtotime("-1 days",@strtotime($data_atual)));
$data_amanha = date('Y-m-d', @strtotime("+1 days",@strtotime($data_atual)));


if($mes_atual == '04' || $mes_atual == '06' || $mes_atual == '09' || $mes_atual == '11'){
	$data_final_mes = $ano_atual.'-'.$mes_atual.'-30';
}else if($mes_atual == '02'){
	$bissexto = date('L', @mktime(0, 0, 0, 1, 1, $ano_atual));
	if($bissexto == 1){
		$data_final_mes = $ano_atual.'-'.$mes_atual.'-29';
	}else{
		$data_final_mes = $ano_atual.'-'.$mes_atual.'-28';
	}

}else{
	$data_final_mes = $ano_atual.'-'.$mes_atual.'-31';
}

$total_pago = 0;
$total_pendentes = 0;

$total_pagoF = 0;
$total_pendentesF = 0;

$dataInicial = @$_POST['p1'];
$dataFinal = @$_POST['p2'];
$tipo = @$_POST['p3'];


if($dataInicial == ""){
	$dataInicial = $data_inicio_mes;
}

if($dataFinal == ""){
	$dataFinal = $data_final_mes;
}



if($mostrar_registros == 'Não'){
	$query = $pdo->query("SELECT * from $tabela where usuario = '$id_usuario' and data >= '$dataInicial' and data <= '$dataFinal' and status like '%$tipo%' order by id desc ");
}else{
	$query = $pdo->query("SELECT * from $tabela where data >= '$dataInicial' and data <= '$dataFinal' and status like '%$tipo%' order by id desc ");
}



$res = $query->fetchAll(PDO::FETCH_ASSOC);
$linhas = @count($res);
if($linhas > 0){
echo <<<HTML
<small>
	<table class="table table-bordered text-nowrap border-bottom dt-responsive " id="tabela">
	<thead> 
	<tr> 	
	<th class="">Nº Compra</th>			
	<th class="">Valor</th>		
	<th class="esc">Status</th>	
	<th class="esc">Data</th>	
	<th>Ações</th>
	</tr> 
	</thead> 
	<tbody>	
	<small>
HTML;


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


if($status == 'Aprovada'){
	$classe_pago = 'bg-success';
	$ocultar = 'ocultar';
	$ocultar_pendentes = '';
	$classe_tipo = 'green';
}else{
	$classe_pago = 'bg-danger';
	$ocultar_pendentes = 'ocultar';
	$ocultar = '';
	$classe_tipo = 'red';
}	



echo <<<HTML

<tr>
<td><i class="fa fa-square mr-1" style="color:{$classe_tipo}"></i> Nº {$id}</td>
<td class="">R$ {$subtotalF} </td>	
<td class="esc"><span class="badge {$classe_pago} me-1 my-2 p-1"><big>{$status}</big></span></td>
<td class="esc">{$dataF}</td>

<td>
	<big><a class="btn btn-info btn-sm " href="#" onclick="editar('{$id}','{$desconto}','{$tipo_desconto}','{$frete}','{$forma_pgto}','{$obs}')" title="Editar Dados"><i class="fa fa-edit "></i></a></big>

	<div class="icones_mobile" class="dropdown" style="display: inline-block;">                      
                        <a  href="#" aria-expanded="false" aria-haspopup="true" data-bs-toggle="dropdown" class="dropdown btn btn-danger btn-sm"><i class="fa fa-trash "></i> </a>
                        <div  class="dropdown-menu tx-13">
                        <div class="dropdown-item-text botao_excluir">
                        <p>Confirmar Exclusão? <a href="#" onclick="excluir('{$id}')"><span class="text-danger">Sim</span></a></p>
                        </div>
                        </div>
                        </div>



<big><a class="{$ocultar} btn btn-success btn-sm" href="#" onclick="baixar('{$id}', 'Ordem Compra', '{$forma_pgto}', '{$valor}')" title="Aprovar {$tipo}"><i class="fa fa-check-square "></i></a></big>


		<big><a class="btn btn-secondary btn-sm" href="#" onclick="arquivo('{$id}', 'Ordem Compra')" title="Inserir / Ver Arquivos"><i class="fa fa-file-o " ></i></a></big>

		

			<form   method="POST" action="rel/ordem_compra_class.php" target="_blank" style="display:inline-block">
					<input type="hidden" name="id" value="{$id}">
					<big><button class="btn btn-primary btn-sm" title="PDF"><i class="fa fa-file-pdf-o "></i></button></big>
					</form>



	


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

}else{
	echo 'Nenhum Registro Encontrado!';
}
?>



<script type="text/javascript">
	$(document).ready( function () {		
    $('#tabela').DataTable({
    	"language" : {
            //"url" : '//cdn.datatables.net/plug-ins/1.13.2/i18n/pt-BR.json'
        },
        "ordering": false,
		"stateSave": true
    });
   
} );
</script>


<script type="text/javascript">
	function editar(id, desconto, tipo_desconto, frete, forma_pgto, obs){
		$('#mensagem').text('');
    	$('#titulo_inserir').text('Editar Registro');

    	

    	$('#id').val(id);
    	
    	$('#desconto').val(desconto);
    	
    	$('#tipo_desconto').val(tipo_desconto);
    	$('#obs').val(obs);
    	$('#frete').val(frete);
    	
    	$('#forma_pgto').val(forma_pgto).change();

    	setTimeout(function() {
		  tipoDesc(tipo_desconto)		  
		}, 600)

    	$('#modalForm').modal('show');


	}

	function limparCampos(){
		$('#id').val('');
    	    	  	    	
    	$('#desconto').val('');    	
    	$('#frete').val('');   	
    	$('#obs').val('');  

    	
    	$('#btn-deletar').hide();	
    	$('#btn-baixar').hide();	

    	listarItens();
	}

	function selecionar(id){

		var ids = $('#ids').val();

		if($('#seletor-'+id).is(":checked") == true){
			var novo_id = ids + id + '-';
			$('#ids').val(novo_id);
		}else{
			var retirar = ids.replace(id + '-', '');
			$('#ids').val(retirar);
		}

		var ids_final = $('#ids').val();
		if(ids_final == ""){
			$('#btn-deletar').hide();
			$('#btn-baixar').hide();
		}else{
			$('#btn-deletar').show();
			$('#btn-baixar').show();
		}
	}

	function deletarSel(){
		var ids = $('#ids').val();
		var id = ids.split("-");
		
		for(i=0; i<id.length-1; i++){
			excluirMultiplos(id[i]);			
		}

		setTimeout(() => {
		  	listar();	
		}, 1000);

		limparCampos();
	}


	function deletarSelBaixar(){
		var ids = $('#ids').val();
		var id = ids.split("-");

		for(i=0; i<id.length-1; i++){
			var novo_id = id[i];
				$.ajax({
					url: 'paginas/' + pag + "/baixar_multiplas.php",
					method: 'POST',
					data: {novo_id},
					dataType: "html",

					success:function(result){
						//alert(result)
						
					}
				});		
		}

		setTimeout(() => {
		  	buscar();
			limparCampos();
		}, 1000);

		
	}


	function permissoes(id, nome){
		    	
    	$('#id_permissoes').val(id);
    	$('#nome_permissoes').text(nome);    	

    	$('#modalPermissoes').modal('show');
    	listarPermissoes(id);
	}

	
		function parcelar(id, valor, nome){
    $('#id-parcelar').val(id);
    $('#valor-parcelar').val(valor);
    $('#qtd-parcelar').val('');
    $('#nome-parcelar').text(nome);
    $('#nome-input-parcelar').val(nome);
    $('#modalParcelar').modal('show');
    $('#mensagem-parcelar').text('');
}


function baixar(id, cliente, forma_pgto, valor){
	$('#id-baixar').val(id);
	$('#descricao-baixar').text(cliente);
	$('#saida-baixar').val(forma_pgto).change();	
	$('#valor-baixar').val(valor);	

	$('#modalBaixar').modal('show');
	$('#mensagem-baixar').text('');
}


function mostrarResiduos(id){

	$.ajax({
		url: 'paginas/' + pag + "/listar-residuos.php",
		method: 'POST',
		data: {id},
		dataType: "html",

		success:function(result){
			$("#listar-residuos").html(result);
		}
	});
	$('#modalResiduos').modal('show');
	
	
}

function arquivo(id, nome){
    $('#id-arquivo').val(id);    
    $('#nome-arquivo').text(nome);
    $('#modalArquivos').modal('show');
    $('#mensagem-arquivo').text(''); 
    $('#arquivo_conta').val('');
    listarArquivos();   
}


function cobrar(id){
	$.ajax({
		url: 'paginas/' + pag + "/cobrar.php",
		method: 'POST',
		data: {id},
		dataType: "html",

		success:function(result){
			alert(result);
		}
	});
}
	
</script>