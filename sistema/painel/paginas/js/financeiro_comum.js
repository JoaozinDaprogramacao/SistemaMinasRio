// =====================================================================
// Comportamentos comuns de Contas a Receber e Contas a Pagar.
// Carregado DEPOIS de js/ajax.js para substituir o envio do formulário e a
// exclusão genéricos por versões que respeitam os filtros e mostram erros.
// =====================================================================

function finAviso(msg, tipo) {
    if (window.Swal) {
        Swal.fire({ icon: tipo || 'info', text: msg, confirmButtonColor: '#0d6efd' });
    } else {
        alert(msg);
    }
}

function finRecarregar() {
    if (typeof buscar === 'function') buscar(); else if (typeof listar === 'function') listar();
}

// ---------------------------------------------------------------------
// Formulário de inserir/editar (substitui o handler genérico do ajax.js)
// ---------------------------------------------------------------------
$(function () {
    $('#form').off('submit').on('submit', function (e) {
        e.preventDefault();
        var formData = new FormData(this);
        $('#mensagem').removeClass().text('Salvando...');
        $('#btn_salvar').hide();
        $.ajax({
            url: 'paginas/' + pag + '/salvar.php', type: 'POST', data: formData,
            cache: false, contentType: false, processData: false,
            success: function (mensagem) {
                $('#btn_salvar').show();
                if (mensagem.trim() == 'Salvo com Sucesso') {
                    $('#mensagem').text('');
                    $('#btn-fechar').click();
                    finRecarregar();
                } else {
                    $('#mensagem').addClass('text-danger').text(mensagem);
                }
            },
            error: function () {
                $('#btn_salvar').show();
                $('#mensagem').addClass('text-danger').text('Erro ao conectar com o servidor.');
            }
        });
    });

    // "Recebido/Pago em" preenchido: pede a conta bancária
    function mostrarBanco() {
        if ($('#data_pgto').val()) $('#div-banco').removeClass('d-none');
        else $('#div-banco').addClass('d-none');
    }
    $(document).on('change', '#data_pgto', mostrarBanco);
    $('#modalForm').on('shown.bs.modal', mostrarBanco);

    // Forma de pagamento escolhida no formulário: sugere a conta padrão dela
    $(document).on('change', '#forma_pgto', function () {
        var banco = $(this).find('option:selected').data('banco');
        if (banco && !$('#banco').val()) $('#banco').val(banco);
    });
});

// ---------------------------------------------------------------------
// Excluir (substitui o do ajax.js: confirma e mostra o motivo da recusa)
// ---------------------------------------------------------------------
function excluir(id) {
    $.ajax({
        url: 'paginas/' + pag + '/excluir.php', method: 'POST', data: { id: id },
        success: function (mensagem) {
            if (mensagem.trim() == 'Excluído com Sucesso') finRecarregar();
            else finAviso(mensagem, 'warning');
        }
    });
}

function deletarSel() {
    var ids = ($('#ids').val() || '').split('-').filter(function (v) { return v !== ''; });
    var pendentes = ids.length, erros = [];
    ids.forEach(function (id) {
        $.post('paginas/' + pag + '/excluir.php', { id: id }, function (m) {
            if (m.trim() != 'Excluído com Sucesso') erros.push('#' + id + ': ' + m);
        }).always(function () {
            if (--pendentes === 0) {
                if (erros.length) finAviso(erros.join('\n'), 'warning');
                finRecarregar();
            }
        });
    });
}

// ---------------------------------------------------------------------
// Estorno de baixa
// ---------------------------------------------------------------------
function estornarBaixa(id, descricao) {
    var enviar = function (motivo) {
        $.post('paginas/' + pag + '/estornar.php', { id: id, motivo: motivo }, function (m) {
            if (m.trim() == 'Estornado com Sucesso') {
                finRecarregar();
                if ($('#modalBaixar').hasClass('show')) $('#btn-fechar-baixar').click();
                finAviso('Baixa estornada. Os valores saíram do extrato bancário e a conta voltou a ficar em aberto.', 'success');
            } else {
                finAviso(m, 'warning');
            }
        });
    };
    if (window.Swal) {
        Swal.fire({
            title: 'Estornar baixa?',
            html: 'A baixa de <b>' + $('<div>').text(descricao || '').html() + '</b> será desfeita: os pagamentos saem do extrato bancário e a conta volta a ficar em aberto.',
            input: 'text', inputPlaceholder: 'Motivo do estorno (obrigatório)',
            showCancelButton: true, confirmButtonText: 'Estornar', cancelButtonText: 'Cancelar', confirmButtonColor: '#d33',
            inputValidator: function (v) { if (!v || !v.trim()) return 'Informe o motivo.'; }
        }).then(function (r) { if (r.isConfirmed) enviar(r.value); });
    } else {
        var motivo = prompt('Motivo do estorno:');
        if (motivo) enviar(motivo);
    }
}

// ---------------------------------------------------------------------
// Linhas de pagamento do modal de baixa: conta padrão da forma e adiantamentos
// ---------------------------------------------------------------------
$(document).on('change', '.forma_pagamento', function () {
    var linha = $(this).closest('.linha-pagamento');
    var banco = linha.find('.banco_pagamento');
    var valor = String($(this).val() || '');
    if (valor.indexOf('adi:') === 0) {
        // Pagamento com adiantamento não movimenta banco (o campo continua no envio para manter as linhas alinhadas)
        banco.val('').css({ 'pointer-events': 'none', 'opacity': .5 });
    } else {
        banco.css({ 'pointer-events': '', 'opacity': '' });
        var padrao = $(this).find('option:selected').data('banco');
        if (padrao && !banco.val()) banco.val(String(padrao));
    }
    if (typeof handlePagamentoInput === 'function') handlePagamentoInput(this);
});

