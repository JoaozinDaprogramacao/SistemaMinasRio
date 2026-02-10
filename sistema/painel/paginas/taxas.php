<?php
$pag = 'taxas';
if (@$categorias == 'ocultar') {
    echo "<script>window.location='../index.php'</script>";
    exit();
}
?>

<div class="justify-content-between">
    <div class="left-content mt-2 mb-3">
        <a class="btn ripple btn-primary text-white" onclick="inserir()" type="button"><i class="fe fe-plus me-2"></i> Nova Taxa / Abatimento</a>

        <div class="dropdown" style="display: inline-block;">
            <a href="#" aria-expanded="false" aria-haspopup="true" data-bs-toggle="dropdown" class="btn btn-danger dropdown" id="btn-deletar" style="display:none"><i class="fe fe-trash-2"></i> Deletar</a>
            <div class="dropdown-menu tx-13">
                <div style="width: 240px; padding:15px 5px 0 10px;" class="dropdown-item-text">
                    <p>Excluir Selecionados? <a href="#" onclick="deletarSel()"><span class="text-danger">Sim</span></a></p>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="row row-sm">
    <div class="col-lg-12">
        <div class="card custom-card">
            <div class="card-body" id="listar"></div>
        </div>
    </div>
</div>

<input type="hidden" id="ids">

<div class="modal fade" id="modalForm" tabindex="-1" aria-labelledby="exampleModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header bg-primary text-white">
                <h4 class="modal-title" id="exampleModalLabel"><span id="titulo_inserir"></span></h4>
                <button id="btn-fechar" aria-label="Close" class="btn-close" data-bs-dismiss="modal" type="button"><span class="text-white" aria-hidden="true">&times;</span></button>
            </div>
            <form id="form">
                <div class="modal-body">
                    <div class="row">
                        <div class="col-md-12 mb-3">
                            <label>Descrição da Taxa</label>
                            <input type="text" class="form-control" id="descricao" name="descricao" placeholder="Ex: ABANORTE" required>
                        </div>

                        <div class="col-md-12 mb-3">
                            <label>Unidades de Medida (INFO)</label>
                            <div class="input-group">
                                <select class="form-control" id="select_unidade">
                                    <option value="">Selecionar Unidade</option>
                                    <?php
                                    $query = $pdo->query("SELECT * FROM unidade_medida ORDER BY nome ASC");
                                    $res = $query->fetchAll(PDO::FETCH_ASSOC);
                                    foreach ($res as $row) {
                                        echo "<option value='{$row['unidade']}'>{$row['nome']} ({$row['unidade']})</option>";
                                    }
                                    ?>
                                </select>
                                <button type="button" class="btn btn-secondary" onclick="addUnidade()">Add</button>
                            </div>
                            <div id="lista_unidades" class="mt-2" style="min-height: 30px;"></div>
                            <input type="hidden" name="info" id="info">
                        </div>

                        <div class="col-md-12 mb-3">
                            <label>Taxas / Preços Unitários (Aceita %)</label>
                            <div class="input-group">
                                <input type="text" class="form-control" id="input_taxa" placeholder="Ex: 52,80 ou 10%">
                                <button type="button" class="btn btn-secondary" onclick="addTaxa()">Add</button>
                            </div>
                            <div id="lista_taxas" class="mt-2" style="min-height: 30px;"></div>
                            <input type="hidden" name="valor_taxa" id="valor_taxa">
                        </div>

                        <div class="col-md-12" style="margin-top: 15px;">
                            <button id="btn_salvar" type="submit" class="btn btn-primary w-100">Salvar Registro</button>
                        </div>
                    </div>

                    <input type="hidden" id="id" name="id">
                    <br>
                    <small>
                        <div id="mensagem" align="center"></div>
                    </small>
                </div>
            </form>
        </div>
    </div>
</div>

<script type="text/javascript">
    var pag = "<?= $pag ?>";
    let unidadesSet = new Set();
    let taxasSet = new Set();

    function addUnidade() {
        let val = $('#select_unidade').val();
        if (val && !unidadesSet.has(val)) {
            unidadesSet.add(val);
            renderUnidades();
        }
        $('#select_unidade').val('');
    }

    function removeUnidade(val) {
        unidadesSet.delete(val);
        renderUnidades();
    }

    function renderUnidades() {
        let html = '';
        let arr = Array.from(unidadesSet);
        arr.forEach(v => {
            html += `<span class="badge bg-info me-1 p-2">${v} <i class="fa fa-times ms-1" onclick="removeUnidade('${v}')" style="cursor:pointer"></i></span>`;
        });
        $('#lista_unidades').html(html);
        $('#info').val(arr.join(';'));
    }

    function addTaxa() {
        let val = $('#input_taxa').val().trim();
        if (val !== "" && !taxasSet.has(val)) {
            taxasSet.add(val);
            renderTaxas();
        }
        $('#input_taxa').val('');
    }

    function removeTaxa(val) {
        taxasSet.delete(val);
        renderTaxas();
    }

    function renderTaxas() {
        let html = '';
        let arr = Array.from(taxasSet);
        arr.forEach(v => {
            html += `<span class="badge bg-dark me-1 p-2">${v} <i class="fa fa-times ms-1" onclick="removeTaxa('${v}')" style="cursor:pointer"></i></span>`;
        });
        $('#lista_taxas').html(html);
        $('#valor_taxa').val(arr.join(';'));
    }

    $('#input_taxa').keypress(function(e) {
        if (e.which == 13) {
            e.preventDefault();
            addTaxa();
        }
    });

    function inserir() {
        $('#mensagem').text('');
        $('#titulo_inserir').text('Inserir Registro');
        limparCampos();
        $('#modalForm').modal('show');
    }
</script>
<script src="js/ajax.js"></script>