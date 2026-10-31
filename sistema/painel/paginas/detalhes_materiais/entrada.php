<?php
$tabela = 'entradas';
require_once("../../../conexao.php");
require_once("../../verificar.php");

$id_usuario = @$_SESSION['id'];

$quantidade_entrada = $_POST['quantidade_entrada'] ?? '';
$motivo_entrada = $_POST['motivo_entrada'] ?? '';
$id_material = intval($_POST['id'] ?? 0);

$quantidade_entrada = str_replace('.', '', $quantidade_entrada);
$quantidade_entrada = (float) str_replace(',', '.', $quantidade_entrada);

if($quantidade_entrada <= 0){
	echo 'Informe a quantidade!';
	exit();
}

$query = $pdo->prepare("INSERT INTO $tabela SET material = :material, quantidade = :quantidade, motivo = :motivo, usuario = :usuario, data = curDate() ");
$query->execute([':material' => $id_material, ':quantidade' => $quantidade_entrada, ':motivo' => $motivo_entrada, ':usuario' => $id_usuario]);

// Soma no estoque gravado (e não no valor que a tela tinha ao abrir o modal)
$pdo->prepare("UPDATE materiais SET estoque = estoque + :quantidade where id = :id")
	->execute([':quantidade' => $quantidade_entrada, ':id' => $id_material]);

echo 'Salvo com Sucesso';
