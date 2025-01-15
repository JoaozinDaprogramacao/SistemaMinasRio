<?php 
$tabela = 'entradas';
require_once("../../../conexao.php");

@session_start();
$id_usuario = @$_SESSION['id'];

$quantidade_entrada = $_POST['quantidade_entrada'];
$motivo_entrada = $_POST['motivo_entrada'];
$id_material = $_POST['id'];
$estoque = $_POST['estoque'];

$quantidade_entrada = str_replace('.', '', $quantidade_entrada);
$quantidade_entrada = str_replace(',', '.', $quantidade_entrada);

$total_materiais = $estoque + $quantidade_entrada;

$query = $pdo->prepare("INSERT INTO $tabela SET material = '$id_material', quantidade = '$quantidade_entrada', motivo = :motivo, usuario = '$id_usuario', data = curDate() ");

$query->bindValue(":motivo", "$motivo_entrada");
$query->execute();

$pdo->query("UPDATE materiais SET estoque = '$total_materiais' where id = '$id_material' ");

echo 'Salvo com Sucesso';

?>