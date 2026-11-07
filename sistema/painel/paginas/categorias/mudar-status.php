<?php 
$tabela = 'categorias';
require_once("../../../conexao.php");
require_once(__DIR__ . "/../../seguranca.php");
seguranca_exigir_acesso(['categorias']);

$id = $_POST['id'];
$acao = $_POST['acao'];

$pdo->query("UPDATE $tabela SET ativo = '$acao' WHERE id = '$id' ");
echo 'Alterado com Sucesso';
?>