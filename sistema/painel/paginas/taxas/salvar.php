<?php 
$tabela = 'taxas_abatimentos';
require_once("../../../conexao.php");

$descricao = $_POST['descricao'];
$id = $_POST['id'];

$info = $_POST['info'];

$valor_taxa = $_POST['valor_taxa'];

if($id == ""){
    $query = $pdo->prepare("INSERT INTO $tabela SET descricao = :desc, info = :info, valor_taxa = :valor");
}else{
    $query = $pdo->prepare("UPDATE $tabela SET descricao = :desc, info = :info, valor_taxa = :valor WHERE id = :id");
    $query->bindValue(":id", "$id");
}

$query->bindValue(":desc", "$descricao");
$query->bindValue(":info", "$info");
$query->bindValue(":valor", "$valor_taxa");
$query->execute();

echo 'Salvo com Sucesso';
?>