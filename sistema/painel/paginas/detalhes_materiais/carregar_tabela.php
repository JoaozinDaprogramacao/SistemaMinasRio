<?php
require_once("../../../conexao.php");
require_once(__DIR__ . "/../../seguranca.php");
require_once(__DIR__ . "/../../funcoes/financeiro.php"); // garante as colunas id_romaneio e id_compra
seguranca_exigir_acesso(['produtos']);
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

// Todas as movimentações de material numa lista só:
// compras, vendas e romaneios (detalhes_materiais) + entradas e saídas manuais do estoque.
$movimentos = "
    SELECT dm.id, dm.data, dm.material_id,
        CASE WHEN COALESCE(dm.compra, 0) > 0 THEN 'compra'
             WHEN dm.id_romaneio IS NOT NULL THEN 'romaneio'
             ELSE 'venda' END AS tipo,
        IF(COALESCE(dm.compra, 0) > 0, dm.compra, COALESCE(dm.venda, 0)) AS quantidade,
        IF(COALESCE(dm.compra, 0) > 0, COALESCE(dm.valor_compra, 0), COALESCE(dm.valor_venda, 0)) AS valor,
        dm.preco_unidade, COALESCE(dm.entrada_estoque, 0) AS entrada_estoque, COALESCE(dm.saida_estoque, 0) AS saida_estoque,
        dm.descricao AS cliente_id, dm.id_venda_real, dm.id_romaneio, dm.id_compra, NULL AS motivo, NULL AS usuario_id
    FROM detalhes_materiais dm
    UNION ALL
    SELECT e.id, e.data, e.material, 'entrada_manual', e.quantidade, 0, NULL, e.quantidade, 0,
        NULL, NULL, NULL, NULL, e.motivo, e.usuario
    FROM entradas e
    UNION ALL
    SELECT s.id, s.data, s.material, 'saida_manual', s.quantidade, 0, NULL, 0, s.quantidade,
        NULL, NULL, NULL, NULL, s.motivo, s.usuario
    FROM saidas s";

$query = "SELECT d.*, m.nome AS nome_material, c.nome AS nome_cliente, u.nome AS nome_usuario,
                 COALESCE(NULLIF(f.nome_atacadista, ''), f.razao_social) AS nome_fornecedor
          FROM ($movimentos) d
          LEFT JOIN materiais m ON m.id = d.material_id
          LEFT JOIN clientes c ON c.id = d.cliente_id AND d.tipo <> 'compra'
          LEFT JOIN usuarios u ON u.id = d.usuario_id
          LEFT JOIN pagar p ON p.id = d.id_compra
          LEFT JOIN fornecedores f ON f.id = p.fornecedor";
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
$tipos = [
    'compra' => ['compra'],
    'venda' => ['venda'],
    'romaneio' => ['romaneio'],
    'entrada_manual' => ['entrada_manual'],
    'saida_manual' => ['saida_manual'],
    'entradas' => ['compra', 'entrada_manual'],
    'saidas' => ['venda', 'romaneio', 'saida_manual'],
];
if (isset($tipos[$filtros['tipo_movimento']])) {
    $where[] = "d.tipo IN ('" . implode("','", $tipos[$filtros['tipo_movimento']]) . "')";
}

// Valor e quantidade movimentados
if ($filtros['valor_min'] !== '') {
    $where[] = "d.valor >= :valor_min";
    $params[':valor_min'] = (float) $filtros['valor_min'];
}
if ($filtros['valor_max'] !== '') {
    $where[] = "d.valor <= :valor_max";
    $params[':valor_max'] = (float) $filtros['valor_max'];
}
if ($filtros['quantidade_min'] !== '') {
    $where[] = "d.quantidade >= :quantidade_min";
    $params[':quantidade_min'] = (float) $filtros['quantidade_min'];
}
if ($filtros['quantidade_max'] !== '') {
    $where[] = "d.quantidade <= :quantidade_max";
    $params[':quantidade_max'] = (float) $filtros['quantidade_max'];
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
        $query .= " ORDER BY d.valor ASC";
        break;
    case 'valor_desc':
        $query .= " ORDER BY d.valor DESC";
        break;
    default:
        $query .= " ORDER BY d.data DESC, d.id DESC";
}

