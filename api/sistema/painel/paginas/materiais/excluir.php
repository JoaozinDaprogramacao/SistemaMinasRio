<?php 
$tabela = 'materiais';
$tabela_detalhes = 'detalhes_materiais';
require_once("../../../conexao.php");

$id = $_POST['id'];

$pdo->query("DELETE FROM $tabela WHERE id = '$id' ");
$pdo->query("DELETE from $tabela_detalhes where material_id = '$id'");
echo 'Excluído com Sucesso';
?>