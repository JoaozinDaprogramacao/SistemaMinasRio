<?php
$tabela = 'linha_bancos';
require_once("../../../conexao.php");

$filtros = [
    'p1' => $_POST['p1'] ?? '',
    'data_inicio' => $_POST['data_inicio'] ?? '',
    'data_fim' => $_POST['data_fim'] ?? '',
    'tipo_movimento' => $_POST['tipo_movimento'] ?? '',
    'valor_min' => $_POST['valor_min'] ?? '',
    'valor_max' => $_POST['valor_max'] ?? ''
];

$query = "SELECT * FROM $tabela";
$where = [];
$params = [];

// Filtro de Banco
if (!empty($filtros['p1'])) {
    $where[] = "id_banco = :banco_id";
    $params[':banco_id'] = $filtros['p1'];
}

// Filtros de Data
if (!empty($filtros['data_inicio']) && !empty($filtros['data_fim'])) {
    $where[] = "data BETWEEN :data_inicio AND :data_fim";
    $params[':data_inicio'] = $filtros['data_inicio'] . ' 00:00:00';
    $params[':data_fim'] = $filtros['data_fim'] . ' 23:59:59';
}

// Filtros de Valor e Tipo
if (!empty($filtros['tipo_movimento'])) {
    if ($filtros['tipo_movimento'] == 'credito') {
        $where[] = "credito > 0";
    } else if ($filtros['tipo_movimento'] == 'debito') {
        $where[] = "debito > 0";
    }
}

if (!empty($filtros['valor_min'])) {
    if ($filtros['tipo_movimento'] == 'credito') {
        $where[] = "credito >= :valor_min";
    } else if ($filtros['tipo_movimento'] == 'debito') {
        $where[] = "debito >= :valor_min";
    } else {
        $where[] = "(credito >= :valor_min OR debito >= :valor_min)";
    }
    $params[':valor_min'] = (float)$filtros['valor_min'];
}

if (!empty($filtros['valor_max'])) {
    if ($filtros['tipo_movimento'] == 'credito') {
        $where[] = "credito <= :valor_max";
    } else if ($filtros['tipo_movimento'] == 'debito') {
        $where[] = "debito <= :valor_max";
    } else {
        $where[] = "(credito <= :valor_max OR debito <= :valor_max)";
    }
    $params[':valor_max'] = (float)$filtros['valor_max'];
}

// Montar query final
if (!empty($where)) {
    $query .= " WHERE " . implode(' AND ', $where);
}

$query .= " ORDER BY data DESC";

// Executar consulta
$stmt = $pdo->prepare($query);
foreach ($params as $key => $value) {
    $stmt->bindValue($key, $value);
}
$stmt->execute();
$res = $stmt->fetchAll(PDO::FETCH_ASSOC);

$linhas = count($res);

if ($linhas > 0) {
    echo <<<HTML
    <div id="container-tabela">
        <table class="table table-bordered text-nowrap border-bottom dt-responsive" id="tabela">
        <thead> 
            <tr> 
                <th class="text-center">Selecionar</th>
                <th>Data</th>
                <th>Descrição</th>
                <th>Nº Fiscal</th>
                <th>Crédito R$</th>
                <th>Débito R$</th>
                <th>Saldo R$</th>
                <th>Status</th>
                <th>Ações</th>
            </tr> 
        </thead>
        <tbody>
HTML;

    foreach ($res as $item) {
        $data_formatada = date('d/m/Y \à\s H:i', strtotime($item['data']));
        $credito_formatado = number_format($item['credito'], 2, ',', '.');
        $debito_formatado = number_format($item['debito'], 2, ',', '.');
        $saldo_formatado = number_format($item['saldo'], 2, ',', '.');

        $descricao_crua = $item['descricao'];
        // Fazendo a consulta para obter o nome do plano de pagamento com base no ID
		$query_descricao = $pdo->query("SELECT descricao FROM descricao_banco WHERE id = '$descricao_crua'");

		// Recuperando o nome do plano de pagamento
		$descricao = $query_descricao->fetch(PDO::FETCH_ASSOC);

		// Verificando se o resultado foi encontrado e atribuindo o nome do plano
		if ($descricao) {
			$descricao = $descricao['descricao'];
		} else {
			$descricao = 'Descrição não encontrado';
		}

        echo <<<HTML
        <tr>
            <td class="text-center">
                <div class="custom-control custom-checkbox">
                    <input type="checkbox" class="custom-control-input" id="seletor-{$item['id']}">
                    <label class="custom-control-label" for="seletor-{$item['id']}"></label>
                </div>
            </td>
            <td>{$data_formatada}</td>
            <th>{$descricao}</td>
            <td>{$item['n_fiscal']}</td>
            <td class="text-success fw-bold">R$ {$credito_formatado}</td>
            <td class="text-danger fw-bold">R$ {$debito_formatado}</td>
            <td>R$ {$saldo_formatado}</td>
            <td>{$item['status']}</td>
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
        <p class="text-end mt-3">Total de Movimentações: {$linhas}</p>
    </div>
HTML;
} else {
    echo '<div class="alert alert-warning">Nenhum registro encontrado!</div>';
}
?> 