<div class="row g-2 mb-3 align-items-center">
    <div class="col-auto">
        <select id="atacadista" class="form-select form-select-sm" onchange="buscar()">
            <option value="">Cliente</option>
            <?php echo fin_options($pdo, "SELECT id, nome FROM clientes ORDER BY nome") ?>
        </select>
    </div>

    <div class="col-auto">
        <select id="formaPGTO" class="form-select form-select-sm" onchange="buscar()">
            <option value="">Forma PGTO</option>
            <?php echo fin_options($pdo, "SELECT id, nome FROM formas_pgto ORDER BY nome") ?>
        </select>
    </div>

    <div class="col-auto">
        <select id="categoria_filtro" class="form-select form-select-sm" onchange="buscar()">
            <option value="">Categoria</option>
            <?php echo fin_options($pdo, "SELECT id, nome FROM categorias_receber ORDER BY nome") ?>
        </select>
    </div>

    <div class="col-auto">
        <select id="centro_filtro" class="form-select form-select-sm" onchange="buscar()">
            <option value="">Centro de Custo</option>
            <?php echo fin_options($pdo, "SELECT id, nome FROM centros_custo WHERE ativo = 1 ORDER BY nome") ?>
        </select>
    </div>

    <div class="col-auto">
        <select id="status_filtro" class="form-select form-select-sm" onchange="buscar()">
            <option value="">Situação</option>
            <option value="aberto">Em aberto</option>
            <option value="vencido">Vencidas</option>
            <option value="parcial">Recebidas em parte</option>
            <option value="pago">Recebidas</option>
        </select>
    </div>

    <div class="col-auto">
        <select id="tipo_conta" class="form-select form-select-sm" onchange="buscar()">
            <option value="">À Vista / A Prazo</option>
            <option value="A VISTA">À Vista</option>
            <option value="A PRAZO">A Prazo</option>
        </select>
    </div>

    <div class="col-auto">
        <select id="filtrar_por" class="form-select form-select-sm" onchange="buscar()">
            <option value="vencimento">Por Vencimento</option>
            <option value="data_lanc">Por Faturamento</option>
            <option value="data_pgto">Por Recebimento</option>
        </select>
    </div>

    <div class="col-auto">
        <select id="select_periodo" class="form-select form-select-sm" onchange="definirPeriodo(this.value)">
            <option value="">Personalizado</option>
            <option value="hoje">Hoje</option>
            <option value="mes" selected>Este Mês</option>
            <option value="mes_passado">Mês Passado</option>
            <option value="ano">Este Ano</option>
        </select>
    </div>

    <div class="col-auto d-flex align-items-center gap-1">
        <input type="date" id="dataInicial" class="form-control form-control-sm" value="<?php echo date('Y-m-01') ?>" onchange="alteracaoManualData()">
        <span class="text-muted">até</span>
        <input type="date" id="dataFinal" class="form-control form-control-sm" value="<?php echo date('Y-m-t') ?>" onchange="alteracaoManualData()">
    </div>
</div>
