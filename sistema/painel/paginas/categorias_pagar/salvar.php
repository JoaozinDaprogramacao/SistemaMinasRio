<?php
require_once("../../../conexao.php");
require_once("funcoes.php");
$tabela = 'categorias_pagar';

$id   = $_POST['id'];
$nome = trim($_POST['nome']);

if ($nome == '') {
    echo 'Digite o nome da categoria!';
    exit();
}

garantir_categoria_romaneio($pdo);

if ($id != '') {
    $atual = $pdo->query("SELECT nome, protegida FROM $tabela WHERE id = '$id'")->fetch(PDO::FETCH_ASSOC);
    if ($atual && $atual['protegida'] && $nome !== $atual['nome']) {
        echo 'Esta categoria é usada pelo sistema e não pode ser renomeada!';
        exit();
    }
}

$check = $pdo->query("SELECT id FROM $tabela WHERE nome = '$nome'");
$row   = $check->fetch(PDO::FETCH_ASSOC);
if ($row && $row['id'] != $id) {
    echo 'Categoria já cadastrada, escolha outro nome!';
    exit();
}

if ($id == '') {
    $q = $pdo->prepare("INSERT INTO $tabela SET nome = :nome");
} else {
    $q = $pdo->prepare("UPDATE $tabela SET nome = :nome WHERE id = '$id'");
}

$q->bindValue(':nome', $nome);
$q->execute();

echo 'Salvo com Sucesso';
