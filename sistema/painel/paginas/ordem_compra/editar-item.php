<?php 
$tabela = 'itens_compra';
require_once("../../../conexao.php");
require_once(__DIR__ . "/../../seguranca.php");
seguranca_exigir_login();

$id = $_POST['id'];
$valor = $_POST['valor'];
$quantidade = $_POST['quantidade'];

$valor = str_replace(',', '.', $valor);
$quantidade = str_replace(',', '.', $quantidade);

$valor_total = $valor * $quantidade;

$pdo->query("UPDATE $tabela SET valor = '$valor', quantidade = '$quantidade', total = '$valor_total' WHERE id = '$id' ");
echo 'Editado com Sucesso';

?>