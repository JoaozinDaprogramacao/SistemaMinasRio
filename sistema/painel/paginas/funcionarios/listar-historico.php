<?php
require_once("../../../conexao.php");

header('Content-Type: application/json');

if (empty($_POST['id'])) {
    echo json_encode([]);
    exit();
}
$id = $_POST['id'];

// --- CONSTRUÇÃO DINÂMICA DA QUERY ---
$filterConditions = [];
$params = [':id_funcionario' => $id];

if (!empty($_POST['data_inicio'])) {
    $filterConditions[] = "data_filtragem >= :data_inicio";
    $params[':data_inicio'] = $_POST['data_inicio'];
}

if (!empty($_POST['data_fim'])) {
    $filterConditions[] = "data_filtragem <= :data_fim";
    $params[':data_fim'] = $_POST['data_fim'];
}

// O filtro de tipo agora pode incluir os novos eventos
if (!empty($_POST['tipo']) && $_POST['tipo'] != 'Todos') {
    $filterConditions[] = "tipo = :tipo";
    $params[':tipo'] = $_POST['tipo'];
}

if (!empty($_POST['valor_min']) && is_numeric($_POST['valor_min'])) {
    $filterConditions[] = "valor >= :valor_min";
    $params[':valor_min'] = $_POST['valor_min'];
}


// --- MONTAGEM DA QUERY FINAL COM TODOS OS EVENTOS ---
$baseQuery = "
    (SELECT 
        id, id_funcionario, 'Gratificação' AS tipo, valor, data, 
        CAST(data AS DATE) as data_filtragem, descricao, NULL AS forma_pgto
     FROM gratificacoes)
    
    UNION ALL
    
    (SELECT 
        id, id_funcionario, 'Adiantamento' AS tipo, valor, data, 
        CAST(data AS DATE) as data_filtragem, NULL AS descricao, forma_pgto
     FROM adiantamentos)

    UNION ALL 
    
    -- INÍCIO DO NOVO BLOCO PARA O HISTÓRICO DE EVENTOS --
    (SELECT
        id, id_funcionario, tipo_evento AS tipo, NULL AS valor, data_evento as data,
        CAST(data_evento AS DATE) as data_filtragem, descricao, NULL as forma_pgto
    FROM historico_funcionarios)
    -- FIM DO NOVO BLOCO --
";

$finalQuery = "SELECT * FROM ({$baseQuery}) AS historico WHERE id_funcionario = :id_funcionario";

if (count($filterConditions) > 0) {
    $finalQuery .= " AND " . implode(' AND ', $filterConditions);
}

$finalQuery .= " ORDER BY data DESC, id DESC";


// --- EXECUÇÃO ---
try {
    $query = $pdo->prepare($finalQuery);
    $query->execute($params);
    $resultado = $query->fetchAll(PDO::FETCH_ASSOC);
    echo json_encode($resultado);
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Erro na consulta ao banco de dados.', 'details' => $e->getMessage()]);
}
?>