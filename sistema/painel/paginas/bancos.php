<?php
require_once("verificar.php");
$pag = 'bancos';

if (@$bancos == 'ocultar') {
    echo "<script>window.location='index'</script>";
    exit();
}

?>

<div class="justify-content-between">
    <form action="rel/receber_class.php" target="_blank" method="POST">
        <div class="left-content mt-2 mb-3">
            <a style="margin-bottom: 10px; margin-top: 5px" class="btn ripple btn-primary text-white" onclick="inserir()" type="button"><i class="fe fe-plus me-2"></i>Adicionar Conta</a>

            <div class="dropdown" style="display: inline-block;">
                <a href="#" aria-expanded="false" aria-haspopup="true" data-bs-toggle="dropdown" class="btn btn-danger dropdown" id="btn-deletar" style="display:none"><i class="fe fe-trash-2"></i> Deletar</a>
                <div class="dropdown-menu tx-13">
                    <div style="width: 240px; padding:15px 5px 0 10px;" class="dropdown-item-text">
                        <p>Excluir Selecionados? <a href="#" onclick="deletarSel()"><span class="text-danger">Sim</span></a></p>
                    </div>
                </div>
            </div>


            <div class="cab_mobile"></div>
        </div>


        <div class="row row-sm">
            <div class="col-lg-12">
                <div class="card custom-card">
                    <div class="card-body" id="listar">

                    </div>
                </div>
            </div>
        </div>


        <input type="hidden" name="tipo_data" id="tipo_data">
        <input type="hidden" name="pago" id="pago">
        <input type="hidden" name="tipo_data_filtro" id="tipo_data_filtro">

    </form>

</div>


<input type="hidden" id="ids">




<script type="text/javascript">
    var pag = "<?= $pag ?>"
</script>
<script src="js/ajax.js"></script>