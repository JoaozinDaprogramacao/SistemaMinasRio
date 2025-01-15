<?php 
$tabela = 'romaneio_compra';
require_once("../../../conexao.php");

$id = $_POST['id'];

$query = $pdo->query("SELECT * FROM $tabela where id = '$id'");
$res = $query->fetchAll(PDO::FETCH_ASSOC);

$pdo->query("DELETE FROM $tabela WHERE id = '$id' ");
$pdo->query("DELETE FROM linha_produto WHERE id_romaneio = '$id' ");
$pdo->query("DELETE FROM linha_comissao WHERE id_romaneio = '$id' ");
$pdo->query("DELETE FROM linha_observacao WHERE id_romaneio = '$id' ");

$pdo->query("DELETE FROM pagar WHERE id_romaneio = '$id' ");


echo 'Excluído com Sucesso';
?>