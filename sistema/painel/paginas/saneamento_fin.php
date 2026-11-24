<?php
require_once("verificar.php");
require_once(__DIR__ . "/../funcoes/financeiro_saneamento.php");
$pag = 'saneamento_fin';

if (@$_SESSION['nivel'] != 'Administrador') {
    echo "<script>window.location='index'</script>";
    exit();
}
?>
<div class="mt-2 mb-3">
    <h4 class="mb-0">Saneamento financeiro</h4>
    <small class="text-muted">Acerta os dados gravados pelas versões antigas do sistema. As correções automáticas só fazem o que os próprios dados comprovam;
        o que depende do extrato real do banco fica listado para você decidir. Tudo fica registrado na Auditoria. Faça um backup do banco antes.</small>
</div>
<div id="listar">Analisando...</div>

<script>
    function buscar() { $.post('paginas/saneamento_fin/listar.php', {}, function (h) { $('#listar').html(h); }); }

    function sanAcao(dados, confirmar) {
        var enviar = function () {
            $.post('paginas/saneamento_fin/acao.php', dados, function (m) {
                Swal.fire({ icon: 'info', html: m }).then(buscar);
            });
        };
        if (!confirmar) return enviar();
        Swal.fire({ html: confirmar, icon: 'question', showCancelButton: true, confirmButtonText: 'Executar', cancelButtonText: 'Cancelar' })
            .then(function (r) { if (r.isConfirmed) enviar(); });
    }

    function executarAutomatico() {
        sanAcao({ acao: 'automatico' }, 'Executar as correções automáticas?<br><small>Registra os pagamentos antigos, converte os resíduos, lança no extrato os recebimentos que faltam e acerta os status.</small>');
    }
    function definirConta(tipo, id) {
        var banco = $('#conta-' + tipo + '-' + id).val();
        if (!banco) { Swal.fire({ icon: 'warning', text: 'Escolha a conta.' }); return; }
        sanAcao({ acao: 'conta', tipo: tipo, id: id, banco: banco });
    }
    function contasPadrao() {
        var itens = [];
        $('.sel-conta').each(function () { if ($(this).val()) itens.push({ tipo: $(this).data('tipo'), id: $(this).data('id'), banco: $(this).val() }); });
        if (!itens.length) { Swal.fire({ icon: 'warning', text: 'Nenhuma linha com conta escolhida.' }); return; }
        sanAcao({ acao: 'contas_lote', itens: JSON.stringify(itens) }, 'Lançar ' + itens.length + ' pagamento(s) no extrato com as contas escolhidas?');
    }
    function removerDobro(id) {
        sanAcao({ acao: 'remover_dobro', id: id }, 'Remover este pagamento em dobro?<br><small>Só confirme depois de ver no extrato do banco que o valor saiu <b>uma vez só</b>.</small>');
    }
    function manterDobro(id) {
        sanAcao({ acao: 'manter_dobro', id: id }, 'Manter os dois pagamentos?<br><small>Confirme só se o extrato do banco mostra as duas saídas.</small>');
    }
    $(function () { buscar(); });
</script>
