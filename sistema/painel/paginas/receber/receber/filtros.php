<div class="row g-2 mb-3 align-items-center">
    <!-- Filtro de Atacadista -->
    <div class="col-auto">
        <select name="atacadista" id="atacadista" class="form-select form-select-sm" onchange="buscar()">
            <option value="">Atacadista</option>
            <?php
            $query = $pdo->query("SELECT * FROM clientes ORDER BY id DESC");
            $res = $query->fetchAll(PDO::FETCH_ASSOC);
            for ($i = 0; $i < @count($res); $i++) {
                echo '<option value="' . $res[$i]['id'] . '">' . $res[$i]['nome'] . ' - ' . $res[$i]['cpf'] . '</option>';
            }
            ?>
        </select>
    </div>

    <!-- Filtro de Forma de Pagamento -->
    <div class="col-auto">
        <select name="formaPGTO" id="formaPGTO" class="form-select form-select-sm" onchange="buscar()">
            <option value="">Forma PGTO</option>
            <?php
            $query = $pdo->query("SELECT * FROM formas_pgto");
            $res = $query->fetchAll(PDO::FETCH_ASSOC);
            for ($i = 0; $i < @count($res); $i++) {
                echo '<option value="' . $res[$i]['id'] . '">' . $res[$i]['nome'] . '</option>';
            }
            ?>
        </select>
    </div>
    <div class="col-auto">
        <div id="reportrange" class="form-control form-control-sm" style="background: #fff; cursor: pointer; padding: 5px 10px; border: 1px solid #ccc; width: 100%; display: flex; align-items: center; gap: 8px;">
            <i class="fa fa-calendar"></i>&nbsp;
            <span id="labelData">Selecionar Período</span>
            <i class="fa fa-caret-down"></i>
            <input type="hidden" name="dataInicial" id="dataInicial">
            <input type="hidden" name="dataFinal" id="dataFinal">
        </div>
    </div>
</div>