


var pag = "<?= $pag ?>"



$(document).ready(function () {
    $('.select2').select2({
        placeholder: "Selecione os romaneios",
        allowClear: true,
        width: 'resolve'
    });
});



let romaneiosSelecionados = [];

function carregarImg() {
    var target = document.getElementById('target');
    var file = document.querySelector("#foto").files[0];

    var reader = new FileReader();

    reader.onloadend = function () {
        target.src = reader.result;
    };

    if (file) {
        reader.readAsDataURL(file);

    } else {
        target.src = "";
    }
}




function buscarCat(id) {
    $('#cat').val(id);
    listar(id)
}



$("#form-saida").submit(function () {

    event.preventDefault();
    var formData = new FormData(this);

    $.ajax({
        url: 'paginas/' + pag + "/saida.php",
        type: 'POST',
        data: formData,

        success: function (mensagem) {
            $('#mensagem-saida').text('');
            $('#mensagem-saida').removeClass()
            if (mensagem.trim() == "Salvo com Sucesso") {

                $('#btn-fechar-saida').click();
                listar();

            } else {

                $('#mensagem-saida').addClass('text-danger')
                $('#mensagem-saida').text(mensagem)
            }


        },

        cache: false,
        contentType: false,
        processData: false,

    });

});

// PROTEÇÃO: Só executa se o elemento existir na página
var elementRel = document.getElementById('relatorio');

if (elementRel) {
    elementRel.addEventListener('submit', function (event) {
        event.preventDefault();

        var dataInicial = $('#dataInicial').val();
        var dataFinal = $('#dataFinal').val();
        var cliente = $('#cliente').val();

        var formData = new FormData();
        formData.append('dataInicial', dataInicial);
        formData.append('dataFinal', dataFinal);
        formData.append('cliente', cliente);

        $.ajax({
            url: 'rel/romaneio_venda_class.php',
            type: 'POST',
            data: formData,
            processData: false,
            contentType: false,
            success: function (mensagem) {
                $('#mensagem-baixar').text('');
                $('#mensagem-baixar').removeClass();
                if (mensagem.trim() == "Baixado com Sucesso") {
                    $('#btn-fechar-baixar').click();
                    buscar();
                } else {
                    $('#mensagem-baixar').addClass('text-danger');
                    $('#mensagem-baixar').text(mensagem);
                }
            },
            error: function (xhr, status, error) {
                console.error('Erro na requisição AJAX:', error);
            }
        });
    });
}





function buscar() {
    var dataInicial = $('#dataInicial').val();
    var dataFinal = $('#dataFinal').val();
    var atacadista = $('#cliente').val();


    listar(dataInicial, dataFinal, atacadista);
}
$("#form-entrada").submit(function () {

    event.preventDefault();
    var formData = new FormData(this);

    $.ajax({
        url: 'paginas/' + pag + "/entrada.php",
        type: 'POST',
        data: formData,

        success: function (mensagem) {
            $('#mensagem-entrada').text('');
            $('#mensagem-entrada').removeClass()
            if (mensagem.trim() == "Salvo com Sucesso") {

                $('#btn-fechar-entrada').click();
                listar();

            } else {

                $('#mensagem-entrada').addClass('text-danger')
                $('#mensagem-entrada').text(mensagem)
            }


        },

        cache: false,
        contentType: false,
        processData: false,

    });

});




// Adicione antes do submit do form
$('#dataInicialRel').val($('#dataInicial').val());
$('#dataFinalRel').val($('#dataFinal').val());
$('#clienteRel').val($('#cliente').val());



function buscarDadosCliente(id) {
    // SE estivermos carregando os dados da edição, NÃO busca o padrão do cliente
    if (carregando_dados) {
        return;
    }

    $.ajax({
        url: 'paginas/romaneio_venda/buscar_cliente.php',
        type: 'POST',
        data: {
            id: id
        },
        dataType: 'json',
        success: function (dados) {
            if (dados && !dados.error) {
                const planoId = parseInt(dados.plano_pagamento);
                const prazoDias = parseInt(dados.prazo_pagamento);

                // Só preenche se tiver retornado algo válido
                if (planoId > 0) {
                    $('#plano_pgto').val(planoId).trigger('change'); // Use trigger change se usar select2
                }

                document.getElementById('quant_dias').value = prazoDias;

                calcularVencimento();
                calculaTotais();
            }
        },
        error: function (xhr, status, error) {
            // Tratamento silencioso do erro
        }
    });
}



