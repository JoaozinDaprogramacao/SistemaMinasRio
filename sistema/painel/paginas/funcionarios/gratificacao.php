<?php
require_once("../../../conexao.php");

$id_funcionario = $_POST['id_funcionario'];
$valor = $_POST['valor'];
$data = $_POST['data'];
$descricao = $_POST['descricao'];

// Limpando o valor para salvar no DB
$valor = str_replace('.', '', $valor);
$valor = str_replace(',', '.', $valor);

if ($id_funcionario == "" || $valor == "" || $data == "") {
    echo 'Preencha todos os campos obrigatórios!';
    exit();
}

$query = $pdo->prepare("INSERT INTO gratificacoes (id_funcionario, valor, data, descricao, pago) VALUES (:id_funcionario, :valor, :data, :descricao, 'Não')");
$query->bindValue(":id_funcionario", $id_funcionario);
$query->bindValue(":valor", $valor);
$query->bindValue(":data", $data);
$query->bindValue(":descricao", $descricao);
$query->execute();

echo 'Salvo com Sucesso';
?>