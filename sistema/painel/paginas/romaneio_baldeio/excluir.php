<?php 
$tabela = 'baldeio';
require_once("../../../conexao.php");

$id = $_POST['id'];

$query = $pdo->query("SELECT * FROM $tabela where id = '$id'");
$res = $query->fetchAll(PDO::FETCH_ASSOC);

$pdo->query("DELETE FROM $tabela WHERE id = '$id' ");

$pdo->query("DELETE FROM pagar WHERE id_baldeio = '$id' ");


echo 'Excluído com Sucesso';
?>