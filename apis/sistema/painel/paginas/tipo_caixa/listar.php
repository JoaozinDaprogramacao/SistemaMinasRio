<?php 
$tabela = 'tipo_caixa';
$tabela_unidade_medida = 'unidade_medida';
require_once("../../../conexao.php");

$cat = @$_POST['p1'];

if($cat == ""){
	$filtrar = "";
}else{
	$filtrar = " where categoria = '$cat'";
}

$query = $pdo->query("
    SELECT 
        t1.id, 
        t1.tipo, 
        t2.unidade 
    FROM 
        $tabela t1
    LEFT JOIN 
        $tabela_unidade_medida t2 
    ON 
        t1.unidade_medida = t2.id 
    $filtrar
    ORDER BY 
        t1.id DESC
");
$res = $query->fetchAll(PDO::FETCH_ASSOC);
$linhas = @count($res);
if ($linhas > 0) {
    echo <<<HTML

    <table class="table table-bordered text-nowrap border-bottom dt-responsive" id="tabela">
    <thead> 
    <tr> 
    <th align="center" width="5%" class="text-center">Selecionar</th>
    <th>Tipo</th>    
    <th>Unidade de Medida</th>    
    <th>Ações</th>
    </tr> 
    </thead> 
    <tbody>    
HTML;


for ($i = 0; $i < $linhas; $i++) {
    $linha = $i + 1; // Ajusta para começar do 1


	$id = $res[$i]['id'];
	$tipo = $res[$i]['tipo'];
	$unidade_medida = $res[$i]['unidade'];
	


echo <<<HTML
<tr style="">
<td align="center">
<div class="custom-checkbox custom-control">
<input type="checkbox" class="custom-control-input" id="seletor-{$id}" onchange="selecionar('{$id}')">
<label for="seletor-{$id}" class="custom-control-label mt-1 text-dark"></label>
</div>
</td>
<td>{$tipo}</td>
<td>{$unidade_medida}</td>

<td>
	<big><a class="btn btn-info btn-sm" href="#" onclick="editar('{$id}','{$tipo}','{$unidade_medida}')" title="Editar Dados"><i class="fa fa-edit "></i></a></big>

	<div class="dropdown" style="display: inline-block;">                      
                        <a class="btn btn-danger btn-sm" href="#" aria-expanded="false" aria-haspopup="true" data-bs-toggle="dropdown" class="dropdown"><i class="fa fa-trash "></i> </a>
                        <div  class="dropdown-menu tx-13">
                        <div style="width: 240px; padding:15px 5px 0 10px;" class="dropdown-item-text">
                        <p>Confirmar Exclusão? <a href="#" onclick="excluir('{$id}')"><span class="text-danger">Sim</span></a></p>
                        </div>
                        </div>
                        </div>


<big><a class="btn btn-primary btn-sm" href="#" onclick="mostrar('{$tipo}','{$unidade_medida}')" title="Mostrar Dados"><i class="fa fa-info-circle "></i></a></big>

</td>
</tr>
HTML;

}

}else{
	echo 'Não possui nenhum cadastro!';
}


echo <<<HTML
</tbody>
<small><div align="center" id="mensagem-excluir"></div></small>
</table>

<br>		
			<p align="right" style="margin-top: -10px">
				<span style="margin-right: 10px">Total Itens  <span > {$linhas} </span></span>
				
			</p>

HTML;
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
	function editar(id, tipo, unidade_medida){

		$('#mensagem').text('');
    	$('#titulo_inserir').text('Editar Registro');

    	$('#id').val(id);
    	$('#tipo').val(tipo); 
    	$('#unidade_medida').val(unidade_medida).change();
    

    	$('#modalForm').modal('show');
	}


	function mostrar(tipo, unidade_medida){
		
    	$('#tipo').text(tipo);
    	$('#unidade_medida').text(unidade_medida);

    	$('#modalDados').modal('show');
	}




	function limparCampos(){
		$('#id').val('');
    	$('#tipo').val(''); 
    	$('#unidade_medidas').val('0');

    	$('#ids').val('');
    	$('#btn-deletar').hide();	
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
		}else{
			$('#btn-deletar').show();
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


</script>


</script>