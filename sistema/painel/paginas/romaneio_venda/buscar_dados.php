<?php
require_once("../../../conexao.php");

// 1. Validação da Entrada
if (empty($_POST['id'])) {
    http_response_code(400); // Bad Request
    echo json_encode(['error' => 'ID do romaneio não fornecido.']);
    exit();
}

$id = $_POST['id'];

try {
    // 2. Buscar o Romaneio Principal (Cabeçalho)
    // Ao fazer "rv.*", trazemos também a coluna 'id_romaneio_compra'
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

    // Se o romaneio não existir, encerra com erro 404.
    if (!$romaneio) {
        http_response_code(404);
        echo json_encode(['error' => 'Romaneio com ID ' . htmlspecialchars($id) . ' não encontrado.']);
        exit();
    }

    // 3. Buscar as Linhas de Produto
    $query_produtos = $pdo->prepare("
        SELECT 
            lp.*,
            p.nome AS nome_produto,
            CONCAT(tc.tipo, ' ', um.unidade) AS tipo_caixa_completo
        FROM linha_produto AS lp
        LEFT JOIN produtos AS p ON lp.variedade = p.id
        LEFT JOIN tipo_caixa AS tc ON lp.tipo_caixa = tc.id
        LEFT JOIN unidade_medida AS um ON tc.unidade_medida = um.id
        WHERE lp.id_romaneio = :id_romaneio
        ORDER BY lp.id ASC
    ");
    $query_produtos->bindValue(":id_romaneio", $id);
    $query_produtos->execute();
    $produtos = $query_produtos->fetchAll(PDO::FETCH_ASSOC);

    // 4. Buscar as Linhas de Comissão
    $query_comissoes = $pdo->prepare("
        SELECT 
            lc.*,
            p.nome AS nome_produto,
            CONCAT(tc.tipo, ' ', um.unidade) AS tipo_caixa_completo
        FROM linha_comissao AS lc
        LEFT JOIN produtos AS p ON lc.descricao = p.id 
        LEFT JOIN tipo_caixa AS tc ON lc.tipo_caixa = tc.id
        LEFT JOIN unidade_medida AS um ON tc.unidade_medida = um.id
        WHERE lc.id_romaneio = :id_romaneio
        ORDER BY lc.id ASC
    ");
    $query_comissoes->bindValue(":id_romaneio", $id);
    $query_comissoes->execute();
    $comissoes = $query_comissoes->fetchAll(PDO::FETCH_ASSOC);

    // 5. Buscar as Linhas de Materiais/Observações
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

    // ==================================================================================
    // 6. [AJUSTADO] Obter IDs dos Romaneios de Compra
    // ==================================================================================
    // Não precisamos fazer outra consulta SQL. O dado já veio no passo 2 
    // dentro da variável $romaneio['id_romaneio_compra'].

    $ids_compras = [];

    // Verificamos se existe valor na coluna e adicionamos ao array
    if (!empty($romaneio['id_romaneio_compra'])) {
        $ids_compras[] = $romaneio['id_romaneio_compra'];
    }

    // 7. Montar a Resposta Final
    $dados_finais = [
        'romaneio'    => $romaneio,
        'produtos'    => $produtos,
        'comissoes'   => $comissoes,
        'materiais'   => $materiais,
        'ids_compras' => $ids_compras // Array contendo o ID (ex: [10]) ou vazio []
    ];

    // 8. Enviar a resposta como JSON
    header('Content-Type: application/json');
    echo json_encode($dados_finais, JSON_PRETTY_PRINT | JSON_NUMERIC_CHECK);
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Erro interno ao buscar dados: ' . $e->getMessage()]);
}
