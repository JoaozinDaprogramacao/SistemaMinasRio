<?php
$tabela = 'detalhes_materiais';
require_once("../../../conexao.php");
?>


<script>
function toggleFiltros() {
    const container = $('#filtros-container');
    container.toggle(300, function() {
        $('#btn-filtros i').toggleClass('bi-chevron-down bi-chevron-up');
    });
}

function aplicarFiltros() {
    const filtros = {
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
        url: 'paginas/detalhes_materiais/carregar_tabela.php',
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
            "url": '//cdn.datatables.net/plug-ins/1.13.7/i18n/pt-BR.json'
        },
        "dom": "<'row'<'col-sm-12 col-md-6'l><'col-sm-12 col-md-6'f>>" +
               "<'row'<'col-sm-12'tr>>" +
               "<'row'<'col-sm-12 col-md-5'i><'col-sm-12 col-md-7'p>>",
        "processing": true,
        "ordering": false,
        "orderMulti": true,
        "stateSave": true,
        "columnDefs": [
            { "orderable": false, "targets": [0, 5] } // Desativa ordenação em colunas específicas
        ]
    });
}

$(document).ready(function() {
    aplicarFiltros();
    $('#filtros-container input, #filtros-container select').change(aplicarFiltros);
});
</script>

<div class="container-fluid mt-3">
    <!-- Botão e Filtros -->
    <div class="row mb-3">
        <div class="col-md-12">
            <button class="btn btn-outline-primary btn-sm" type="button" id="btn-filtros" onclick="toggleFiltros()">
                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-funnel me-1" viewBox="0 0 16 16">
                    <path d="M1.5 1.5A.5.5 0 0 1 2 1h12a.5.5 0 0 1 .5.5v2a.5.5 0 0 1-.128.334L10 8.692V13.5a.5.5 0 0 1-.342.474l-3 1A.5.5 0 0 1 6 14.5V8.692L1.628 3.834A.5.5 0 0 1 1.5 3.5v-2zm1 .5v1.308l4.372 4.858A.5.5 0 0 1 7 8.5v5.306l2-.666V8.5a.5.5 0 0 1 .128-.334L13.5 3.808V2h-11z"/>
                </svg>
                Filtros
                <i class="bi bi-chevron-down ms-1"></i>
            </button>
            
            <div id="filtros-container" style="display: none;" class="border p-3 bg-light rounded shadow-sm mt-2">
                <div class="row g-2">
                    <!-- Filtros de Data -->
                    <div class="col-md-2">
                        <input type="date" class="form-control form-control-sm" id="data_inicio" placeholder="Data Início">
                    </div>
                    <div class="col-md-2">
                        <input type="date" class="form-control form-control-sm" id="data_fim" placeholder="Data Fim">
                    </div>

                    <!-- Filtros de Tipo e Status -->
                    <div class="col-md-2">
                        <select class="form-select form-select-sm" id="tipo_movimento">
                            <option value="">Todos Movimentos</option>
                            <option value="compra">Compra</option>
                            <option value="venda">Venda</option>
                        </select>
                    </div>

                    <!-- Filtros de Valores -->
                    <div class="col-md-2">
                        <input type="number" class="form-control form-control-sm" id="valor_min" placeholder="Valor Mínimo" step="0.01">
                    </div>
                    <div class="col-md-2">
                        <input type="number" class="form-control form-control-sm" id="valor_max" placeholder="Valor Máximo" step="0.01">
                    </div>

                    <!-- Filtros de Quantidade -->
                    <div class="col-md-3">
                        <input type="number" class="form-control form-control-sm" id="quantidade_min" placeholder="Qtd. Mínima">
                    </div>
                    <div class="col-md-3">
                        <input type="number" class="form-control form-control-sm" id="quantidade_max" placeholder="Qtd. Máxima">
                    </div>

                    <!-- Filtros Relacionais -->
                    <div class="col-md-2">
                        <select class="form-select form-select-sm" id="material">
                            <option value="">Todos Materiais</option>
                            <?php
                            $query = $pdo->query("SELECT id, nome FROM materiais");
                            $materiais = $query->fetchAll(PDO::FETCH_ASSOC);
                            foreach ($materiais as $material) {
                                echo "<option value='{$material['id']}'>{$material['nome']}</option>";
                            }
                            ?>
                        </select>
                    </div>
                    <div class="col-md-2">
                        <select class="form-select form-select-sm" id="fornecedor">
                            <option value="">Todos Fornecedores</option>
                            <?php
                            $query = $pdo->query("SELECT id, nome_atacadista FROM fornecedores");
                            $fornecedores = $query->fetchAll(PDO::FETCH_ASSOC);
                            foreach ($fornecedores as $fornecedor) {
                                echo "<option value='{$fornecedor['id']}'>{$fornecedor['nome_atacadista']}</option>";
                            }
                            ?>
                        </select>
                    </div>
                    <div class="col-md-2">
                        <select class="form-select form-select-sm" id="responsavel">
                            <option value="">Todos Responsáveis</option>
                            <?php
                            $query = $pdo->query("SELECT id, nome FROM usuarios ORDER BY nome");
                            $usuarios = $query->fetchAll(PDO::FETCH_ASSOC);
                            foreach ($usuarios as $usuario) {
                                echo "<option value='{$usuario['id']}'>{$usuario['nome']}</option>";
                            }
                            ?>
                        </select>
                    </div>

                    <!-- Filtros Avançados -->
                    <div class="col-md-2">
                        <select class="form-select form-select-sm" id="ordenacao">
                            <option value="">Ordenar Por</option>
                            <option value="data_asc">Data (Mais Antiga)</option>
                            <option value="data_desc">Data (Mais Recente)</option>
                            <option value="valor_asc">Valor (Crescente)</option>
                            <option value="valor_desc">Valor (Decrescente)</option>
                        </select>
                    </div>
                </div>
                <div class="row mt-2">
                    <div class="col-md-12 text-end">
                        <button type="button" onclick="aplicarFiltros()" class="btn btn-primary btn-sm me-1">
                            <i class="bi bi-check-lg"></i> Aplicar
                        </button>
                        <button type="button" onclick="limparFiltros()" class="btn btn-outline-secondary btn-sm">
                            <i class="bi bi-x-lg"></i> Limpar
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Container da Tabela -->
    <div id="container-tabela">
        <?php include('carregar_tabela.php'); ?>
    </div>
</div>
