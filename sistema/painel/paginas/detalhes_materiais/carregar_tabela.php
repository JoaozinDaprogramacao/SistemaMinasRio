<?php
$tabela = 'detalhes_materiais';
require_once("../../../conexao.php");

// Receber todos os filtros via POST
$filtros = [
    'p1' => $_POST['p1'] ?? '',
    'data_inicio' => $_POST['data_inicio'] ?? '',
    'data_fim' => $_POST['data_fim'] ?? '',
    'tipo_movimento' => $_POST['tipo_movimento'] ?? '',
    'descricao_filtro' => $_POST['descricao_filtro'] ?? '',
    'valor_min' => $_POST['valor_min'] ?? '',
    'valor_max' => $_POST['valor_max'] ?? '',
    'quantidade_min' => $_POST['quantidade_min'] ?? '',
    'quantidade_max' => $_POST['quantidade_max'] ?? '',
    'material' => $_POST['material'] ?? '',
    'fornecedor' => $_POST['fornecedor'] ?? '',
    'responsavel' => $_POST['responsavel'] ?? '',
    'notas_filtro' => $_POST['notas_filtro'] ?? '',
    'ordenacao' => $_POST['ordenacao'] ?? ''
];

// Construção segura da query com prepared statements
$query = "SELECT * FROM $tabela";
$where = [];
$params = [];

// Filtro de Categoria (Material)
if (!empty($filtros['p1'])) {
    $where[] = "material_id = :material_id";
    $params[':material_id'] = $filtros['p1'];
}

// Filtros de Data
if (!empty($filtros['data_inicio']) && !empty($filtros['data_fim'])) {
    $where[] = "data BETWEEN :data_inicio AND :data_fim";
    $params[':data_inicio'] = $filtros['data_inicio'] . ' 00:00:00';
    $params[':data_fim'] = $filtros['data_fim'] . ' 23:59:59';
}

// Tipo de Movimento
if (!empty($filtros['tipo_movimento'])) {
    if ($filtros['tipo_movimento'] == 'compra') {
        $where[] = "compra > 0";
    } else {
        $where[] = "venda > 0";
    }
}

// Filtros de Texto
if (!empty($filtros['descricao_filtro'])) {
    $where[] = "descricao LIKE :descricao";
    $params[':descricao'] = '%' . $filtros['descricao_filtro'] . '%';
}

if (!empty($filtros['notas_filtro'])) {
    $where[] = "notas LIKE :notas";
    $params[':notas'] = '%' . $filtros['notas_filtro'] . '%';
}

// Filtros Numéricos para Compra e Venda
if (!empty($filtros['valor_min'])) {
    $where[] = "(valor_compra >= :valor_min OR valor_venda >= :valor_min)";
    $params[':valor_min'] = (float)$filtros['valor_min'];
}

if (!empty($filtros['valor_max'])) {
    $where[] = "(valor_compra <= :valor_max OR valor_venda <= :valor_max)";
    $params[':valor_max'] = (float)$filtros['valor_max'];
}
if (!empty($filtros['quantidade_min'])) {
    $where[] = "entrada_estoque >= :quantidade_min";
    $params[':quantidade_min'] = (int)$filtros['quantidade_min'];
}

if (!empty($filtros['quantidade_max'])) {
    $where[] = "entrada_estoque <= :quantidade_max";
    $params[':quantidade_max'] = (int)$filtros['quantidade_max'];
}

// Filtros Relacionais
if (!empty($filtros['material'])) {
    $where[] = "material_id = :material";
    $params[':material'] = $filtros['material'];
}

if (!empty($filtros['fornecedor'])) {
    $where[] = "fornecedor_id = :fornecedor";
    $params[':fornecedor'] = $filtros['fornecedor'];
}

if (!empty($filtros['responsavel'])) {
    $where[] = "usuario_id = :responsavel";
    $params[':responsavel'] = $filtros['responsavel'];
}

// Montar a query final
if (!empty($where)) {
    $query .= " WHERE " . implode(' AND ', $where);
}

