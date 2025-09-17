<?php
require_once("../../../conexao.php");

header('Content-Type: application/json');

// Pega o ID do funcionário de forma segura
if (empty($_POST['id'])) {
    echo json_encode([]); // Retorna um array vazio se o ID não for fornecido
    exit();
}
$id = $_POST['id'];

// --- CONSTRUÇÃO DINÂMICA DA QUERY ---
$filterConditions = [];
$params = [':id_funcionario' => $id];

// Adiciona filtro por Data de Início
if (!empty($_POST['data_inicio'])) {
    $filterConditions[] = "data_filtragem >= :data_inicio";
    $params[':data_inicio'] = $_POST['data_inicio'];
}

// Adiciona filtro por Data de Fim
if (!empty($_POST['data_fim'])) {
    $filterConditions[] = "data_filtragem <= :data_fim";
    $params[':data_fim'] = $_POST['data_fim'];
}

// Adiciona filtro por Tipo
if (!empty($_POST['tipo']) && $_POST['tipo'] != 'Todos') {
    $filterConditions[] = "tipo = :tipo";
    $params[':tipo'] = $_POST['tipo'];
}

// Adiciona filtro por Valor Mínimo
if (!empty($_POST['valor_min']) && is_numeric($_POST['valor_min'])) {
    $filterConditions[] = "valor >= :valor_min";
    $params[':valor_min'] = $_POST['valor_min'];
}


// --- MONTAGEM DA QUERY FINAL (ESTRUTURA CORRIGIDA E MAIS ROBUSTA) ---

// Base da query com a união das tabelas.
// A CORREÇÃO PRINCIPAL ESTÁ AQUI: CAST(data AS DATE)
// Isso força o SQL a tratar ambas as colunas como um tipo DATE puro para a filtragem.
$baseQuery = "
    (SELECT id, id_funcionario, 'Gratificação' AS tipo, valor, data, CAST(data AS DATE) as data_filtragem, descricao, NULL AS forma_pgto FROM gratificacoes)
    UNION ALL
    (SELECT id, id_funcionario, 'Adiantamento' AS tipo, valor, data, CAST(data AS DATE) as data_filtragem, NULL AS descricao, forma_pgto FROM adiantamentos)
";

// A primeira condição do WHERE é sempre o id_funcionario
$finalQuery = "SELECT * FROM ({$baseQuery}) AS historico WHERE id_funcionario = :id_funcionario";

// Se existirem outros filtros, anexa eles com AND
if (count($filterConditions) > 0) {
    $finalQuery .= " AND " . implode(' AND ', $filterConditions);
}

// Adiciona a ordenação
$finalQuery .= " ORDER BY data DESC, id DESC";


// --- EXECUÇÃO ---
try {
    $query = $pdo->prepare($finalQuery);
    $query->execute($params);
    $resultado = $query->fetchAll(PDO::FETCH_ASSOC);
    echo json_encode($resultado);
} catch (PDOException $e) {
    // Em caso de erro, retorna um JSON com a mensagem de erro para depuração
    http_response_code(500); // Internal Server Error
    echo json_encode(['error' => 'Erro na consulta ao banco de dados.', 'details' => $e->getMessage()]);
}
?>