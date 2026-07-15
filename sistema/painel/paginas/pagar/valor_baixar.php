<?php
$tabela = 'pagar';
require_once("../../../conexao.php");

$ids = $_POST['ids'];
$total_contas = 0;

$ids = substr($ids, 0, -1);
$separar = explode("-", $ids);
$total_ids = @count($separar);

for($i=0; $i<$total_ids; $i++){
		$id = $separar[$i];
		$query = $pdo->query("SELECT * FROM $tabela where id = '$id' and pago != 'Sim'");
		$res = $query->fetchAll(PDO::FETCH_ASSOC);
		if(@count($res) > 0){
			$conta = $res[0];
			$valor = ($conta['pago'] === 'Parcial') ? $conta['valor_restante'] : ($conta['subtotal'] ?: $conta['valor']);
			$total_contas += $valor;
		}
    }

$total_contas = @number_format($total_contas, 2, ',', '.');
echo $total_contas;
?>