<?php
require_once("../../../conexao.php");

$id_funcionario = $_POST['id_funcionario'];
$valor = $_POST['valor'];
$data = $_POST['data'];
$forma_pgto = $_POST['forma_pgto'];

$valor = str_replace('.', '', $valor);
$valor = str_replace(',', '.', $valor);

if ($id_funcionario == "" || $valor == "" || $data == "") {
    echo 'Preencha todos os campos obrigatórios!';
    exit();
}

$query = $pdo->prepare("INSERT INTO adiantamentos (id_funcionario, valor, data, forma_pgto, pago) VALUES (:id_funcionario, :valor, :data, :forma_pgto, 'Sim')");
$query->bindValue(":id_funcionario", $id_funcionario);
$query->bindValue(":valor", $valor);
$query->bindValue(":data", $data);
$query->bindValue(":forma_pgto", $forma_pgto);
$query->execute();

echo 'Salvo com Sucesso';
?>