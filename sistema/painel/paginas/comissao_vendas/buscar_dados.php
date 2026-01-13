<?php
require_once("../../../conexao.php");

if (empty($_POST['id'])) {
    http_response_code(400);
    echo json_encode(['error' => 'ID do romaneio não fornecido.']);
    exit();
}

$id = $_POST['id'];

try {
    // 1. Cabeçalho do Romaneio
    $query_romaneio = $pdo->prepare("
        SELECT 
            rv.*, 
            c.nome AS nome_cliente, 
            pp.nome AS nome_plano
        FROM romaneio_venda AS rv
        LEFT JOIN clientes AS c ON rv.atacadista = c.id
        LEFT JOIN planos_pgto AS pp ON rv.plano_pgto = pp.id
        WHERE rv.id = :id
    ");
    $query_romaneio->bindValue(":id", $id);
    $query_romaneio->execute();
    $romaneio = $query_romaneio->fetch(PDO::FETCH_ASSOC);

    if (!$romaneio) {
        http_response_code(404);
        echo json_encode(['error' => 'Romaneio não encontrado.']);
        exit();
    }

    // 2. Linhas de Produto (Double JOIN: linha -> produtos -> categorias)
    $query_produtos = $pdo->prepare("
        SELECT 
            lp.*,
            p.nome AS nome_produto,
            cat.nome AS nome_variedade,
            CONCAT(tc.tipo, ' ', um.unidade) AS tipo_caixa_completo
        FROM linha_produto AS lp
        LEFT JOIN produtos AS p ON lp.variedade = p.id
        LEFT JOIN categorias AS cat ON p.categoria = cat.id
        LEFT JOIN tipo_caixa AS tc ON lp.tipo_caixa = tc.id
        LEFT JOIN unidade_medida AS um ON tc.unidade_medida = um.id
        WHERE lp.id_romaneio = :id_romaneio
        ORDER BY lp.id ASC
    ");
    $query_produtos->bindValue(":id_romaneio", $id);
    $query_produtos->execute();
    $produtos = $query_produtos->fetchAll(PDO::FETCH_ASSOC);

    // 3. Linhas de Comissão (Double JOIN: linha -> produtos -> categorias)
    $query_comissoes = $pdo->prepare("
        SELECT 
            lc.*,
            p.nome AS nome_produto,
            cat.nome AS nome_variedade,
            CONCAT(tc.tipo, ' ', um.unidade) AS tipo_caixa_completo
        FROM linha_comissao AS lc
        LEFT JOIN produtos AS p ON lc.descricao = p.id 
        LEFT JOIN categorias AS cat ON p.categoria = cat.id
        LEFT JOIN tipo_caixa AS tc ON lc.tipo_caixa = tc.id
        LEFT JOIN unidade_medida AS um ON tc.unidade_medida = um.id
        WHERE lc.id_romaneio = :id_romaneio
        ORDER BY lc.id ASC
    ");
    $query_comissoes->bindValue(":id_romaneio", $id);
    $query_comissoes->execute();
    $comissoes = $query_comissoes->fetchAll(PDO::FETCH_ASSOC);

    // 4. Linhas de Materiais
    $query_materiais = $pdo->prepare("
        SELECT 
            lo.*,
            m.nome AS nome_material
        FROM linha_observacao AS lo
        LEFT JOIN materiais AS m ON lo.descricao = m.id
        WHERE lo.id_romaneio = :id_romaneio
        ORDER BY lo.id ASC
    ");
    $query_materiais->bindValue(":id_romaneio", $id);
    $query_materiais->execute();
    $materiais = $query_materiais->fetchAll(PDO::FETCH_ASSOC);

    $ids_compras = [];
    if (!empty($romaneio['id_romaneio_compra'])) {
        $ids_compras[] = $romaneio['id_romaneio_compra'];
    }

    $dados_finais = [
        'romaneio'    => $romaneio,
        'produtos'    => $produtos,
        'comissoes'   => $comissoes,
        'materiais'   => $materiais,
        'ids_compras' => $ids_compras
    ];

    header('Content-Type: application/json');
    echo json_encode($dados_finais, JSON_PRETTY_PRINT | JSON_NUMERIC_CHECK);

} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Erro interno: ' . $e->getMessage()]);
}