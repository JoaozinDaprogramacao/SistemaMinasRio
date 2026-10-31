<?php
$tabela = 'vendas';
require_once("../../../conexao.php");

// Receber todos os filtros via POST, adaptados para o contexto de vendas
$filtros = [
    'p1' => $_POST['p1'] ?? '', // Abas de cliente: listar() do ajax.js envia p1
    'data_inicio' => $_POST['data_inicio'] ?? '',
    'data_fim' => $_POST['data_fim'] ?? '',
    'status_pagamento' => $_POST['status_pagamento'] ?? '',
    'valor_min' => $_POST['valor_min'] ?? '',
    'valor_max' => $_POST['valor_max'] ?? '',
    'cliente' => $_POST['cliente'] ?? '',
    'vendedor' => $_POST['vendedor'] ?? '',
    'ordenacao' => $_POST['ordenacao'] ?? 'data_desc' // Padrão para mais recentes primeiro
];

// Construção segura da query base com JOINS para buscar nomes
$query = "SELECT v.*, c.nome as nome_cliente, u.nome as nome_vendedor 
          FROM vendas v
          LEFT JOIN clientes c ON v.cliente_id = c.id
          LEFT JOIN usuarios u ON v.vendedor_id = u.id";

$where = [];
$params = [];

// Filtro de Cliente (pode vir das abas ou do painel de filtros)
$cliente_filtro = !empty($filtros['p1']) ? $filtros['p1'] : $filtros['cliente'];
if (!empty($cliente_filtro)) {
    $where[] = "v.cliente_id = :cliente_id";
    $params[':cliente_id'] = $cliente_filtro;
}

// Filtros de Data
if (!empty($filtros['data_inicio']) && !empty($filtros['data_fim'])) {
    $where[] = "v.data_venda BETWEEN :data_inicio AND :data_fim";
    $params[':data_inicio'] = $filtros['data_inicio'] . ' 00:00:00';
    $params[':data_fim'] = $filtros['data_fim'] . ' 23:59:59';
}

// Filtro de Status de Pagamento
if (!empty($filtros['status_pagamento'])) {
    $where[] = "v.status_pagamento = :status_pagamento";
    $params[':status_pagamento'] = $filtros['status_pagamento'];
}

// Filtros de Valor Total
if (!empty($filtros['valor_min'])) {
    $where[] = "v.valor_total >= :valor_min";
    $params[':valor_min'] = (float)$filtros['valor_min'];
}
if (!empty($filtros['valor_max'])) {
    $where[] = "v.valor_total <= :valor_max";
    $params[':valor_max'] = (float)$filtros['valor_max'];
}

// Filtro de Vendedor (Responsável)
if (!empty($filtros['vendedor'])) {
    $where[] = "v.vendedor_id = :vendedor_id";
    $params[':vendedor_id'] = $filtros['vendedor'];
}

// Montar a query final com as cláusulas WHERE
if (!empty($where)) {
    $query .= " WHERE " . implode(' AND ', $where);
}

// Ordenação
switch ($filtros['ordenacao']) {
    case 'data_asc':
        $query .= " ORDER BY v.data_venda ASC";
        break;
    case 'data_desc':
        $query .= " ORDER BY v.data_venda DESC";
        break;
    case 'valor_asc':
        $query .= " ORDER BY v.valor_total ASC";
        break;
    case 'valor_desc':
        $query .= " ORDER BY v.valor_total DESC";
        break;
    default:
        $query .= " ORDER BY v.id DESC";
}

// Executar a consulta preparada
$stmt = $pdo->prepare($query);
$stmt->execute($params);
$res = $stmt->fetchAll(PDO::FETCH_ASSOC);
$linhas = count($res);

