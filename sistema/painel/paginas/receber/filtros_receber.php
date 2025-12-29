<div class="row g-2 mb-3 align-items-center">
    <div class="col-auto">
        <select name="atacadista" id="atacadista" class="form-select form-select-sm" onchange="buscar()">
            <option value="">Filtrar Atacadista</option>
            <?php
            $res = $pdo->query("SELECT * FROM clientes ORDER BY nome ASC")->fetchAll(PDO::FETCH_ASSOC);
            foreach ($res as $row) {
                echo "<option value='{$row['id']}'>{$row['nome']}</option>";
            }
            ?>
        </select>
    </div>

    <div class="col-auto">
        <button type="button" class="btn btn-info btn-sm text-white" onclick="toggleFiltrosAvancados()">
            <i class="fa fa-filter"></i> Filtros Avançados
        </button>
    </div>
</div>

<div id="form-filtros-avancados" class="card mb-3 d-none shadow-sm border-info">
    <div class="row mt-3">
        <div class="col-md-6" id="card_total_material" style="display:none">
            <div class="alert alert-secondary p-2 mb-0">
                <small>Total em Material Selecionado:</small><br>
                <strong id="total_material_selecionado" class="text-primary">R$ 0,00</strong>
            </div>
        </div>
        <div class="col-md-6" id="card_total_produto" style="display:none">
            <div class="alert alert-secondary p-2 mb-0">
                <small>Total em Produto Selecionado:</small><br>
                <strong id="total_produto_selecionado" class="text-success">R$ 0,00</strong>
            </div>
        </div>
    </div>
    <div class="card-body bg-light">
        <p class="small fw-bold text-muted mb-2">Selecione um ou mais filtros para combinar:</p>

        <div class="row g-2">
            <div class="col-auto">
                <input type="checkbox" class="btn-check" id="check_romaneio" autocomplete="off" onchange="buscar()">
                <label class="btn btn-outline-primary btn-sm" for="check_romaneio">
                    <i class="fa fa-list-alt"></i> Todos os Romaneios
                </label>
            </div>

            <div class="col-auto">
                <input type="checkbox" class="btn-check" id="check_material" autocomplete="off" onchange="toggleVisaoFiltro('materiais')">
                <label class="btn btn-outline-primary btn-sm" for="check_material">
                    <i class="fa fa-cube"></i> Materiais
                </label>
            </div>

            <div class="col-auto">
                <input type="checkbox" class="btn-check" id="check_produto" autocomplete="off" onchange="toggleVisaoFiltro('produtos')">
                <label class="btn btn-outline-primary btn-sm" for="check_produto">
                    <i class="fa fa-tag"></i> Produtos/Variedades
                </label>
            </div>
        </div>

        <hr class="my-3">

        <div class="row">
            <div id="div_sel_materiais" class="col-md-4 d-none">
                <label class="small text-primary fw-bold">Escolha o Material:</label>
                <select id="w_material" class="form-select form-select-sm" onchange="buscar()">
                    <option value="">Todos os Materiais</option>
                    <?php
                    $res = $pdo->query("SELECT * FROM materiais ORDER BY nome ASC")->fetchAll(PDO::FETCH_ASSOC);
                    foreach ($res as $row) {
                        echo "<option value='{$row['id']}'>{$row['nome']}</option>";
                    }
                    ?>
                </select>
            </div>

            <div id="div_sel_produtos" class="col-md-6 d-none">
                <label class="small text-primary fw-bold">Escolha o Produto:</label>
                <select id="w_produto" class="form-select form-select-sm" onchange="buscar()">
                    <option value="">Todos os Produtos</option>
                    <?php
                    $query_p = $pdo->query("SELECT p.id, p.nome, c.nome as cat FROM produtos p INNER JOIN categorias c ON p.categoria = c.id ORDER BY p.nome ASC");
                    $res_p = $query_p->fetchAll(PDO::FETCH_ASSOC);
                    foreach ($res_p as $item) {
                        echo "<option value='{$item['id']}'>" . htmlspecialchars($item['nome']) . " - " . htmlspecialchars($item['cat']) . "</option>";
                    }
                    ?>
                </select>
            </div>
        </div>
    </div>
</div>