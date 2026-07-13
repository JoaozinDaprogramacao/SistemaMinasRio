<?php
require_once("../../../conexao.php");

$valor = str_replace(',', '.', $_POST['valor']);
$pgto = $_POST['pgto'];

$query = $pdo->query("SELECT taxa FROM formas_pgto WHERE id = '$pgto'");
$res = $query->fetchAll(PDO::FETCH_ASSOC);

// VERIFICAÇÃO DE SEGURANÇA:
if (@count($res) > 0) {
    $taxa = $res[0]['taxa'];
    $total_taxa = ($taxa * $valor) / 100;
    echo $total_taxa;
} else {
    // Se não encontrar a forma de pagamento, retorna zero para não quebrar o JS
    echo 0;
}
