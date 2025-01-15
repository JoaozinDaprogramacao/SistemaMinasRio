<?php 
$tabela = 'materiais';
require_once("../../../conexao.php");

$id = @$_POST['p1'];
$busca = @$_POST['p2'];


if($busca == ""){
	$query = $pdo->query("SELECT * from $tabela order by id asc");
}else{
	$query = $pdo->query("SELECT * from $tabela where (nome LIKE '%$busca%') order by id asc");
}

$res = $query->fetchAll(PDO::FETCH_ASSOC);
$linhas = @count($res);
if($linhas > 0){
	for($i=0; $i<$linhas; $i++){
	$id = $res[$i]['id'];
	$nome = $res[$i]['nome'];	
	

}
}else{
	echo '<p>Nenhum Material Encontrado!</p>';
}
?>


<script type="text/javascript">
	$(document).ready( function () {
		
	//campo buscar
	$('#nome_categoria').text('<?=$nome_cat?>')

	var busca = $('#txt_buscar').val();
	var id_cat = '<?=$id_cat?>';

	if(id_cat != ""){
		$('#txt_buscar').val('');
	}

	if(busca != ""){
		$('#area_cat').hide();
	}else{
		$('#area_cat').show();
	}

	});

	function produto(id, nome, valor, codigo){

		$('#mensagem').text('');
    	$('#titulo_inserir').text('Venda: '+nome);
    	
    	$('#id').val(id);     	
    	$('#quantidade').val('1');
    	$('#codigo').val(codigo);


    	
    	$('#modalForm').modal('show');
    	listarVendas();
	}
</script>