<?php 
$tabela = 'itens_compra';
require_once("../../../conexao.php");

@session_start();
$id_usuario = $_SESSION['id'];

$quantidade = $_POST['quantidade'];
$quantidade = str_replace('.', '', $quantidade);
$quantidade = str_replace(',', '.', $quantidade);
$id_material = $_POST['id_material'];

$query = $pdo->query("SELECT * from materiais where id = '$id_material'");
$res = $query->fetchAll(PDO::FETCH_ASSOC);

// Verifica se o valor_compra existe e é válido
$valor = isset($res[0]['valor_compra']) ? $res[0]['valor_compra'] : 0;

// Remove a validação do valor aqui, já que será definido depois
$total = $quantidade * $valor;

// Insere o item sem atualizar o estoque
$pdo->query("INSERT INTO itens_compra SET 
    material = '$id_material', 
    quantidade = '$quantidade', 
    valor = '$valor', 
    total = '$total', 
    id_compra = 0, 
    funcionario = '$id_usuario'");

echo 'Inserido com Sucesso';