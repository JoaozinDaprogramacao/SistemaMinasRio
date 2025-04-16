<?php 
require_once("../../../conexao.php");
$pagina = 'clientes';

$valor = @$_POST['valor'];

echo '<select class="sel2" name="fornecedor" id="forneceor" style="width:100%;" onchange="trocarCliente()">';

if($valor == ""){
	echo '<option value="">Selecionar Fornecedor</option>';
}

$query = $pdo->query("SELECT * FROM fornecedores order by id desc");
$res = $query->fetchAll(PDO::FETCH_ASSOC);
for($i=0; $i < @count($res); $i++){
	foreach ($res[$i] as $key => $value){}
	$id_cliente = $res[0]['id'];
	echo '<option value="'.$res[$i]['id'].'">'.$res[$i]['nome_atacadista'].' - '.$res[$i]['cpf'].'</option>';

}

echo '</select>';

?>


	<script type="text/javascript">
			$(document).ready(function() {
			var valor = "<?=$valor?>";	
			var id_cliente = "<?=$id_cliente?>";	
			if(valor == '1'){
				$('#cliente_input').val(id_cliente);
			}
			

				$('.sel2').select2({
					//dropdownParent: $('#modalForm')
				});
			});

			function trocarCliente(){
				$('#cliente_input').val($('#cliente').val());
			}
	</script>