function calcularVencimento() {
    const diasInput = document.getElementById('quant_dias');
    const dataInput = document.querySelector('input[name="data"]');
    const vencimentoInput = document.querySelector('input[name="vencimento"]');

    if (diasInput && dataInput && dataInput.value) {
        const dias = parseInt(diasInput.value) || 0;
        const dataBase = new Date(dataInput.value + 'T00:00:00'); // Adicionado T00:00:00 para evitar problemas de fuso

        if (!isNaN(dias) && dataBase instanceof Date && !isNaN(dataBase.getTime())) {
            const dataVencimento = new Date(dataBase);
            dataVencimento.setDate(dataVencimento.getDate() + dias);

            // Formatação para YYYY-MM-DD
            const yyyy = dataVencimento.getFullYear();
            const mm = String(dataVencimento.getMonth() + 1).padStart(2, '0');
            const dd = String(dataVencimento.getDate()).padStart(2, '0');

            vencimentoInput.value = `${yyyy}-${mm}-${dd}`;
        }
    }
}

// Adiciona um observador para monitorar mudanças no campo de dias
document.addEventListener('DOMContentLoaded', function () {
    const diasInput = document.getElementById('quant_dias');
    if (diasInput) {
        // Observa mudanças no valor do campo
        const observer = new MutationObserver(function (mutations) {
            calcularVencimento();
        });

        observer.observe(diasInput, {
            attributes: true,
            attributeFilter: ['value']
        });
    }
});



function toggleRomaneio(element, id) {
    const index = romaneiosSelecionados.indexOf(id);

    if (index === -1) {
        // Adiciona seleção
        romaneiosSelecionados.push(id);
        element.classList.add('selecionado');
    } else {
        // Remove seleção
        romaneiosSelecionados.splice(index, 1);
        element.classList.remove('selecionado');
    }

    console.log(romaneiosSelecionados);

    // Atualiza input hidden
    document.getElementById('romaneios_selecionados').value = romaneiosSelecionados.join(',');

    // Exibe os romaneios selecionados
    console.log('Romaneios selecionados:');
    romaneiosSelecionados.forEach(romaneio => {
        console.log(`Romaneio #${romaneio}`);
    });

    // Carrega dados dos romaneios selecionados
    carregarDadosRomaneios();
}

function carregarDadosRomaneios() {
    if (romaneiosSelecionados.length === 0) {
        console.log('▶ [Romaneio] Nenhum romaneio selecionado – limpando lista.');
        $('#linha-container_1').empty();
        calculaTotais();
        return;
    }

    console.log('▶ [Romaneio] IDs selecionados:', romaneiosSelecionados);

    $.ajax({
        url: 'paginas/romaneio_venda/buscar_produtos_romaneio.php',
        method: 'POST',
        data: {
            ids: romaneiosSelecionados
        },
        dataType: 'json',

        beforeSend: function (jqXHR, settings) {
            console.groupCollapsed('⏳ [Romaneio] Iniciando requisição AJAX');
            console.log('URL:         ', settings.url);
            console.log('Método:      ', settings.type);
            console.log('Payload:     ', settings.data);
            console.groupEnd();
        },

        success: function (response, textStatus, jqXHR) {
            console.groupCollapsed('✅ [Romaneio] Resposta AJAX recebida');
            console.log('HTTP Status:  ', jqXHR.status, jqXHR.statusText);
            console.log('textStatus:   ', textStatus);
            console.log('Resposta bruta:', response);

            // Se o servidor enviou o wrapper {debug, data}
            var dados = response.data || response;
            if (response.debug) {
                console.group('🛠 [Romaneio] Debug do servidor');
                console.log('IDs recebidos (server):', response.debug.ids_recebidos);
                console.log('Placeholders SQL:      ', response.debug.placeholders);
                console.log('SQL completo:          ', response.debug.sql);
                console.log('Bind values:           ', response.debug.bind_values);
                console.log('Tempo exec (s):        ', response.debug.duration_sec);
                console.log('Linhas retornadas:     ', response.debug.row_count);
                console.groupEnd();
            }

            console.group('📦 [Romaneio] Produtos retornados');
            console.log('Total de produtos:', dados.length);
            console.table(dados);
            console.groupEnd();
            console.groupEnd();

            $('#linha-container_1').empty();

            if (!dados || dados.length === 0) {
                console.warn('⚠️ [Romaneio] Nenhum dado retornado');
                return;
            }

            dados.forEach(function (produto, idx) {
                console.log(`[Romaneio] Preenchendo linha #${idx}`, produto);
                let novaLinha = $('#linha-template_1').clone();
                novaLinha.removeAttr('id').show();

                novaLinha.find('.quant_caixa_1').val(produto.quant);
                novaLinha.find('.produto_1').val(produto.variedade);
                novaLinha.find('.preco_kg_1').val(produto.preco_kg);
                novaLinha.find('.tipo_cx_1').val(produto.tipo_caixa);
                novaLinha.find('.preco_unit_1').val(produto.preco_unit);
                novaLinha.find('.valor_1').val(produto.valor);

                $('#linha-container_1').append(novaLinha);
            });

            calculaTotais();
        },

        error: function (jqXHR, textStatus, errorThrown) {
            console.group('❌ [Romaneio] Erro na requisição AJAX');
            console.error('textStatus:  ', textStatus);
            console.error('HTTP Status: ', jqXHR.status, jqXHR.statusText);
            console.error('errorThrown: ', errorThrown);
            console.error('responseText:', jqXHR.responseText);
            console.groupEnd();
        }
    });
}