// Acrescenta os adiantamentos da pessoa como opções de "forma" em todas as linhas (e no modelo)
function finInjetarAdiantamentos(lista) {
    $('.forma_pagamento optgroup.fin-adi').remove();
    if (!lista || !lista.length) return;
    var html = '<optgroup class="fin-adi" label="Usar adiantamento">';
    lista.forEach(function (a) { html += '<option value="adi:' + a.id + '">' + $('<div>').text(a.rotulo).html() + '</option>'; });
    html += '</optgroup>';
    $('.forma_pagamento').append(html);
}

// Busca dados extras da baixa (adiantamentos e permissão de estorno) para títulos em aberto
function finCarregarExtrasBaixa(id) {
    $.ajax({
        url: 'paginas/' + pag + '/buscar_baixa.php', method: 'POST', data: { id: id }, dataType: 'json',
        success: function (dados) {
            finInjetarAdiantamentos(dados.adiantamentos);
            finBotaoEstorno(id, dados);
        }
    });
}

function finBotaoEstorno(id, dados) {
    $('#btn-estornar-baixa').remove();
    if (dados && dados.pode_estornar && dados.pago && dados.pago !== 'Não') {
        var desc = $('#descricao-baixar').text();
        $('#btn-confirmar-baixar').before('<button type="button" id="btn-estornar-baixa" class="btn btn-outline-danger me-auto"><i class="fa fa-undo me-1"></i>Estornar baixa</button>');
        $('#btn-estornar-baixa').on('click', function () { estornarBaixa(id, desc); });
    }
}

// Linha de pagamento usável: valor, data e (banco ou adiantamento)
function finLinhaCompleta(linha) {
    var forma = String($(linha).find('.forma_pagamento').val() || '');
    return $(linha).find('.valor_pagamento').val().trim() !== '' && $(linha).find('.data_pagamento').val() !== '' && forma !== '' &&
        (forma.indexOf('adi:') === 0 || $(linha).find('.banco_pagamento').val() !== '');
}

// ---------------------------------------------------------------------
// Seleção múltipla e baixa em lote (mesmo comportamento nas duas telas)
// ---------------------------------------------------------------------
function lerIds() {
    return ($('#ids').val() || '').split('-').filter(function (v) { return v !== ''; });
}

function gravarIds(lista) {
    var unicos = lista.filter(function (v, i) { return lista.indexOf(v) === i; });
    $('#ids').val(unicos.length ? unicos.join('-') + '-' : '');
    if (unicos.length) $('#btn-deletar, #btn-baixar').show(); else $('#btn-deletar, #btn-baixar').hide();
}

function selecionar(id) {
    id = String(id);
    var lista = lerIds();
    if ($('#seletor-' + id).is(':checked')) lista.push(id);
    else { lista = lista.filter(function (v) { return v !== id; }); $('#selecionar-todos').prop('checked', false); }
    gravarIds(lista);
}

function selecionarTodos(master) {
    var linhas = $.fn.DataTable.isDataTable('#tabela') ? $('#tabela').DataTable().rows({ search: 'applied' }).nodes() : $('#tabela tbody tr');
    var lista = [];
    $(linhas).find('input[type="checkbox"][id^="seletor-"]').each(function () {
        if (master.checked && $(this).data('pago') === 'Sim') { this.checked = false; return; }
        this.checked = master.checked;
        if (master.checked) lista.push(this.id.replace('seletor-', ''));
    });
    gravarIds(lista);
}

function valorBaixar() {
    $('#total_contas').html('...');
    $('#qtd_contas_massa').text('...');
    $.ajax({
        url: 'paginas/' + pag + '/valor_baixar.php', method: 'POST', data: { ids: $('#ids').val() }, dataType: 'json',
        success: function (r) { $('#total_contas').html('R$ ' + r.total); $('#qtd_contas_massa').text(r.qtd); },
        error: function () { $('#total_contas').html('R$ 0,00'); $('#qtd_contas_massa').text('0'); }
    });
}

function abrirModalBaixaMassa() {
    $('#ids-baixar-massa').val($('#ids').val());
    $('#data-baixar-massa').val(new Date().toISOString().slice(0, 10));
    $('#forma-baixar-massa').prop('selectedIndex', 0);
    $('#banco-baixar-massa').prop('selectedIndex', 0);
    $('#operacao-baixar-massa').val('');
    $('#obs-baixar-massa').val('');
    $('#mensagem-baixar-massa').removeClass('text-danger text-success').text('');
    valorBaixar();
    $('#modalBaixarMassa').modal('show');
}

$(document).on('change', '#forma-baixar-massa', function () {
    var padrao = $(this).find('option:selected').data('banco');
    if (padrao) $('#banco-baixar-massa').val(String(padrao));
});

$(document).off('submit', '#form-baixar-massa').on('submit', '#form-baixar-massa', function (e) {
    e.preventDefault();
    e.stopImmediatePropagation();
    $('#mensagem-baixar-massa').removeClass('text-danger text-success').text('Processando...');
    $.ajax({
        url: 'paginas/' + pag + '/baixar_multiplas.php', type: 'POST', data: $(this).serialize(),
        success: function (m) {
            if (m.trim() == 'Baixado com Sucesso') {
                $('#mensagem-baixar-massa').addClass('text-success').text(m);
                setTimeout(function () { $('#btn-fechar-baixar-massa').click(); gravarIds([]); finRecarregar(); }, 600);
            } else {
                $('#mensagem-baixar-massa').addClass('text-danger').text(m);
            }
        },
        error: function () { $('#mensagem-baixar-massa').addClass('text-danger').text('Erro ao conectar com o servidor.'); }
    });
});
