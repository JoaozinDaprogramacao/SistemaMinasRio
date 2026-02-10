<?php
$tabela = 'taxas_abatimentos';
require_once("../../../conexao.php");

$query = $pdo->query("SELECT * from $tabela order by id asc");
$res = $query->fetchAll(PDO::FETCH_ASSOC);
$linhas = @count($res);

if ($linhas > 0) {
    echo <<<HTML
    <table class="table table-bordered text-nowrap border-bottom dt-responsive" id="tabela">
    <thead> 
    <tr> 
    <th align="center" width="5%" class="text-center">Selecionar</th>
    <th>Descrição</th>      
    <th>INFO (Unidades)</th>   
    <th>Taxas/Preços</th>
    <th>Ações</th>
    </tr> 
    </thead> 
    <tbody> 
HTML;

    for ($i = 0; $i < $linhas; $i++) {
        $id = $res[$i]['id'];
        $descricao = $res[$i]['descricao'];
        $info = $res[$i]['info'];
        $valor_taxa = $res[$i]['valor_taxa'];

        $unidades_f = str_replace(';', ' | ', $info);
        $taxas_f = str_replace(';', ' | ', $valor_taxa);

        echo <<<HTML
<tr>
<td align="center">
<div class="custom-checkbox custom-control">
<input type="checkbox" class="custom-control-input" id="seletor-{$id}" onchange="selecionar('{$id}')">
<label for="seletor-{$id}" class="custom-control-label mt-1 text-dark"></label>
</div>
</td>
<td>{$descricao}</td>
<td><span class="text-muted">{$unidades_f}</span></td>
<td><span class="text-primary">{$taxas_f}</span></td>
<td>
    <big><a class="btn btn-info btn-sm" href="#" onclick="editar('{$id}','{$descricao}','{$info}','{$valor_taxa}')" title="Editar Dados"><i class="fa fa-edit "></i></a></big>
    <div class="dropdown" style="display: inline-block;">                      
        <a class="btn btn-danger btn-sm" href="#" data-bs-toggle="dropdown"><i class="fa fa-trash "></i> </a>
        <div class="dropdown-menu tx-13">
            <div style="width: 240px; padding:15px 5px 0 10px;" class="dropdown-item-text">
                <p>Confirmar Exclusão? <a href="#" onclick="excluir('{$id}')"><span class="text-danger">Sim</span></a></p>
            </div>
        </div>
    </div>
</td>
</tr>
HTML;
    }
} else {
    echo 'Não possui nenhum cadastro!';
}

echo "</tbody></table>";
?>

<script type="text/javascript">
    $(document).ready(function() {
        $('#tabela').DataTable({
            "ordering": false,
            "stateSave": true
        });

        if ($('.sel2').length > 0) {
            $('.sel2').select2({
                dropdownParent: $('#modalForm')
            });
        }
    });

    function editar(id, descricao, info, valor_taxa) {
        $('#mensagem').text('');
        $('#titulo_inserir').text('Editar Registro');
        $('#id').val(id);
        $('#descricao').val(descricao);

        unidadesSet.clear();
        if (info && info.trim() !== "") {
            info.split(';').forEach(v => {
                if (v.trim() !== "") unidadesSet.add(v.trim());
            });
        }
        renderUnidades();

        taxasSet.clear();
        if (valor_taxa && valor_taxa.trim() !== "") {
            valor_taxa.split(';').forEach(v => {
                if (v.trim() !== "") taxasSet.add(v.trim());
            });
        }
        renderTaxas();

        $('#modalForm').modal('show');
    }

    function limparCampos() {
        $('#id').val('');
        $('#descricao').val('');
        $('#input_taxa').val('');
        $('#select_unidade').val('');
        unidadesSet.clear();
        taxasSet.clear();
        renderUnidades();
        renderTaxas();
        $('#ids').val('');
        $('#btn-deletar').hide();
    }

    function selecionar(id) {
        var ids = $('#ids').val();
        if ($('#seletor-' + id).is(":checked")) {
            $('#ids').val(ids + id + '-');
        } else {
            $('#ids').val(ids.replace(id + '-', ''));
        }
        $('#ids').val() == "" ? $('#btn-deletar').hide() : $('#btn-deletar').show();
    }

    function deletarSel() {
        var ids = $('#ids').val().split("-");
        for (i = 0; i < ids.length - 1; i++) {
            excluirMultiplos(ids[i]);
        }
        setTimeout(() => {
            listar();
        }, 1000);
        limparCampos();
    }
</script>