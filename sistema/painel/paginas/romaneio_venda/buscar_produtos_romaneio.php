<?php
// sistema/painel/paginas/romaneio_venda/buscar_produtos_romaneio.php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
require_once("../../../conexao.php");

$ids = $_POST['ids'];
$ids_array = is_array($ids) ? $ids : explode(',', $ids);

// Prepara placeholders para a query
$placeholders = str_repeat('?,', count($ids_array) - 1) . '?';

    $query = $pdo->prepare("SELECT 
        lpc.*,
        p.nome as nome_produto,
        tc.tipo as tipo_caixa
        FROM linha_produto_compra lpc
        LEFT JOIN produtos p ON lpc.variedade = p.id 
        LEFT JOIN tipo_caixa tc ON lpc.tipo_caixa = tc.id
        WHERE lpc.id_romaneio IN ($placeholders)");

    $query->execute($ids_array);
    $produtos = $query->fetchAll(PDO::FETCH_ASSOC);

// Debug
error_log("IDs recebidos: " . print_r($ids_array, true));
error_log("Query SQL: " . $query->queryString);
error_log("Resultados: " . print_r($produtos, true));

// Formatar valores numéricos e ajustar campos para corresponder ao esperado
foreach($produtos as &$produto) {
    $produto['quant'] = $produto['quant'] ?? 0;
    $produto['variedade'] = $produto['variedade'];
    $produto['preco_kg'] = number_format((float)$produto['preco_kg'], 2, ',', '');
    $produto['preco_unit'] = number_format((float)$produto['preco_unit'], 2, ',', '');
    $produto['valor'] = number_format((float)$produto['valor'], 2, ',', '');
}

echo json_encode($produtos);