<?php
$tabela = 'saidas';
require_once("../../../conexao.php");
require_once("../../verificar.php");

$id_usuario = @$_SESSION['id'];

$quantidade_saida = $_POST['quantidade_saida'] ?? '';
$motivo_saida = $_POST['motivo_saida'] ?? '';
$id_material = intval($_POST['id'] ?? 0);

$quantidade_saida = str_replace('.', '', $quantidade_saida);
$quantidade_saida = (float) str_replace(',', '.', $quantidade_saida);

if($quantidade_saida <= 0){
	echo 'Informe a quantidade!';
	exit();
}

$query = $pdo->prepare("INSERT INTO $tabela SET material = :material, quantidade = :quantidade, motivo = :motivo, usuario = :usuario, data = curDate() ");
$query->execute([':material' => $id_material, ':quantidade' => $quantidade_saida, ':motivo' => $motivo_saida, ':usuario' => $id_usuario]);

// Abate do estoque gravado (e não do valor que a tela tinha ao abrir o modal)
$pdo->prepare("UPDATE materiais SET estoque = estoque - :quantidade where id = :id")
	->execute([':quantidade' => $quantidade_saida, ':id' => $id_material]);

echo 'Salvo com Sucesso';