$stmt = $pdo->prepare($query);
$stmt->execute($params);
$res = $stmt->fetchAll(PDO::FETCH_ASSOC);
$linhas = count($res);

$moeda = fn($v) => 'R$ ' . number_format((float) $v, 2, ',', '.');
$num = fn($v) => rtrim(rtrim(number_format((float) $v, 2, ',', '.'), '0'), ',');
$e = fn($v) => htmlspecialchars((string) $v, ENT_QUOTES, 'UTF-8');

$rotulos = [
    'compra' => ['Compra', 'success'],
    'venda' => ['Venda', 'primary'],
    'romaneio' => ['Romaneio', 'info'],
    'entrada_manual' => ['Entrada manual', 'secondary'],
    'saida_manual' => ['Saída manual', 'warning'],
];

if ($linhas > 0) {
    $total_entrada = 0;
    $total_saida = 0;

    echo <<<HTML
    <table class="table table-bordered text-nowrap border-bottom dt-responsive" id="tabela">
    <thead>
        <tr>
            <th>Data</th>
            <th>Material</th>
            <th>Movimento</th>
            <th>Cliente / Fornecedor / Responsável</th>
            <th>Origem / Motivo</th>
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
        $ts = $item['data'] ? strtotime($item['data']) : false;
        $data_formatada = $ts ? (date('H:i:s', $ts) === '00:00:00' ? date('d/m/Y', $ts) : date('d/m/Y \à\s H:i', $ts)) : '-';
        $material = $e($item['nome_material'] ?? 'Material #' . $item['material_id']);
        [$rotulo, $cor] = $rotulos[$item['tipo']] ?? [$item['tipo'], 'secondary'];
        $movimento = "<span class=\"badge bg-{$cor}\">{$rotulo}</span>";
        $manual = in_array($item['tipo'], ['entrada_manual', 'saida_manual'], true);

        if ($manual) {
            $pessoa = $e($item['nome_usuario'] ?? '-');
            $origem = $item['motivo'] !== null && $item['motivo'] !== '' ? $e($item['motivo']) : '<span class="text-muted">sem motivo</span>';
            $preco = '-';
            $valor = '-';
        } else {
            $pessoa = $e(($item['tipo'] === 'compra' ? $item['nome_fornecedor'] : $item['nome_cliente']) ?? '-');
            if ($item['tipo'] === 'compra') {
                $origem = !empty($item['id_compra']) ? 'Compra #' . (int) $item['id_compra'] : '-';
            } elseif (!empty($item['id_romaneio'])) {
                $origem = 'Romaneio #' . (int) $item['id_romaneio'];
            } elseif (!empty($item['id_venda_real'])) {
                $origem = 'Venda #' . (int) $item['id_venda_real'];
            } else {
                $origem = '-';
            }
            $preco = $moeda($item['preco_unidade']);
            $valor = $moeda($item['valor']);
        }

        $total_entrada += (float) $item['entrada_estoque'];
        $total_saida += (float) $item['saida_estoque'];
        $quantidade = $num($item['quantidade']);
        $saida = $num($item['saida_estoque']);
        $entrada = $num($item['entrada_estoque']);

        echo <<<HTML
        <tr>
            <td>{$data_formatada}</td>
            <td>{$material}</td>
            <td>{$movimento}</td>
            <td>{$pessoa}</td>
            <td>{$origem}</td>
            <td class="text-end">{$quantidade}</td>
            <td class="text-end">{$preco}</td>
            <td class="text-end">{$valor}</td>
            <td class="text-end">{$saida}</td>
            <td class="text-end">{$entrada}</td>
        </tr>
HTML;
    }

    $total_entrada = $num($total_entrada);
    $total_saida = $num($total_saida);
    echo <<<HTML
    </tbody>
    </table>
    <p class="text-end mt-3">
        Total de Itens: {$linhas}
        &nbsp;|&nbsp; Saída de estoque: <b>{$total_saida}</b>
        &nbsp;|&nbsp; Entrada de estoque: <b>{$total_entrada}</b>
    </p>
HTML;
} else {
    echo '<div class="alert alert-warning">Nenhum registro encontrado!</div>';
}
