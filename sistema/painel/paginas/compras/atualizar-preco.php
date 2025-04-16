<?php
require_once("../../../conexao.php");

if (!isset($_POST['id']) || !isset($_POST['preco'])) {
    echo "Erro: Dados incompletos.";
    exit;
}

$id = intval($_POST['id']);
$preco = floatval($_POST['preco']);

if ($id <= 0 || $preco < 0) {
    echo "Erro: Dados inválidos.";
    exit;
}

try {
    $query = $pdo->prepare("UPDATE itens_compra SET valor = :preco, total = quantidade * :preco WHERE id = :id");
    $query->bindParam(':preco', $preco);
    $query->bindParam(':id', $id);
    $query->execute();

    if ($query->rowCount() > 0) {
        echo "Atualizado com Sucesso";
    } else {
        echo "Erro: Produto não encontrado ou preço não alterado.";
    }
} catch (PDOException $e) {
    echo "Erro ao atualizar o preço: " . $e->getMessage();
}
?>
