<?php 
$tabela = 'tipo_caixa';
require_once("../../../conexao.php");

$tipo = $_POST['tipo'];
$tipo = str_replace(',', '.', $tipo); 
$unidade_medida = $_POST['unidade_medida'];
$unidade_medida = str_replace('.', '', $unidade_medida);
$unidade_medida = str_replace(',', '.', $unidade_medida);
$id = $_POST['id'];


//validacao
$query = $pdo->query("SELECT * from $tabela where tipo = '$tipo'");
$res = $query->fetchAll(PDO::FETCH_ASSOC);
$id_reg = @$res[0]['id'];
if(@count($res) > 0 and $id != $id_reg){
	echo 'tipo já Cadastrado!';
	exit();
}

if($id == ""){
$query = $pdo->prepare("INSERT INTO $tabela SET tipo = :tipo, unidade_medida = :unidade_medida");
	
}else{
$query = $pdo->prepare("UPDATE $tabela SET tipo = :tipo, unidade_medida = :unidade_medida where id = '$id'");
}
$query->bindValue(":tipo", "$tipo");
$query->bindValue(":unidade_medida", "$unidade_medida");
$query->execute();

echo 'Salvo com Sucesso';


 ?>