// Ordenação
switch ($filtros['ordenacao']) {
    case 'data_asc':
        $query .= " ORDER BY data ASC";
        break;
    case 'data_desc':
        $query .= " ORDER BY data DESC";
        break;
    case 'valor_asc':
        $query .= " ORDER BY valor_compra ASC";
        break;
    case 'valor_desc':
        $query .= " ORDER BY valor_compra DESC";
        break;
    default:
        $query .= " ORDER BY id DESC";
}

// Executar a consulta
$stmt = $pdo->prepare($query);
foreach ($params as $key => $value) {
    $stmt->bindValue($key, $value);
}

// Executar a consulta
$stmt = $pdo->prepare($query);
foreach ($params as $key => $value) {
    $stmt->bindValue($key, $value);
}
$stmt->execute();

// ================================================
// FUNÇÃO PARA VISUALIZAR A QUERY COMPLETA (DEBUG)
// ================================================
function debugQuery($query, $params) {
    $keys = array();
    $values = $params;
    
    // Ordena os parâmetros pelo tamanho do nome (para evitar substituições parciais)
    krsort($params);
    
    foreach ($params as $key => $value) {
        // Remove os dois pontos dos placeholders
        $clean_key = str_replace(':', '', $key);
        
        // Trata diferentes tipos de dados
        if (is_string($value)) {
            $value = "'" . addslashes($value) . "'";
        } elseif (is_null($value)) {
            $value = 'NULL';
        } elseif (is_bool($value)) {
            $value = $value ? 'TRUE' : 'FALSE';
        }
        
        // Substitui os placeholders na query
        $query = str_replace(':' . $clean_key, $value, $query);
    }
    
    return $query;
}

// Exibe a query final formatada
echo "<script>console.log('Query Debug:', " . json_encode(debugQuery($query, $params)) . ")</script>";
// ================================================

$res = $stmt->fetchAll(PDO::FETCH_ASSOC);
$linhas = count($res);

if ($linhas > 0) {
    echo <<<HTML
    <table class="table table-bordered text-nowrap border-bottom dt-responsive" id="tabela">
    <thead> 
        <tr> 
            <th class="text-center">Selecionar</th>
            <th>Data</th>
            <th>Descrição</th>
            <th>Compra</th>
            <th>Venda</th>
            <th>Preço Und.</th>
            <th>Valor Compra</th>
            <th>Valor Venda</th>
            <th>Saída Estq.</th>
            <th>Entrada Estq.</th>
            <th>Ações</th>
        </tr> 
    </thead>
    <tbody>
HTML;

    foreach ($res as $item) {
        $data_formatada = date('d/m/Y \à\s H:i', strtotime($item['data']));

        echo <<<HTML
        <tr>
            <td class="text-center">
                <div class="custom-control custom-checkbox">
                    <input type="checkbox" class="custom-control-input" id="seletor-{$item['id']}">
                    <label class="custom-control-label" for="seletor-{$item['id']}"></label>
                </div>
            </td>
            <td>{$data_formatada}</td>
            <td>{$item['descricao']}</td>
            <td>{$item['compra']}</td>
            <td>{$item['venda']}</td>
            <td>R$ {$item['preco_unidade']}</td>
            <td>R$ {$item['valor_compra']}</td>
            <td>R$ {$item['valor_venda']}</td>
            <td>{$item['saida_estoque']}</td>
            <td>{$item['entrada_estoque']}</td>
            <td>
                <button class="btn btn-info btn-sm" onclick="editar({$item['id']})">
                    <i class="fa fa-edit"></i>
                </button>
                <button class="btn btn-danger btn-sm" onclick="excluir({$item['id']})">
                    <i class="fa fa-trash"></i>
                </button>
            </td>
        </tr>
HTML;
    }

    echo <<<HTML
    </tbody>
    </table>
    <p class="text-end mt-3">Total de Itens: {$linhas}</p>
HTML;
} else {
    echo '<div class="alert alert-warning">Nenhum registro encontrado!</div>';
}
?>