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

$saldo = str_replace(',', '.', $saldo);

if($id == ""){
    $query = $pdo->prepare("INSERT INTO $tabela SET correntista = :correntista, banco = :banco, agencia = :agencia, conta = :conta, saldo = :saldo, id_usuario = '$id_usuario'");
} else {
    $query = $pdo->prepare("UPDATE $tabela SET correntista = :correntista, banco = :banco, agencia = :agencia, conta = :conta, saldo = :saldo, where id = '$id'");
}

$query->bindValue(":correntista", "$correntista");
$query->bindValue(":banco", "$banco");
$query->bindValue(":agencia", "$agencia");
$query->bindValue(":conta", "$conta");
$query->bindValue(":saldo", "$saldo");
$query->execute();

echo 'Salvo com Sucesso';
?>