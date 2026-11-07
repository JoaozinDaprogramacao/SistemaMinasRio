<?php 
$tabela = 'orcamentos';
require_once("../../../conexao.php");
require_once(__DIR__ . "/../../seguranca.php");
seguranca_exigir_acesso(['orcamento']);

$id = $_POST['id'];


$pdo->query("DELETE FROM $tabela WHERE id = '$id' ");
$pdo->query("DELETE FROM itens_orc WHERE id_orcamento = '$id' ");
echo 'Excluído com Sucesso';
?>