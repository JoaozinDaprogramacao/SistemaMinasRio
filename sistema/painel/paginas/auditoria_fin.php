<?php
require_once("verificar.php");
require_once(__DIR__ . "/../funcoes/financeiro.php");
$pag = 'auditoria_fin';

if (@$auditoria_fin == 'ocultar') {
    echo "<script>window.location='index'</script>";
    exit();
}
?>
<div class="mt-2 mb-3">
    <h4 class="mb-0">Auditoria financeira</h4>
    <small class="text-muted">Quem fez o quê no financeiro: lançamentos, baixas, estornos, exclusões, conciliações e alterações de parâmetros.</small>
</div>
<div class="row g-2 mb-3 align-items-end">
    <div class="col-auto"><label class="small">De</label><input type="date" id="au-ini" class="form-control form-control-sm" value="<?= date('Y-m-d', strtotime('-30 days')) ?>" onchange="buscar()"></div>
    <div class="col-auto"><label class="small">Até</label><input type="date" id="au-fim" class="form-control form-control-sm" value="<?= date('Y-m-d') ?>" onchange="buscar()"></div>
    <div class="col-auto"><label class="small">Usuário</label>
        <select id="au-usuario" class="form-select form-select-sm" onchange="buscar()"><?= fin_options($pdo, "SELECT id, nome FROM usuarios ORDER BY nome", null, 'Todos') ?></select></div>
    <div class="col-auto"><label class="small">Ação</label>
        <select id="au-acao" class="form-select form-select-sm" onchange="buscar()">
            <option value="">Todas</option>
            <?= fin_options($pdo, "SELECT DISTINCT acao, acao FROM fin_auditoria ORDER BY acao") ?>
        </select></div>
    <div class="col"><label class="small">Buscar</label><input type="text" id="au-busca" class="form-control form-control-sm" placeholder="Texto da descrição" onkeyup="if(event.key==='Enter')buscar()"></div>
</div>
<div class="card custom-card"><div class="card-body p-0" id="listar">Carregando...</div></div>
<script>
    function buscar() {
        $.post('paginas/auditoria_fin/listar.php', { ini: $('#au-ini').val(), fim: $('#au-fim').val(), usuario: $('#au-usuario').val(), acao: $('#au-acao').val(), busca: $('#au-busca').val() },
            function (h) { $('#listar').html(h); });
    }
    $(function () { buscar(); });
</script>