/**
 * Requisita a lista de Romaneios de Compra filtrada pelo Cliente e ordenada por ID (DESC).
 * Esta função é chamada no evento 'change' do select de cliente.
 * * @param {string} clienteId O ID do cliente selecionado.
*/
/**
 * Agora aceita idVendaAtual e um callback (função para rodar depois que carregar)
 */
function atualizarListaRomaneiosCompra(clienteId, idCompraSalva = null, callback = null) {
    const listaContainer = $('#lista-romaneios-compra');

    if (!clienteId || clienteId == '0') {
        listaContainer.html('<p class="text-secondary text-center">Selecione um Cliente...</p>');
        romaneiosSelecionados = [];
        $('#romaneios_selecionados').val('');
        if (callback) callback();
        return;
    }

    listaContainer.html('<p class="text-info text-center">Carregando romaneios...</p>');

    $.ajax({
        url: 'paginas/romaneio_venda/listar_romaneios_compra.php',
        type: 'POST',
        data: {
            cliente_id: clienteId,
            id_compra_salva: idCompraSalva // <--- Enviamos o ID da COMPRA, não da venda
        },
        success: function (htmlLista) {
            listaContainer.html(htmlLista);
            if (callback) callback();
        }
    });
}
// Inicializa a lista de romaneios de compra quando o modal é aberto (útil para edição)
$(document).ready(function () {
    $('#modalForm').on('show.bs.modal', function (e) {
        // Tenta obter o cliente já selecionado (se estiver em modo edição)
        const clienteId = $('#cliente_modal').val();
        if (clienteId && clienteId != '0') {
            // Se houver um ID, carrega a lista filtrada
            atualizarListaRomaneiosCompra(clienteId);
        } else {
            // Garante que o placeholder inicial seja exibido
            $('#lista-romaneios-compra').html('<p class="text-secondary text-center">Selecione um Cliente para carregar os Romaneios de Compra relacionados.</p>');
        }
    });
});


function limparCampos() {
    // 1. ESCONDE AS MENSAGENS DE FEEDBACK
    $('#mensagem-sucesso').hide();
    $('#mensagem-erro').hide();
    $('#mensagem').text(''); // Limpa texto de erro genérico se houver

    // 2. LIMPA O ID (CRUCIAL PARA NÃO EDITAR O ANTERIOR SEM QUERER)
    $('#id').val('');

    // 3. DESABILITA EVENTOS (Para evitar duplicidade ao reatribuir)
    $('#plano_pgto, #cliente_modal, .produto_1, .tipo_cx_1, .desc_2, .material').off('change');

    // 4. LIMPA OS CAMPOS DO FORMULÁRIO PRINCIPAL
    $('.data_atual').val(new Date().toISOString().split('T')[0]);
    $('#cliente_modal').val('0').trigger('change');
    $('#plano_pgto').val('0').trigger('change');
    $('#nota_fiscal').val('');
    $('#quant_dias').val('');
    $('#vencimento').val(new Date().toISOString().split('T')[0]);
    $('#desc-avista').val('');

    // Limpa checkboxes e campos de adicional/desconto
    $('#adicional_ativo, #desconto_ativo').prop('checked', false);
    $('#descricao_adicional').val('');
    $('#valor_adicional').val('0,00');
    $('#descricao_desconto').val('');
    $('#valor_desconto').val('0,00');

    // Chama as funções que escondem/mostram os inputs de adicional/desconto
    if (typeof adicionalAtivado === 'function') adicionalAtivado();
    if (typeof descontoAtivado === 'function') descontoAtivado();

    // 5. LIMPA ROMANEIOS DE COMPRA
    romaneiosSelecionados = [];
    $('#romaneios_selecionados').val('');

    // 6. LIMPA TODOS OS CONTÊINERES DE LINHAS DINÂMICAS
    $('#linha-container_1').empty();
    $('#linha-container_2').empty();
    $('#linha-container_3').empty();

    // 7. RESETA OS TOTAIS VISUAIS PARA ZERO
    $('#total_caixa').text('0 CXS');
    $('#total_kg').text('0 KG');
    $('#total_bruto').text('R$ 0,00');
    $('#total-desc').text('R$ 0,00');
    $('#total-geral').text('0,00');
    $('#total_comissao').text('0,00');
    $('#total_materiais').text('0,00');
    $('#total_carga').text('0,00');
    $('#total_liquido').text('0,00');
    $('#valor_liquido').val('0,00');

    // 8. ADICIONA AS PRIMEIRAS LINHAS VAZIAS NOVAMENTE
    addNewLine1();
    addNewLine2();
    addNewLine3();

    // 9. RESETAR O ESTADO DO BOTÃO (RESOLVE SEU PROBLEMA)
    $('#btn_salvar').text('Salvar');
    $('#btn_salvar').prop('disabled', false);

    // Resetar variável de controle global se você usar
    carregando_dados = false;

    // 10. REATIVA OS EVENTOS E ATUALIZA OS CÁLCULOS
    setTimeout(function () {
        $('#cliente_modal').on('change', function () {
            buscarDadosCliente($(this).val());
            atualizarListaRomaneiosCompra($(this).val());
        });
        $('#plano_pgto').on('change', calculaTotais);
        $(document).on('change', '.produto_1, .tipo_cx_1, .desc_2, .material', calculaTotais);

        calculaTotais();
    }, 100);
}

