<?php 
$tabela = 'saidas';
require_once("../../../conexao.php");

@session_start();
$id_usuario = @$_SESSION['id'];

$quantidade_saida = $_POST['quantidade_saida'];
$motivo_saida = $_POST['motivo_saida'];
$id_produto = $_POST['id'];
$estoque = $_POST['estoque'];

$quantidade_saida = str_replace('.', '', $quantidade_saida);
$quantidade_saida = str_replace(',', '.', $quantidade_saida);

$total_produtos = $estoque - $quantidade_saida;


$query = $pdo->prepare("INSERT INTO $tabela SET produto = '$id_produto', quantidade = '$quantidade_saida', motivo = :motivo, usuario = '$id_usuario', data = curDate() ");

$query->bindValue(":motivo", "$motivo_saida");
$query->execute();

$pdo->query("UPDATE produtos SET estoque = '$total_produtos' where id = '$id_produto' ");

echo 'Salvo com Sucesso';

?>