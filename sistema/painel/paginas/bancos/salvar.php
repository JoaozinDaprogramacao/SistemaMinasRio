<?php
$tabela = 'bancos';
require_once("../../../conexao.php");

@session_start();
$id_usuario = @$_SESSION['id'];

$correntista = $_POST['correntista'];
$banco = $_POST['banco'];
$agencia = $_POST['agencia'];
$conta = $_POST['conta'];
$saldo = $_POST['saldo'];
$id = $_POST['id'];

// Trata o saldo: remove "R$ ", tira o ponto de milhar e troca a vírgula decimal por ponto
$saldo = str_replace('R$', '', $saldo);
$saldo = str_replace(' ', '', $saldo);
$saldo = str_replace('.', '', $saldo);
$saldo = str_replace(',', '.', $saldo);

if ($id == "") {
    $query = $pdo->prepare("INSERT INTO $tabela SET correntista = :correntista, banco = :banco, agencia = :agencia, conta = :conta, saldo = :saldo, id_usuario = :id_usuario");
    $query->bindValue(":id_usuario", "$id_usuario");
} else {
    // CORREÇÃO: Removida a vírgula antes do WHERE e adicionado o :id
    $query = $pdo->prepare("UPDATE $tabela SET correntista = :correntista, banco = :banco, agencia = :agencia, conta = :conta, saldo = :saldo WHERE id = :id");
    $query->bindValue(":id", "$id");
}

$query->bindValue(":correntista", "$correntista");
$query->bindValue(":banco", "$banco");
$query->bindValue(":agencia", "$agencia");
$query->bindValue(":conta", "$conta");
$query->bindValue(":saldo", "$saldo");

$query->execute();

echo 'Salvo com Sucesso';
