<?php
require_once("../../../conexao.php");
require_once("funcoes_venda.php");

@session_start();
$id_usuario = $_SESSION['id'];

if (!isset($_POST['id']) || !isset($_POST['preco'])) {
    echo "Erro: Dados incompletos.";
    exit;
}

$id = intval($_POST['id']);
$preco = venda_valor_br($_POST['preco']);

if ($id <= 0 || $preco < 0) {
    echo "Erro: Dados inválidos.";
    exit;
}

// Só itens do carrinho do próprio usuário: venda fechada se altera pela edição
$query = $pdo->prepare("UPDATE itens_venda SET valor = :preco, total = quantidade * :preco2 WHERE id = :id AND funcionario = :f AND id_venda = 0");
$query->execute([':preco' => $preco, ':preco2' => $preco, ':id' => $id, ':f' => $id_usuario]);

echo "Atualizado com Sucesso";
