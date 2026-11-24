<?php
// Tela comum dos cadastros simples do financeiro. Quem inclui define $cadfin_entidade.
require_once(__DIR__ . "/../../funcoes/financeiro.php");
require_once(__DIR__ . "/config.php");
$cadfin = cadfin_config($cadfin_entidade);
$cadfin_ativo = fin_col_existe($pdo, $cadfin['tabela'], 'ativo');
?>
<div class="d-flex justify-content-between align-items-center mt-2 mb-3">
    <h4 class="mb-0"><?= fin_e($cadfin['titulo']) ?></h4>
    <a class="btn btn-primary text-white" onclick="cadNovo()"><i class="fe fe-plus me-2"></i>Adicionar</a>
</div>
<div class="card custom-card"><div class="card-body" id="listar">Carregando...</div></div>

<div class="modal fade" id="modalCad" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header bg-primary text-white">
                <h5 class="modal-title" id="cad-titulo">Adicionar</h5>
                <button class="btn-close btn-close-white" data-bs-dismiss="modal" type="button" id="btn-fechar-cad"></button>
            </div>
            <form id="form-cad">
                <div class="modal-body">
                    <label>Nome</label>
                    <input type="text" class="form-control mb-2" name="nome" id="cad-nome" required maxlength="100">
                    <?php if ($cadfin['grupos']) { ?>
                        <label>Grupo na DRE</label>
                        <select class="form-select mb-2" name="grupo_dre" id="cad-grupo">
                            <?php foreach ($cadfin['grupos'] as $k => $n) echo '<option value="' . $k . '">' . fin_e($n) . '</option>'; ?>
                        </select>
                    <?php } ?>
                    <?php if ($cadfin_ativo) { ?>
                        <label>Situação</label>
                        <select class="form-select" name="ativo" id="cad-ativo"><option value="1">Ativo</option><option value="0">Inativo</option></select>
                    <?php } ?>
                    <input type="hidden" name="id" id="cad-id">
                    <input type="hidden" name="e" value="<?= fin_e($cadfin_entidade) ?>">
                    <div id="msg-cad" class="text-danger small mt-2"></div>
                </div>
                <div class="modal-footer"><button type="submit" class="btn btn-success">Salvar</button></div>
            </form>
        </div>
    </div>
</div>

<script>
    var CADFIN = '<?= fin_js($cadfin_entidade) ?>';
    function buscar() { $.post('paginas/cadastro_fin/listar.php', { e: CADFIN }, function (h) { $('#listar').html(h); }); }
    function cadNovo() {
        $('#form-cad')[0].reset(); $('#cad-id').val(''); $('#cad-nome').prop('readonly', false);
        $('#cad-titulo').text('Adicionar'); $('#msg-cad').text(''); $('#modalCad').modal('show');
    }
    function cadEditar(i) {
        $('#cad-id').val(i.id); $('#cad-nome').val(i.nome).prop('readonly', !!i.prot);
        $('#cad-grupo').val(i.grupo); $('#cad-ativo').val(String(i.ativo));
        $('#cad-titulo').text('Editar'); $('#msg-cad').text(''); $('#modalCad').modal('show');
    }
    function cadExcluir(id, uso) {
        Swal.fire({ title: 'Excluir?', text: uso ? uso + ' lançamento(s) usam este cadastro e ficarão sem ele.' : 'Este cadastro não está em uso.',
            icon: 'warning', showCancelButton: true, confirmButtonText: 'Excluir', cancelButtonText: 'Cancelar', confirmButtonColor: '#d33' })
            .then(function (r) {
                if (!r.isConfirmed) return;
                $.post('paginas/cadastro_fin/excluir.php', { e: CADFIN, id: id }, function (m) {
                    if (m.trim() == 'Excluído com Sucesso') buscar(); else Swal.fire({ icon: 'warning', text: m });
                });
            });
    }
    $('#form-cad').on('submit', function (e) {
        e.preventDefault();
        $.post('paginas/cadastro_fin/salvar.php', $(this).serialize(), function (m) {
            if (m.trim() == 'Salvo com Sucesso') { $('#btn-fechar-cad').click(); buscar(); } else $('#msg-cad').text(m);
        });
    });
    $(function () { buscar(); });
</script>
