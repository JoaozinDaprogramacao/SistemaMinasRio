<?php
$tabela = 'linha_bancos';
require_once("../../../conexao.php");

// Receber todos os filtros via POST
$filtros = [
    'p1' => $_POST['p1'] ?? '',
    'data_inicio' => $_POST['data_inicio'] ?? '',
    'data_fim' => $_POST['data_fim'] ?? '',
    'tipo_movimento' => $_POST['tipo_movimento'] ?? '',
    'valor_min' => $_POST['valor_min'] ?? '',
    'valor_max' => $_POST['valor_max'] ?? '',
    'notas_filtro' => $_POST['notas_filtro'] ?? '',
    'ordenacao' => $_POST['ordenacao'] ?? ''
];

// Construção segura da query com prepared statements
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

// Filtros de Valor
if (!empty($filtros['valor_min'])) {
    $where[] = "(credito >= :valor_min OR debito >= :valor_min)";
    $params[':valor_min'] = (float)$filtros['valor_min'];
}

if (!empty($filtros['valor_max'])) {
    $where[] = "(credito <= :valor_max OR debito <= :valor_max)";
    $params[':valor_max'] = (float)$filtros['valor_max'];
}

// Montar a query final
if (!empty($where)) {
    $query .= " WHERE " . implode(' AND ', $where);
}

// Ordenação
$query .= " ORDER BY data DESC";

// Executar a consulta
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

    $total_creditos = 0;
    $total_debitos = 0;
    $saldo_total = 0;

    foreach ($res as $item) {
        $data_formatada = date('d/m/Y \à\s H:i', strtotime($item['data']));
        $credito_formatado = number_format($item['credito'], 2, ',', '.');
        $debito_formatado = number_format($item['debito'], 2, ',', '.');
        $saldo_formatado = number_format($item['saldo'], 2, ',', '.');

        // Acumulando os totais
        $total_creditos += $item['credito'];
        $total_debitos += $item['debito'];
        $saldo_total = $item['saldo']; // Pega o último saldo que já está calculado

        $classe_credito = $credito_formatado == '0,00' ? '' : 'text-success';
        $classe_debito = $debito_formatado == '0,00' ? '' : 'text-danger';

        echo <<<HTML
        <tr>
            <td class="text-center">
                <div class="custom-control custom-checkbox">
                    <input type="checkbox" class="custom-control-input" id="seletor-{$item['id']}">
                    <label class="custom-control-label" for="seletor-{$item['id']}"></label>
                </div>
            </td>
            <td>{$data_formatada}</td>
            <td>{$item['n_fiscal']}</td>
            <td class="{$classe_credito}">R$ {$credito_formatado}</td>
            <td class="{$classe_debito}">R$ {$debito_formatado}</td>
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

    // Formatando os totais
    $total_creditos_f = number_format($total_creditos, 2, ',', '.');
    $total_debitos_f = number_format($total_debitos, 2, ',', '.');
    $saldo_total_f = number_format($saldo_total, 2, ',', '.');

    // Define a classe do saldo (positivo ou negativo)
    $classe_saldo = $saldo_total >= 0 ? 'text-success' : 'text-danger';

    echo <<<HTML
        </tbody>
        </table>
        
        <div class="card mt-3">
            <div class="card-header bg-light">
                <h5 class="mb-0">Resumo das Movimentações</h5>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-4">
                        <div class="border rounded p-3 text-center">
                            <h6 class="text-muted mb-2">Total Créditos</h6>
                            <h4 class="text-success mb-0">R$ {$total_creditos_f}</h4>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="border rounded p-3 text-center">
                            <h6 class="text-muted mb-2">Total Débitos</h6>
                            <h4 class="text-danger mb-0">R$ {$total_debitos_f}</h4>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="border rounded p-3 text-center">
                            <h6 class="text-muted mb-2">Saldo Total</h6>
                            <h4 class="{$classe_saldo} mb-0">R$ {$saldo_total_f}</h4>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
HTML;
} else {
    echo '<div class="alert alert-warning">Nenhum registro encontrado!</div>';
}
?>

<script>
function toggleFiltros() {
    const container = $('#filtros-container');
    container.toggle(300, function() {
        $('#btn-filtros i').toggleClass('bi-chevron-down bi-chevron-up');
    });
}

function aplicarFiltros() {
    // Obter o ID do banco da aba selecionada
    var banco_id = $('#cat').val();
    
    // Construir objeto de filtros
    var filtros = {
        p1: banco_id, // Usar o ID do banco passado pela função buscarCat
        data_inicio: $('#data_inicio').val(),
        data_fim: $('#data_fim').val(),
        tipo_movimento: $('#tipo_movimento').val(),
        status: $('#status').val(),
        valor_min: $('#valor_min').val(),
        valor_max: $('#valor_max').val(),
        quantidade_min: $('#quantidade_min').val(),
        quantidade_max: $('#quantidade_max').val(),
        material: $('#material').val(),
        fornecedor: $('#fornecedor').val(),
        responsavel: $('#responsavel').val(),
        descricao_filtro: $('#descricao_filtro').val(),
        localizacao_filtro: $('#localizacao_filtro').val(),
        notas_filtro: $('#notas_filtro').val(),
        ordenacao: $('#ordenacao').val()
    };

    if ($.fn.DataTable.isDataTable('#tabela')) {
        $('#tabela').DataTable().destroy();
    }

    $.ajax({
        url: 'paginas/detalhes_bancos/carregar_tabela.php',
        method: 'POST',
        data: filtros,
        success: function(resposta) {
            $('#container-tabela').html(resposta);
            initDataTable();
        }
    });
}

function limparFiltros() {
    $('#filtros-container input').val('');
    $('#filtros-container select').val('');
    aplicarFiltros();
}

function initDataTable() {
    $('#tabela').DataTable({
        "language": {
            // Definir diretamente as traduções mais importantes
            "emptyTable": "Nenhum registro encontrado",
            "search": "Pesquisar",
            "paginate": {
                "next": "Próximo",
                "previous": "Anterior"
            },
            "info": "Mostrando _START_ até _END_ de _TOTAL_ registros",
            "lengthMenu": "Mostrar _MENU_ por página"
        },
        "dom": "<'row'<'col-sm-12 col-md-6'l><'col-sm-12 col-md-6'f>>" +
               "<'row'<'col-sm-12'tr>>" +
               "<'row'<'col-sm-12 col-md-5'i><'col-sm-12 col-md-7'p>>",
        "processing": true,
        "ordering": false,
        "orderMulti": true,
        "stateSave": true,
        "columnDefs": [
            { "orderable": false, "targets": [0, 5] }
        ]
    });
}

$(document).ready(function() {
    // Primeira carga da tabela
    aplicarFiltros();
    
    // Configurar eventos de mudança nos filtros
    $('#filtros-container input, #filtros-container select').change(aplicarFiltros);
});
</script>

<div class="container-fluid mt-3">
    <!-- Container da Tabela -->
    <div id="container-tabela">
        <!-- Remover a linha abaixo -->
        <?php /* include('carregar_tabela.php'); */ ?>
    </div>
</div>

<style>
    .text-success {
        color: #28a745 !important;
    }
    .text-danger {
        color: #dc3545 !important;
    }
</style>
