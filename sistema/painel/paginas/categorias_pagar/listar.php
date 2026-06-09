<?php
require_once("../../../conexao.php");
require_once("../../verificar.php");
$tabela = 'categorias_pagar';

$query = $pdo->query("SELECT * FROM $tabela ORDER BY nome ASC");
$res   = $query->fetchAll(PDO::FETCH_ASSOC);
$total = count($res);

if ($total > 0) {
    echo <<<HTML
    <table class="table table-bordered text-nowrap border-bottom dt-responsive" id="tabela">
    <thead>
        <tr>
            <th align="center" width="5%" class="text-center">Selecionar</th>
            <th>Nome da Categoria</th>
            <th>Ações</th>
        </tr>
    </thead>
    <tbody>
HTML;

    foreach ($res as $row) {
        $id   = $row['id'];
        $nome = htmlspecialchars($row['nome']);
        echo <<<HTML
    <tr>
        <td align="center">
            <div class="custom-checkbox custom-control">
                <input type="checkbox" class="custom-control-input" id="seletor-{$id}" onchange="selecionar('{$id}')">
                <label for="seletor-{$id}" class="custom-control-label mt-1 text-dark"></label>
            </div>
        </td>
        <td>{$nome}</td>
        <td>
            <big><a class="btn btn-info btn-sm" href="#" onclick="editar('{$id}','{$nome}')" title="Editar"><i class="fa fa-edit"></i></a></big>
            <div class="dropdown" style="display: inline-block;">
                <a class="btn btn-danger btn-sm" href="#" data-bs-toggle="dropdown"><i class="fa fa-trash"></i></a>
                <div class="dropdown-menu tx-13">
                    <div class="dropdown-item-text">
                        <p>Confirmar exclusão? <a href="#" onclick="excluir('{$id}')"><span class="text-danger">Sim</span></a></p>
                    </div>
                </div>
            </div>
        </td>
    </tr>
HTML;
    }

    echo <<<HTML
    </tbody>
    </table>
    <small><div align="center" id="mensagem-excluir"></div></small>
HTML;
} else {
    echo '<p class="text-muted mt-3">Nenhuma categoria cadastrada ainda.</p>';
}
?>

<script>
$(document).ready(function () {
    $('#tabela').DataTable({
        ordering: false,
        stateSave: true,
        language: { url: "//cdn.datatables.net/plug-ins/1.13.2/i18n/pt-BR.json" }
    });
});

function editar(id, nome) {
    $('#id').val(id);
    $('#nome').val(nome);
    $('#titulo_inserir').text('Editar Categoria');
    $('#modalForm').modal('show');
}

function limparCampos() {
    $('#nome').val('');
    $('#id').val('');
    $('#titulo_inserir').text('Nova Categoria');
}

function selecionar(id) {
    var ids = $('#ids').val();
    if ($('#seletor-' + id).is(':checked')) {
        $('#ids').val(ids + id + '-');
    } else {
        $('#ids').val(ids.replace(id + '-', ''));
    }
    $('#btn-deletar').toggle($('#ids').val() !== '');
}

function deletarSel() {
    var ids = $('#ids').val().split('-');
    for (var i = 0; i < ids.length - 1; i++) {
        excluirMultiplos(ids[i]);
    }
    setTimeout(function () { listar(); limparCampos(); }, 800);
}
</script>