// Adicione antes do submit do form-romaneio
function verificarPlanoAVista() {
    var planoSelecionado = $('#plano_pgto option:selected').text().trim().toUpperCase();
    var valorDesconto = $('#desc-avista').val();

    // Verifica ambas as formas de acentuação
    if (planoSelecionado === 'À VISTA' || planoSelecionado === 'Á VISTA') {
        if (!valorDesconto || valorDesconto === '0' || valorDesconto === '0,00') {
            $('#desc-avista').addClass('is-invalid');
            return false;
        }
    }

    $('#desc-avista').removeClass('is-invalid');
    return true;
}

// Evento change do plano de pagamento
$('#plano_pgto').change(function () {
    verificarPlanoAVista();
});

// Remove qualquer binding anterior para não duplicar e usa delegação no document
$(document).off('submit', '#form-romaneio').on('submit', '#form-romaneio', function (e) {
    // 1. Bloqueio total do comportamento padrão (recarregar página)
    e.preventDefault();
    e.stopPropagation();

    console.log("▶ Iniciando submissão oficial do formulário...");

    // 2. Validação do Plano
    if (typeof verificarPlanoAVista === "function" && !verificarPlanoAVista()) {
        $('#mensagem-erro').html('<ul style="margin: 0; padding-left: 20px;"><li>Para pagamento à vista, o desconto é obrigatório</li></ul>').show();
        return false;
    }

    // 3. Preparação dos dados
    var formData = new FormData(this);

    // 4. Interface: Desabilita o botão para evitar cliques duplos
    var btnSalvar = $('#btn_salvar');
    btnSalvar.prop('disabled', true).text('Salvando...');

    $.ajax({
        url: 'paginas/romaneio_venda/salvar.php',
        type: 'POST',
        data: formData,
        contentType: false,
        processData: false,
        success: function (response) {
            console.log("Resposta do Servidor:", response);
            try {
                const data = typeof response === 'string' ? JSON.parse(response) : response;

                if (data.status === 'sucesso') {
                    // Feedback de sucesso
                    $('#mensagem-sucesso').html(data.mensagem).show();
                    $('#mensagem-erro').hide();

                    // Ações de fechamento
                    setTimeout(function () {
                        $('#modalForm').modal('hide');
                        limparCampos();
                        if (typeof buscar === "function") buscar();
                    }, 1000);
                } else {
                    // Exibe erro retornado pelo PHP
                    $('#mensagem-erro').html(data.mensagem).show();
                    btnSalvar.prop('disabled', false).text('Salvar');
                }
            } catch (err) {
                console.error("Erro ao processar JSON:", err);
                $('#mensagem-erro').html("Erro interno na resposta do servidor.").show();
                btnSalvar.prop('disabled', false).text('Salvar');
            }
        },
        error: function (xhr, status, error) {
            console.error("Erro Crítico no AJAX:", error);
            $('#mensagem-erro').html("Erro de conexão com o servidor.").show();
            btnSalvar.prop('disabled', false).text('Salvar');
        }
    });

    return false; // Terceira trava contra recarregamento
});

// Remove a classe de erro quando o campo é alterado
$('.form-control, .form-select').change(function () {
    $(this).removeClass('is-invalid');
    if ($('.is-invalid').length === 0) {
        $('#mensagem-erro').text('');
    }
});