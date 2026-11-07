<?php 
$tabela = 'tarefas';
require_once("../../../conexao.php");
require_once(__DIR__ . "/../../seguranca.php");
seguranca_exigir_acesso(['tarefas']);

$id = $_POST['id'];

$query = $pdo->query("SELECT * FROM $tabela where id = '$id'");
$res = $query->fetchAll(PDO::FETCH_ASSOC);
$hash = @$res[0]['hash'];

if($hash != ""){
	require("../../apis/cancelar_agendamento.php");
}


$pdo->query("UPDATE $tabela SET status = 'Concluída' WHERE id = '$id' ");
echo 'Concluído com Sucesso';
?>