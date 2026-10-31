<?php
$tabela = 'receber';
require_once("../../../conexao.php");
require_once("../vendas/funcoes_venda.php");

@session_start();
$nivel = @$_SESSION['nivel'];
$id_usuario = @$_SESSION['id'];

$id = intval($_POST['id'] ?? 0);

$query = $pdo->prepare("SELECT id_venda_real FROM $tabela WHERE id = :id");
$query->execute([':id' => $id]);
$id_venda_real = $query->fetchColumn();

if (!$id_venda_real) {
	echo 'Lançamento não encontrado ou não pertence a uma venda!';
	exit();
}

// Cancelar qualquer lançamento cancela a venda inteira (estoque, contas e comissão)
$erro = venda_cancelar($pdo, $id_venda_real, $id_usuario, $nivel);

echo ($erro == '') ? 'Excluído com Sucesso' : $erro;
