<?php
// sistema/painel/paginas/romaneio_venda/buscar_produtos_romaneio.php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require_once("../../../conexao.php");
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

try {
    // 1) Recepção dos IDs
    $ids = $_POST['ids'] ?? null;
    if (!$ids) {
        throw new Exception("Nenhum ID recebido em \$_POST['ids']");
    }
    $ids_array = is_array($ids) ? $ids : explode(',', $ids);

    // 2) Placeholders
    $placeholders = implode(',', array_fill(0, count($ids_array), '?'));

    // 3) SQL
    $sql = "
        SELECT 
            lpc.*,
            p.nome         AS nome_produto,
            tc.tipo        AS tipo_caixa
        FROM linha_produto_compra lpc
        LEFT JOIN produtos p ON lpc.variedade   = p.id 
        LEFT JOIN tipo_caixa    tc ON lpc.tipo_caixa = tc.id
        WHERE lpc.id_romaneio IN ($placeholders)
    ";
    $stmt = $pdo->prepare($sql);
    foreach ($ids_array as $i => $val) {
        $stmt->bindValue($i + 1, $val, PDO::PARAM_INT);
    }

    // 4) Execução e tempo
    $t0 = microtime(true);
    $stmt->execute();
    $duration = microtime(true) - $t0;

    // 5) Fetch
    $produtos = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // 6) Formatação
    foreach ($produtos as &$produto) {
        $produto['quant']      = $produto['quant']  ?? 0;
        $produto['preco_kg']   = number_format((float)$produto['preco_kg'],   2, ',', '');
        $produto['preco_unit'] = number_format((float)$produto['preco_unit'], 2, ',', '');
        $produto['valor']      = number_format((float)$produto['valor'],      2, ',', '');
    }

    // 7) Monta resposta COM debug
    $response = [
        'debug' => [
            'ids_recebidos'   => $ids_array,
            'placeholders'    => $placeholders,
            'sql'             => trim($sql),
            'bind_values'     => $ids_array,
            'duration_sec'    => round($duration, 4),
            'row_count'       => count($produtos),
        ],
        'data' => $produtos,
    ];

    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($response, JSON_UNESCAPED_UNICODE);

} catch (Exception $e) {
    http_response_code(400);
    echo json_encode([
        'error'   => 'Falha na requisição',
        'message' => $e->getMessage()
    ], JSON_UNESCAPED_UNICODE);
}
