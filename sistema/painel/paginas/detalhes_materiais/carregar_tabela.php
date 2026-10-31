<?php
$tabela = 'detalhes_materiais';
require_once("../../../conexao.php");
require_once("../../verificar.php");

// Receber todos os filtros via POST
$filtros = [
    'data_inicio' => $_POST['data_inicio'] ?? '',
    'data_fim' => $_POST['data_fim'] ?? '',
    'tipo_movimento' => $_POST['tipo_movimento'] ?? '',
    'valor_min' => $_POST['valor_min'] ?? '',
    'valor_max' => $_POST['valor_max'] ?? '',
    'quantidade_min' => $_POST['quantidade_min'] ?? '',
    'quantidade_max' => $_POST['quantidade_max'] ?? '',
    'material' => $_POST['material'] ?? '',
    'ordenacao' => $_POST['ordenacao'] ?? ''
];

// Construção segura da query com prepared statements
$query = "SELECT d.*, m.nome AS nome_material, c.nome AS nome_cliente
          FROM $tabela d
          LEFT JOIN materiais m ON m.id = d.material_id
          LEFT JOIN clientes c ON c.id = d.descricao";
$where = [];
$params = [];

// Filtros de Data
if (!empty($filtros['data_inicio'])) {
    $where[] = "d.data >= :data_inicio";
    $params[':data_inicio'] = $filtros['data_inicio'] . ' 00:00:00';
}
if (!empty($filtros['data_fim'])) {
    $where[] = "d.data <= :data_fim";
    $params[':data_fim'] = $filtros['data_fim'] . ' 23:59:59';
}

// Tipo de Movimento
if ($filtros['tipo_movimento'] == 'compra') {
    $where[] = "d.compra > 0";
} elseif ($filtros['tipo_movimento'] == 'venda') {
    $where[] = "d.venda > 0";
}

// Valor e quantidade movimentados (compra ou venda)
if ($filtros['valor_min'] !== '') {
    $where[] = "(d.valor_compra >= :valor_min OR d.valor_venda >= :valor_min2)";
    $params[':valor_min'] = $params[':valor_min2'] = (float) $filtros['valor_min'];
}
if ($filtros['valor_max'] !== '') {
    $where[] = "GREATEST(d.valor_compra, d.valor_venda) <= :valor_max";
    $params[':valor_max'] = (float) $filtros['valor_max'];
}
if ($filtros['quantidade_min'] !== '') {
    $where[] = "(d.compra + d.venda) >= :quantidade_min";
    $params[':quantidade_min'] = (int) $filtros['quantidade_min'];
}
if ($filtros['quantidade_max'] !== '') {
    $where[] = "(d.compra + d.venda) <= :quantidade_max";
    $params[':quantidade_max'] = (int) $filtros['quantidade_max'];
}

// Material (filtro e abas da tela)
if (!empty($filtros['material'])) {
    $where[] = "d.material_id = :material";
    $params[':material'] = (int) $filtros['material'];
}

if (!empty($where)) {
    $query .= " WHERE " . implode(' AND ', $where);
}

// Ordenação
switch ($filtros['ordenacao']) {
    case 'data_asc':
        $query .= " ORDER BY d.data ASC, d.id ASC";
        break;
    case 'valor_asc':
        $query .= " ORDER BY GREATEST(d.valor_compra, d.valor_venda) ASC";
        break;
    case 'valor_desc':
        $query .= " ORDER BY GREATEST(d.valor_compra, d.valor_venda) DESC";
        break;
    default:
        $query .= " ORDER BY d.data DESC, d.id DESC";
}

$stmt = $pdo->prepare($query);
$stmt->execute($params);
$res = $stmt->fetchAll(PDO::FETCH_ASSOC);
$linhas = count($res);

$moeda = fn($v) => 'R$ ' . number_format((float) $v, 2, ',', '.');

if ($linhas > 0) {
    echo <<<HTML
    <table class="table table-bordered text-nowrap border-bottom dt-responsive" id="tabela">
    <thead>
        <tr>
            <th>Data</th>
            <th>Material</th>
            <th>Movimento</th>
            <th>Cliente</th>
            <th>Venda</th>
            <th class="text-end">Quantidade</th>
            <th class="text-end">Preço Und.</th>
            <th class="text-end">Valor</th>
            <th class="text-end">Saída Estq.</th>
            <th class="text-end">Entrada Estq.</th>
        </tr>
    </thead>
    <tbody>
HTML;

    foreach ($res as $item) {
        $data_formatada = $item['data'] ? date('d/m/Y \à\s H:i', strtotime($item['data'])) : '-';
        $material = htmlspecialchars($item['nome_material'] ?? 'Material #' . $item['material_id']);
        $cliente = htmlspecialchars($item['nome_cliente'] ?? '-');
        $eh_compra = $item['compra'] > 0;
        $movimento = $eh_compra ? 'Compra' : 'Venda';
        $quantidade = $eh_compra ? $item['compra'] : $item['venda'];
        $valor = $moeda($eh_compra ? $item['valor_compra'] : $item['valor_venda']);
        $preco = $moeda($item['preco_unidade']);
        $venda = $item['id_venda_real'] ? '#' . (int) $item['id_venda_real'] : '-';

        echo <<<HTML
        <tr>
            <td>{$data_formatada}</td>
            <td>{$material}</td>
            <td>{$movimento}</td>
            <td>{$cliente}</td>
            <td>{$venda}</td>
            <td class="text-end">{$quantidade}</td>
            <td class="text-end">{$preco}</td>
            <td class="text-end">{$valor}</td>
            <td class="text-end">{$item['saida_estoque']}</td>
            <td class="text-end">{$item['entrada_estoque']}</td>
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
