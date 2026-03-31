<div class="row g-2 mb-3 align-items-center">
    <div class="col-auto">
        <select id="atacadista" class="form-select form-select-sm" onchange="buscar()">
            <option value="">Atacadista</option>
            <?php
            $query = $pdo->query("SELECT * FROM clientes ORDER BY nome ASC");
            $res = $query->fetchAll(PDO::FETCH_ASSOC);
            foreach ($res as $row) {
                echo '<option value="' . $row['id'] . '">' . $row['nome'] . '</option>';
            }
            ?>
        </select>
    </div>

    <div class="col-auto">
        <select id="formaPGTO" class="form-select form-select-sm" onchange="buscar()">
            <option value="">Forma PGTO</option>
            <?php
            $query = $pdo->query("SELECT * FROM formas_pgto ORDER BY nome ASC");
            $res = $query->fetchAll(PDO::FETCH_ASSOC);
            foreach ($res as $row) {
                echo '<option value="' . $row['id'] . '">' . $row['nome'] . '</option>';
            }
            ?>
        </select>
    </div>

    <div class="col-auto">
        <select id="tipo_conta" class="form-select form-select-sm" onchange="buscar()">
            <option value="">À Vista / A Prazo</option>
            <option value="À Vista">À Vista</option>
            <option value="A Prazo">A Prazo</option>
        </select>
    </div>

    <div class="col-auto">
        <select id="filtrar_por" class="form-select form-select-sm" onchange="buscar()">
            <option value="vencimento">Por Vencimento</option>
            <option value="data_pgto">Por Faturamento (Pagamento)</option>
            <option value="data_lanc">Por Lançamento</option>
        </select>
    </div>

    <div class="col-auto">
        <select id="select_periodo" class="form-select form-select-sm" onchange="definirPeriodo(this.value)">
            <option value="">Personalizado</option>
            <option value="hoje">Hoje</option>
            <option value="mes">Este Mês</option>
            <option value="mes_passado">Mês Passado</option>
            <option value="ano">Este Ano</option>
        </select>
    </div>

    <div class="col-auto d-flex align-items-center gap-1">
        <input type="date" id="dataInicial" class="form-control form-control-sm" value="<?php echo $data_inicio_mes ?>" onchange="alteracaoManualData()">
        <span class="text-muted">até</span>
        <input type="date" id="dataFinal" class="form-control form-control-sm" value="<?php echo $data_hoje ?>" onchange="alteracaoManualData()">
    </div>
</div>