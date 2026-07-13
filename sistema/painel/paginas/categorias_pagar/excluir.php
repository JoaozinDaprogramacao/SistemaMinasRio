<?php
require_once("../../../conexao.php");
require_once("funcoes.php");
$tabela = 'categorias_pagar';

$id = $_POST['id'];
garantir_categoria_romaneio($pdo);

$check = $pdo->query("SELECT protegida FROM $tabela WHERE id = '$id'")->fetch(PDO::FETCH_ASSOC);
if ($check && $check['protegida']) {
    echo 'Esta categoria é usada pelo sistema e não pode ser excluída!';
    exit();
}

$pdo->query("DELETE FROM $tabela WHERE id = '$id'");
echo 'Excluído com Sucesso';
