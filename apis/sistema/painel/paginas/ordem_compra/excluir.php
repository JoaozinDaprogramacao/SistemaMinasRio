<?php 
$tabela = 'ordem_compra';
require_once("../../../conexao.php");

$id = $_POST['id'];


$pdo->query("DELETE FROM $tabela WHERE id = '$id' ");
$pdo->query("DELETE FROM itens_compra WHERE id_orcamento = '$id' ");
echo 'Excluído com Sucesso';
?>