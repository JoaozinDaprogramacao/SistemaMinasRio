<?php
$tabela = 'vendas'; // Tabela principal desta página
require_once("../../../conexao.php");
?>

<!-- ========================================================== -->
<!-- ======================= SCRIPTS ========================== -->
<!-- ========================================================== -->

<script>
    // Função para mostrar/ocultar o painel de filtros
    function toggleFiltros() {
        const container = $('#filtros-container');
        container.slideToggle(300, function() {
            $('#btn-filtros i.bi').toggleClass('bi-chevron-down bi-chevron-up');
        });
    }


    // Coloque esta função dentro do seu arquivo JS principal ou no listar.php
    function mostrarDetalhes(id_venda) {
        // Limpa dados antigos do modal para mostrar o "carregando"
        $('#detalhes_cliente, #detalhes_vendedor, #detalhes_data, #detalhes_status, #detalhes_subtotal, #detalhes_desconto, #detalhes_frete, #detalhes_total, #detalhes_pago, #detalhes_restante').html('...');
        $('#listar-itens').html('<div class="text-center p-3">Carregando itens...</div>');

        $.ajax({
            url: 'paginas/vendas_detalhes/buscar_detalhes.php', // Caminho correto
            method: 'POST',
            data: {
                id: id_venda
            }, // Envia o ID da venda
            dataType: "json",
            success: function(result) {
                if (result.erro) {
                    alert(result.erro);
                    return;
                }
                // Preenche o modal com os dados recebidos do PHP
                $('#span_id_venda').text(result.id);
                $('#detalhes_cliente').text(result.cliente);
                $('#detalhes_vendedor').text(result.vendedor);
                $('#detalhes_data').text(result.data);
                $('#detalhes_status').html('<span class="fw-bold ' + result.classe_status + '">' + result.status + '</span>');

                $('#detalhes_subtotal').text(result.subtotal);
                $('#detalhes_desconto').text(result.desconto);
                $('#detalhes_frete').text(result.frete);
                $('#detalhes_total').text(result.total);
                $('#detalhes_pago').text(result.pago);
                $('#detalhes_restante').text(result.restante);

                $('#listar-itens').html(result.tabela_itens);

                // Ajusta o link do botão de imprimir para usar o ID do comprovante correto
                $('#btn_imprimir').attr('href', 'rel/comprovante_pdf.php?id=' + result.id_comprovante);

                // Abre o modal
                var myModal = new bootstrap.Modal(document.getElementById('modalDetalhes'));
                myModal.show();
            },
            error: function() {
                alert('Ocorreu um erro ao buscar os detalhes da venda. Tente novamente.');
            }
        });
    }

    // Função principal que aplica os filtros e recarrega a tabela
    function aplicarFiltros() {
        const filtros = {
            data_inicio: $('#data_inicio').val(),
            data_fim: $('#data_fim').val(),
            status_pagamento: $('#status_pagamento').val(),
            valor_min: $('#valor_min').val(),
            valor_max: $('#valor_max').val(),
            cliente: $('#cliente').val(),
            vendedor: $('#vendedor').val(),
            ordenacao: $('#ordenacao').val()
        };

        // Destrói a instância do DataTable se ela já existir, para recriá-la com novos dados
        if ($.fn.DataTable.isDataTable('#tabela')) {
            $('#tabela').DataTable().destroy();
        }

        // Requisição AJAX para buscar os dados filtrados
        $.ajax({
            url: 'paginas/vendas_detalhes/carregar_tabela.php',
            method: 'POST',
            data: filtros,
            success: function(resposta) {
                $('#container-tabela').html(resposta); // Insere a nova tabela no container
                initDataTable(); // Inicializa o DataTable na nova tabela
            }
        });
    }

    // Limpa todos os campos de filtro e aplica novamente
    function limparFiltros() {
        $('#filtros-container input').val('');
        $('#filtros-container select').val('');
        aplicarFiltros();
    }

    // Inicializa a biblioteca DataTables.net na tabela
    function initDataTable() {
        $('#tabela').DataTable({
            "language": {
                "url": '//cdn.datatables.net/plug-ins/1.13.7/i18n/pt-BR.json'
            },
            "dom": "<'row'<'col-sm-12 col-md-6'l><'col-sm-12 col-md-6'f>>" +
                "<'row'<'col-sm-12'tr>>" +
                "<'row'<'col-sm-12 col-md-5'i><'col-sm-12 col-md-7'p>>",
            "processing": true,
            "ordering": false, // A ordenação será controlada pelo filtro 'ordenacao'
            "stateSave": true,
            "columnDefs": [{
                    "orderable": false,
                    "targets": [0, 8]
                } // Desativa ordenação no checkbox e na coluna de Ações
            ]
        });
    }

    // Gatilho inicial ao carregar a página
    $(document).ready(function() {
        initDataTable(); // Inicializa a tabela com os dados que já foram carregados pelo include PHP

        // Adiciona um listener para aplicar filtros sempre que um campo for alterado
        $('#filtros-container input, #filtros-container select').change(aplicarFiltros);
    });
