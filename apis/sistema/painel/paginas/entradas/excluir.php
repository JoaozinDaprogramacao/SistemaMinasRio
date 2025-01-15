<?php 
$tabela = 'entradas';
require_once("../../../conexao.php");

$id = $_POST['id'];

$query = $pdo->query("SELECT * FROM $tabela where id = '$id'");
$res = $query->fetchAll(PDO::FETCH_ASSOC);
$id_produto = $res[0]['produto'];
$quantidade = $res[0]['quantidade'];

$query = $pdo->query("SELECT * FROM produtos where id = '$id_produto'");
$res = $query->fetchAll(PDO::FETCH_ASSOC);
$estoque = @$res[0]['estoque'];

$total = $estoque - $quantidade;

$pdo->query("UPDATE produtos SET estoque = '$total' WHERE id = '$id_produto' ");
$pdo->query("DELETE FROM $tabela WHERE id = '$id' ");
echo 'Excluído com Sucesso';
?>