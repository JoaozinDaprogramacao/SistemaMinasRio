<?php
require_once("../../../conexao.php");
$tabela = 'categorias_pagar';

$id = $_POST['id'];
$pdo->query("DELETE FROM $tabela WHERE id = '$id'");
echo 'Excluído com Sucesso';
