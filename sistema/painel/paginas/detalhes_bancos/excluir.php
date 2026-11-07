<?php 
$tabela = 'materials';
$tabela_detalhes = 'detalhes_materiais';
require_once("../../../conexao.php");
require_once(__DIR__ . "/../../seguranca.php");
seguranca_exigir_acesso(['produtos']);

$id = $_POST['id'];

$pdo->query("DELETE FROM $tabela WHERE id = '$id' ");
$pdo->query("DELETE from $tabela_detalhes where material_id = '$id'");
echo 'Excluído com Sucesso';
?>