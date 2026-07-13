<?php
require_once("verificar.php");
$pag = 'categorias_pagar';
?>

<div class="justify-content-between">
    <div class="left-content mt-2 mb-3">
        <a class="btn ripple btn-primary text-white" onclick="inserir()" type="button">
            <i class="fe fe-plus me-2"></i> Nova Categoria
        </a>
        <div class="dropdown" style="display: inline-block;">
            <a href="#" class="btn btn-danger dropdown-toggle" data-bs-toggle="dropdown" id="btn-deletar" style="display:none">
                <i class="fe fe-trash-2"></i> Deletar
            </a>
            <div class="dropdown-menu p-3">
                <p>Excluir selecionados? <a href="#" onclick="deletarSel()" class="text-danger fw-bold">Sim</a></p>
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

<!-- Modal -->
<div class="modal fade" id="modalForm" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header bg-primary text-white">
                <h4 class="modal-title"><span id="titulo_inserir">Nova Categoria</span></h4>
                <button id="btn-fechar" class="btn-close" data-bs-dismiss="modal" type="button">
                    <span class="text-white" aria-hidden="true">&times;</span>
                </button>
            </div>
            <form id="form">
                <div class="modal-body">
                    <div class="row">
                        <div class="col-md-9">
                            <label>Nome da Categoria</label>
                            <input type="text" class="form-control" id="nome" name="nome" placeholder="Ex: Salário, Aluguel, Imposto..." required>
                        </div>
                        <div class="col-md-3 d-flex align-items-end">
                            <button id="btn_salvar" type="submit" class="btn btn-success w-100">Salvar</button>
                        </div>
                    </div>
                    <input type="hidden" name="id" id="id">
                    <br>
                    <small><div id="mensagem" align="center"></div></small>
                </div>
            </form>
        </div>
    </div>
</div>

<script>var pag = "<?= $pag ?>";</script>
<script src="js/ajax.js"></script>
