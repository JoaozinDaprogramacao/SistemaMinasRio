<?php
require_once("../../../conexao.php");

@session_start();
$id_usuario = $_SESSION['id'];
$id = intval($_POST['id'] ?? 0);

// O estoque só é abatido ao fechar a venda, então remover do carrinho não devolve nada
$query = $pdo->prepare("DELETE FROM itens_venda WHERE id = :id AND funcionario = :f AND id_venda = 0");
$query->execute([':id' => $id, ':f' => $id_usuario]);

if ($query->rowCount() > 0) {
	echo "Excluído com Sucesso";
} else {
	echo "Item não encontrado no carrinho!";
}
