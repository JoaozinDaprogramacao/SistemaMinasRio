<?php 
$tabela = 'acessos';
require_once("../../../conexao.php");
require_once(__DIR__ . "/../../seguranca.php");
seguranca_exigir_acesso(['acessos']);

if($alterar_acessos != 'Sim'){
	echo 'Você não tem permissão para alterar os acessos!';
	exit();
}

$id = $_POST['id'];

$pdo->query("DELETE FROM $tabela WHERE id = '$id' ");
echo 'Excluído com Sucesso';
?>