<?php 
$tabela = 'itens_compra';
require_once("../../../conexao.php");

@session_start();
$id_usuario = $_SESSION['id'];

$query = $pdo->query("SELECT * from $tabela where funcionario = '$id_usuario' and id_compra = '0' order by id asc");
$res = $query->fetchAll(PDO::FETCH_ASSOC);
$linhas = @count($res);
if($linhas > 0){
	for($i=0; $i<$linhas; $i++){
		$id_material = $res[$i]['material'];
		$quantidade = $res[$i]['quantidade'];
		$id = $res[$i]['id'];

		$query2 = $pdo->query("SELECT * from materiais where id = '$id_material'");
		$res2 = $query2->fetchAll(PDO::FETCH_ASSOC);
		$estoque = $res2[0]['estoque'];
		$tem_estoque = $res2[0]['tem_estoque'];
		$compras = $res2[0]['compras'];
		$unidade = $res[0]['unidade'];

		$query3 = $pdo->query("SELECT * FROM unidade_medida where id = '$unidade'");
		$res3 = $query3->fetchAll(PDO::FETCH_ASSOC);
		if(@count($res3) > 0){
			$nome_unidade = $res3[0]['nome'];
		}else{
			$nome_unidade = 'Sem Unidade';
		}

		$pdo->query("DELETE FROM $tabela WHERE id = '$id' ");

		if($tem_estoque == 'Sim'){
			$novo_estoque = $estoque - $quantidade;

			if($nome_unidade == 'Quilogramas' or $nome_unidade == 'Quilo' or $nome_unidade == 'Quilograma' or $nome_unidade == 'KG' or $nome_unidade == 'Metros' or $nome_unidade == 'Metro' or $nome_unidade == 'M' or $nome_unidade == 'm' or $nome_unidade == 'Litro' or $nome_unidade == 'Litros' or $nome_unidade == 'L'){
				$compras = $compras - 1;
			}else{
				$compras = $compras - $quantidade;
			}

			//atualizar os materiais na tabela materiais
			$pdo->query("UPDATE materiais SET estoque = '$novo_estoque', compras = '$compras' WHERE id = '$id_material'"); 
		}
	}
}

?>