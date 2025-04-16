<?php 
$tabela = 'saidas';
require_once("../../../conexao.php");

$dataInicial = @$_POST['p1'];
$dataFinal = @$_POST['p2'];

if($dataInicial != "" and $dataFinal != ""){
	$query = $pdo->query("SELECT * from $tabela where data >= '$dataInicial' and data <= '$dataFinal' order by id asc");
}else{
	$query = $pdo->query("SELECT * from $tabela order by id desc limit 50");
}

$res = $query->fetchAll(PDO::FETCH_ASSOC);
$linhas = @count($res);
if($linhas > 0){
echo <<<HTML
<small>
	<table class="table table-striped table-hover table-bordered text-nowrap border-bottom dt-responsive" id="tabela">
	<thead>
	<tr> 
	<th>Material</th>	
	<th>Quantidade</th>		
	<th>Motivo</th>
	<th>Usuário</th>
	<th>Data</th>	
	<th>Excluir</th>
	</tr> 
	</thead> 
	<tbody>	
HTML;

for($i=0; $i<$linhas; $i++){
	$id = $res[$i]['id'];
	$material = $res[$i]['material'];	
	$quantidade = $res[$i]['quantidade'];	
	$motivo = $res[$i]['motivo'];
	$usuario = $res[$i]['usuario'];
	$data = $res[$i]['data'];
	
	
	$dataF = implode('/', array_reverse(@explode('-', $data)));

	$query2 = $pdo->query("SELECT * from materiais where id = '$material'");
	$res2 = $query2->fetchAll(PDO::FETCH_ASSOC);
	$nome_material = @$res2[0]['nome'];
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
	

echo <<<HTML
<tr>
<td>{$nome_material}</td>
<td>{$quantidadeF} <small><span style="color:blue">({$nome_unidade})</span></small></td>
<td>{$motivo}</td>
<td>{$nome_usuario}</td>
<td>{$dataF}</td>
<td>	

	<div class="dropdown" style="display: inline-block;">                      
	<a class="btn btn-danger btn-sm" href="#" aria-expanded="false" aria-haspopup="true" data-bs-toggle="dropdown" class="dropdown"><i class="fa fa-trash"></i></a>
	<div  class="dropdown-menu tx-13">
	<div style="width: 240px; padding:15px 5px 0 10px;" class="dropdown-item-text">
	<p>Confirmar Exclusão? <a href="#" onclick="excluir('{$id}')"><span class="text-danger"><button class="btn-danger">Sim</button></span></a></p>
	</div>
	</div>
	</div>



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