</script>

<!-- ========================================================== -->
<!-- ======================= HTML ============================= -->
<!-- ========================================================== -->

<div class="container-fluid mt-3">
    <!-- Botão e Painel de Filtros -->
    <div class="row mb-3">
        <div class="col-md-12">
            <button class="btn btn-outline-primary btn-sm" type="button" id="btn-filtros" onclick="toggleFiltros()">
                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-funnel me-1" viewBox="0 0 16 16">
                    <path d="M1.5 1.5A.5.5 0 0 1 2 1h12a.5.5 0 0 1 .5.5v2a.5.5 0 0 1-.128.334L10 8.692V13.5a.5.5 0 0 1-.342.474l-3 1A.5.5 0 0 1 6 14.5V8.692L1.628 3.834A.5.5 0 0 1 1.5 3.5v-2zm1 .5v1.308l4.372 4.858A.5.5 0 0 1 7 8.5v5.306l2-.666V8.5a.5.5 0 0 1 .128-.334L13.5 3.808V2h-11z" />
                </svg>
                Filtros Avançados
                <i class="bi bi-chevron-down ms-1"></i>
            </button>

            <div id="filtros-container" style="display: none;" class="border p-3 bg-light rounded shadow-sm mt-2">
                <div class="row g-2">
                    <!-- Filtros de Data -->
                    <div class="col-md-2">
                        <label class="form-label-sm">De:</label>
                        <input type="date" class="form-control form-control-sm" id="data_inicio">
                    </div>
                    <div class="col-md-2">
                        <label class="form-label-sm">Até:</label>
                        <input type="date" class="form-control form-control-sm" id="data_fim">
                    </div>

                    <!-- Filtro de Status de Pagamento -->
                    <div class="col-md-2">
                        <label class="form-label-sm">Status Pagamento:</label>
                        <select class="form-select form-select-sm" id="status_pagamento">
                            <option value="">Todos Status</option>
                            <option value="Pago">Pago</option>
                            <option value="Parcialmente Pago">Parcialmente Pago</option>
                            <option value="Aguardando Pagamento">Aguardando Pagamento</option>
                        </select>
                    </div>

                    <!-- Filtros de Valores -->
                    <div class="col-md-2">
                        <label class="form-label-sm">Valor Mínimo:</label>
                        <input type="number" class="form-control form-control-sm" id="valor_min" placeholder="R$ Mín." step="0.01">
                    </div>
                    <div class="col-md-2">
                        <label class="form-label-sm">Valor Máximo:</label>
                        <input type="number" class="form-control form-control-sm" id="valor_max" placeholder="R$ Máx." step="0.01">
                    </div>

                    <!-- Filtro de Cliente -->
                    <div class="col-md-2">
                        <label class="form-label-sm">Cliente:</label>
                        <select class="form-select form-select-sm" id="cliente">
                            <option value="">Todos Clientes</option>
                            <?php
                            $query_cli = $pdo->query("SELECT id, nome FROM clientes ORDER BY nome");
                            $clientes = $query_cli->fetchAll(PDO::FETCH_ASSOC);
                            foreach ($clientes as $cliente) {
                                echo "<option value='{$cliente['id']}'>{$cliente['nome']}</option>";
                            }
                            ?>
                        </select>
                    </div>

                    <!-- Filtro de Vendedor -->
                    <div class="col-md-2">
                        <label class="form-label-sm">Vendedor:</label>
                        <select class="form-select form-select-sm" id="vendedor">
                            <option value="">Todos Vendedores</option>
                            <?php
                            $query_user = $pdo->query("SELECT id, nome FROM usuarios ORDER BY nome");
                            $usuarios = $query_user->fetchAll(PDO::FETCH_ASSOC);
                            foreach ($usuarios as $usuario) {
                                echo "<option value='{$usuario['id']}'>{$usuario['nome']}</option>";
                            }
                            ?>
                        </select>
                    </div>

                    <!-- Filtro de Ordenação -->
                    <div class="col-md-2">
                        <label class="form-label-sm">Ordenar Por:</label>
                        <select class="form-select form-select-sm" id="ordenacao">
                            <option value="data_desc">Data (Mais Recente)</option>
                            <option value="data_asc">Data (Mais Antiga)</option>
                            <option value="valor_desc">Valor (Maior)</option>
                            <option value="valor_asc">Valor (Menor)</option>
                        </select>
                    </div>
                </div>
                <div class="row mt-2">
                    <div class="col-md-12 text-end">
                        <button type="button" onclick="limparFiltros()" class="btn btn-outline-secondary btn-sm">
                            <i class="bi bi-x-lg"></i> Limpar Filtros
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Container da Tabela (Carrega a tabela inicial) -->
    <div id="container-tabela">
        <?php include('carregar_tabela.php'); ?>
    </div>
</div>