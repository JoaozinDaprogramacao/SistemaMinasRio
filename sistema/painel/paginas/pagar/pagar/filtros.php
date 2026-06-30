<div class="row g-2 mb-3 align-items-center">
    <div class="col-auto">
        <select id="atacadista" class="form-select form-select-sm" onchange="buscar()">
            <option value="">Fornecedor</option>
            <?php
            $query = $pdo->query("SELECT * FROM fornecedores ORDER BY nome_atacadista ASC");
            $res = $query->fetchAll(PDO::FETCH_ASSOC);
            foreach ($res as $row) {
                echo '<option value="' . $row['id'] . '">' . $row['nome_atacadista'] . '</option>';
            }
            ?>
        </select>
    </div>

    <div class="col-auto">
        <select id="funcionario_filtro" class="form-select form-select-sm" onchange="buscar()">
            <option value="">Funcionário</option>
            <?php
            $query = $pdo->query("SELECT * FROM funcionarios ORDER BY nome ASC");
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
        <select id="categoria_filtro" class="form-select form-select-sm" onchange="buscar()">
            <option value="">Categoria</option>
            <?php
            try {
                require_once(__DIR__ . "/../../categorias_pagar/funcoes.php");
                garantir_categoria_romaneio($pdo);
                $q = $pdo->query("SELECT id, nome FROM categorias_pagar ORDER BY nome ASC");
                foreach ($q->fetchAll(PDO::FETCH_ASSOC) as $row) {
                    echo '<option value="' . $row['id'] . '">' . htmlspecialchars($row['nome']) . '</option>';
                }
            } catch (Exception $e) {}
            ?>
        </select>
    </div>

    <div class="col-auto">
        <select id="filtrar_por" class="form-select form-select-sm" onchange="buscar()">
            <option value="vencimento">Por Vencimento</option>
            <option value="data_lanc">Por Faturamento</option>
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