if ($linhas > 0) {
    echo <<<HTML
    <table class="table table-bordered text-nowrap border-bottom dt-responsive" id="tabela">
    <thead> 
        <tr> 
            <th class="text-center"><input type="checkbox" id="selec-todos" onchange="marcarTodos()"></th>
            <th>ID</th>
            <th>Cliente</th>
            <th>Vendedor</th>
            <th>Data</th>
            <th>Valor Total</th>
            <th>Status</th>
            <th>Restante</th>
            <th>Ações</th>
        </tr> 
    </thead>
    <tbody>
HTML;

    foreach ($res as $item) {
        $id = $item['id'];
        $data_formatada = date('d/m/Y H:i', strtotime($item['data_venda']));
        $valor_total = number_format($item['valor_total'], 2, ',', '.');
        $valor_restante = number_format($item['valor_restante'], 2, ',', '.');
        $status = $item['status_pagamento'];

        // Define a cor do status
        $classe_status = 'text-warning'; // Amarelo para "Aguardando"
        if ($status == 'Pago') {
            $classe_status = 'text-success'; // Verde para "Pago"
        } elseif ($status == 'Parcialmente Pago') {
            $classe_status = 'text-primary'; // Azul para "Parcialmente"
        } elseif ($status == 'Cancelada') {
            $classe_status = 'text-muted text-decoration-line-through';
        }

        // Define a cor do valor restante
        $classe_restante = $item['valor_restante'] > 0 ? 'text-danger' : 'text-muted';

        $nome_cliente = htmlspecialchars($item['nome_cliente'] ?? 'Cliente excluído');
        $nome_vendedor = htmlspecialchars($item['nome_vendedor'] ?? 'Usuário excluído');
        $ocultar_acoes = ($status == 'Cancelada') ? 'd-none' : '';

        echo <<<HTML
        <tr>
            <td class="text-center">
                <input type="checkbox" id="seletor-{$id}" class="form-check-input chk {$ocultar_acoes}" onchange="selecionar('{$id}')">
            </td>
            <td>{$id}</td>
            <td>{$nome_cliente}</td>
            <td>{$nome_vendedor}</td>
            <td>{$data_formatada}</td>
            <td>R$ {$valor_total}</td>
            <td><span class="fw-bold {$classe_status}">{$status}</span></td>
            <td><span class="fw-bold {$classe_restante}">R$ {$valor_restante}</span></td>
            <td>
                <button class="btn btn-primary btn-sm" onclick="mostrarDetalhes({$id})" title="Ver Detalhes">
                    <i class="fa fa-eye"></i>
                </button>

                <button class="btn btn-info btn-sm {$ocultar_acoes}" onclick="editarVenda({$id})" title="Editar Venda">
                    <i class="fa fa-edit"></i>
                </button>

                <a class="btn btn-dark btn-sm" href="rel/comprovante_pdf.php?venda={$id}" target="_blank" title="Comprovante">
                    <i class="fa fa-file-pdf-o"></i>
                </a>

                <button class="btn btn-danger btn-sm {$ocultar_acoes}" onclick="cancelarVenda({$id})" title="Cancelar Venda">
                    <i class="fa fa-ban"></i>
                </button>
            </td>
        </tr>
HTML;
    }
    
    echo <<<HTML
    </tbody>
    </table>
HTML;
} else {
    echo '<div class="alert alert-warning text-center">Nenhum registro encontrado com os filtros aplicados!</div>';
}
?>


<script type="text/javascript">
function editarVenda(id_venda) {
    Swal.fire({
        title: "Aguarde!",
        text: "Preparando venda para edição...",
        icon: "info",
        showConfirmButton: false,
        allowOutsideClick: false,
        didOpen: () => {
            Swal.showLoading();
        }
    });

    // Marca a venda na sessão; a aba Vendas copia os itens para o carrinho ao abrir
    $.ajax({
        url: "paginas/vendas_detalhes/editar.php",
        method: "POST",
        data: { id: id_venda },
        success: function(resposta) {
            if (resposta.trim() !== "sucesso") {
                Swal.fire("Erro!", "Não foi possível carregar a venda: " + resposta, "error");
                return;
            }
            window.location = "vendas";
        },
        error: function() {
            Swal.fire("Erro de Conexão", "Não foi possível carregar os dados da venda.", "error");
        }
    });
}
</script>
