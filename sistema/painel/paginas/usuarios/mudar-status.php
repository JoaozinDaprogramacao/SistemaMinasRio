<?php 
$tabela = 'usuarios';
require_once("../../../conexao.php");
require_once(__DIR__ . "/../../seguranca.php");
seguranca_exigir_acesso(['usuarios']);

$id = $_POST['id'];
$acao = $_POST['acao'];

$pdo->query("UPDATE $tabela SET ativo = '$acao' WHERE id = '$id' ");
echo 'Alterado com Sucesso';
?>