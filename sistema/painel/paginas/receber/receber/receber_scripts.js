$(document).ready(function () {
    $(document).on('focus', '.input-zeravel', function () {
        if ($(this).val() == '0') {
            $(this).val('');
        }
    });

    $(document).on('blur', '.input-zeravel', function () {
        if ($(this).val().trim() == '') {
            $(this).val('0');
            totalizar(); 
        }
    });

    $('#modalBaixar').on('hidden.bs.modal', function () {
        limparModalBaixar();
    });

    function initDatePicker() {
        var start = moment(dataInicialPadrao);
        var end = moment(dataFinalPadrao);

        function cb(start, end) {
            $('#reportrange span').html(start.format('DD/MM/YYYY') + ' - ' + end.format('DD/MM/YYYY'));
            $('#dataInicial').val(start.format('YYYY-MM-DD'));
            $('#dataFinal').val(end.format('YYYY-MM-DD'));

            if (typeof buscar === "function") { buscar(); }
        }

        $('#reportrange').daterangepicker({
            startDate: start,
            endDate: end,
            opens: 'left',
            locale: {
                format: 'DD/MM/YYYY',
                applyLabel: "Aplicar",
                cancelLabel: "Cancelar",
                customRangeLabel: "Personalizado",
                daysOfWeek: ["Dom", "Seg", "Ter", "Qua", "Qui", "Sex", "Sáb"],
                monthNames: ["Jan", "Fev", "Mar", "Abr", "Mai", "Jun", "Jul", "Ago", "Set", "Out", "Nov", "Dez"]
            },
            ranges: {
                'Hoje': [moment(), moment()],
                'Ontem': [moment().subtract(1, 'days'), moment().subtract(1, 'days')],
                'Últimos 7 Dias': [moment().subtract(6, 'days'), moment()],
                'Este Mês': [moment().startOf('month'), moment().endOf('month')],
                'Mês Passado': [moment().subtract(1, 'month').startOf('month'), moment().subtract(1, 'month').endOf('month')]
            }
        }, cb);

        cb(start, end);
    }

    if (typeof $.fn.daterangepicker === 'function') {
        initDatePicker();
    } else {
        console.error("Erro: daterangepicker não carregado. Verifique os links CDN.");
    }

    function verificarDatasEExibirBanco() {
        var vencimento = $('#vencimento').val();
        var dataPgto = $('#data_pgto').val();
        if (dataPgto && vencimento && dataPgto === vencimento) {
            $('#div-banco').removeClass('d-none');
        } else {
            $('#div-banco').addClass('d-none');
        }
    }

    $('#vencimento, #data_pgto').on('change', verificarDatasEExibirBanco);
    $('#modalForm').on('shown.bs.modal', verificarDatasEExibirBanco);
    $('#modalForm').on('hidden.bs.modal', function () {
        $('#div-banco').addClass('d-none');
        limparCampos();
    });
});

function alteracaoManualData() {
    document.getElementById('select_periodo').value = "";
    buscar();
}

function definirPeriodo(valor) {
    if (valor === "") return; 

    const hoje = new Date();
    let dataIni = new Date();
    let dataFim = new Date();

    if (valor === 'hoje') {
        dataIni = hoje;
        dataFim = hoje;
    } else if (valor === 'mes') {
        dataIni = new Date(hoje.getFullYear(), hoje.getMonth(), 1);
        dataFim = new Date(hoje.getFullYear(), hoje.getMonth() + 1, 0);
    } else if (valor === 'mes_passado') {
        dataIni = new Date(hoje.getFullYear(), hoje.getMonth() - 1, 1);
        dataFim = new Date(hoje.getFullYear(), hoje.getMonth(), 0);
    } else if (valor === 'ano') {
        dataIni = new Date(hoje.getFullYear(), 0, 1);
        dataFim = new Date(hoje.getFullYear(), 11, 31);
    }

    const f = (d) => {
        const mes = ("0" + (d.getMonth() + 1)).slice(-2);
        const dia = ("0" + d.getDate()).slice(-2);
        return d.getFullYear() + "-" + mes + "-" + dia;
    };

    document.getElementById('dataInicial').value = f(dataIni);
    document.getElementById('dataFinal').value = f(dataFim);

    buscar();
}

function buscar() {
    var filtro = ""; 
    var dataInicial = $('#dataInicial').val();
    var dataFinal = $('#dataFinal').val();
    var tipo_data = $('#filtrar_por').val(); 
    var atacadista = $('#atacadista').val();
    var formaPGTO = $('#formaPGTO').val();
    var tipo_conta = $('#tipo_conta').val(); 

    listar(filtro, dataInicial, dataFinal, tipo_data, atacadista, formaPGTO, tipo_conta);
}

function listar(p1, p2, p3, p4, p5, p6, p7) {
    $.ajax({
        url: 'paginas/' + pag + "/listar.php",
        method: 'POST',
        data: { p1: p1, p2: p2, p3: p3, p4: p4, p5: p5, p6: p6, p7: p7 },
        dataType: "html",
        success: function (result) {
            $("#listar").html(result);
        }
    });
}

function mostrar(descricao, valor, cliente, vencimento, data_pgto, nome_pgto, frequencia, obs, arquivo, multa, juros, desconto, taxa, total, usu_lanc, usu_pgto, pago, arq) {
    if (data_pgto == "") data_pgto = 'Pendente';
    $('#titulo_dados').text(descricao);
    $('#valor_dados').text(valor);
    $('#cliente_dados').text(cliente);
    $('#vencimento_dados').text(vencimento);
    $('#data_pgto_dados').text(data_pgto);
    $('#nome_pgto_dados').text(nome_pgto);
    $('#frequencia_dados').text(frequencia);
    $('#obs_dados').text(obs);
    $('#multa_dados').text(multa);
    $('#juros_dados').text(juros);
    $('#desconto_dados').text(desconto);
    $('#taxa_dados').text(taxa);
    $('#total_dados').text(total);
    $('#usu_lanc_dados').text(usu_lanc);
    $('#usu_pgto_dados').text(usu_pgto);
    $('#pago_dados').text(pago);
    $('#target_dados').attr("src", "images/contas/" + arquivo);
    $('#target_link_dados').attr("href", "images/contas/" + arq);
    $('#modalDados').modal('show');
}

function limparCampos() {
    $('#id').val('');
    $('#descricao').val('');
    $('#valor').val('');
    $('#vencimento').val("<?= $data_atual ?>");
    $('#data_pgto').val('');
    $('#obs').val('');
    $('#arquivo').val('');
    $('#target').attr("src", "images/contas/sem-foto.png");
    $('#cliente').val('0').change();
    $('#forma_pgto').prop('selectedIndex', 0).change();
    $('#frequencia').prop('selectedIndex', 0).change();
    $('#banco').val('').change();
    $('#descricao_banco').val('').change();
    $('#ids').val('');
    $('#btn-deletar').hide();
    $('#btn-baixar-modal').hide();
    $('#btn-baixar').hide();
    $('#mensagem').text('');
    $('#titulo_inserir').text('Inserir Registro');
}

function selecionar(id) {
    var ids = $('#ids').val();
    if ($('#seletor-' + id).is(":checked") == true) {
        var novo_id = ids + id + '-';
        $('#ids').val(novo_id);
    } else {
        var retirar = ids.replace(id + '-', '');
        $('#ids').val(retirar);
    }

    var ids_final = $('#ids').val();
    if (ids_final == "") {
        $('#btn-deletar').hide();
        $('#btn-baixar').hide();
    } else {
        $('#btn-deletar').show();
        $('#btn-baixar').show();
    }
}

function deletarSel() {
    var ids = $('#ids').val();
    var id = ids.split("-");
    for (i = 0; i < id.length - 1; i++) {
        excluirMultiplos(id[i]);
    }
    setTimeout(() => { listar(); }, 1000);
    limparCampos();
}

function deletarSelBaixar() {
    var ids = $('#ids').val();
    var id = ids.split("-");
    for (i = 0; i < id.length - 1; i++) {
        var novo_id = id[i];
        $.ajax({
            url: 'paginas/' + pag + "/baixar_multiplas.php",
            method: 'POST',
            data: { novo_id },
            dataType: "html"
        });
    }
    setTimeout(() => {
        buscar();
        limparCampos();
    }, 1000);
}

function permissoes(id, nome) {
    $('#id_permissoes').val(id);
    $('#nome_permissoes').text(nome);
    $('#modalPermissoes').modal('show');
    listarPermissoes(id);
}

function parcelar(id, valor, nome) {
    $('#id-parcelar').val(id);
    $('#valor-parcelar').val(valor);
    $('#qtd-parcelar').val('');
    $('#nome-parcelar').text(nome);
    $('#nome-input-parcelar').val(nome);
    $('#modalParcelar').modal('show');
    $('#mensagem-parcelar').text('');
}

function mostrarResiduos(id) {
    $.ajax({
        url: 'paginas/' + pag + "/listar-residuos.php",
        method: 'POST',
        data: { id },
        dataType: "html",
        success: function (result) {
            $("#listar-residuos").html(result);
        }
    });
    $('#modalResiduos').modal('show');
}

function arquivo(id, nome) {
    $('#id-arquivo').val(id);
    $('#nome-arquivo').text(nome);
    $('#modalArquivos').modal('show');
    $('#mensagem-arquivo').text('');
    $('#arquivo_conta').val('');
    listarArquivos();
}

function cobrar(id) {
    $.ajax({
        url: 'paginas/' + pag + "/cobrar.php",
        method: 'POST',
        data: { id },
        dataType: "html",
        success: function (result) { alert(result); }
    });
}

function tipoData(tipo) {
    $('#tipo_data').val(tipo);
    buscar();
}

$(document).on('click', '#relatorio', function (e) {
    e.preventDefault();
    var url = 'rel/receber_class.php?dataInicial=' + $('#dataInicial').val() +
        '&dataFinal=' + $('#dataFinal').val() +
        '&tipo_data=' + $('#tipo_data').val() +
        '&atacadista=' + $('#atacadista').val() +
        '&formaPGTO=' + $('#formaPGTO').val();
    window.open(url, '_blank');
});

function excluir(id) {
    if (!confirm("Deseja realmente excluir este registro?")) return;
    $('#mensagem-excluir').text('Excluindo...');
    $.ajax({
        url: 'paginas/' + pag + "/excluir.php",
        method: 'POST',
        data: { id },
        success: function (mensagem) {
            if (mensagem.trim() == "Excluído com Sucesso") {
                buscar();
            } else {
                $('#mensagem-excluir').addClass('text-danger').text(mensagem);
            }
        }
    });
}

$(document).on('submit', '#form-baixar', function (e) {
    e.preventDefault();
    $('#mensagem-baixar').removeClass('text-danger text-success').text('Processando...');

    var formData = new FormData(this);
    $.ajax({
        url: 'paginas/' + pag + "/baixar.php",
        type: 'POST',
        data: formData,
        cache: false,
        contentType: false,
        processData: false,
        success: function (mensagem) {
            $('#mensagem-baixar').text('');
            if (mensagem.trim() == "Baixado com Sucesso") {
                $('#btn-fechar-baixar').click();
                buscar(); 
            } else {
                $('#mensagem-baixar').addClass('text-danger').html(mensagem);
            }
        },
        error: function () {
            $('#mensagem-baixar').addClass('text-danger').text('Erro ao conectar com o servidor.');
        }
    });
});

$("#form-parcelar").submit(function (e) {
    e.preventDefault();
    var formData = new FormData(this);
    $.ajax({
        url: 'paginas/' + pag + "/parcelar.php",
        type: 'POST',
        data: formData,
        cache: false,
        contentType: false,
        processData: false,
        success: function (mensagem) {
            if (mensagem.trim() == "Parcelado com Sucesso") {
                $('#btn-fechar-parcelar').click();
                buscar();
            } else {
                $('#mensagem-parcelar').addClass('text-danger').text(mensagem);
            }
        }
    });
});

function marcarTodos() {
    let checkbox = document.getElementById('input-todos');
    var usuario = $('#id_permissoes').val();
    if (checkbox.checked) { adicionarPermissoes(usuario); } 
    else { limparPermissoes(usuario); }
}

function carregarImg() {
    var target = document.getElementById('target');
    var file = document.querySelector("#arquivo").files[0];
    if (!file) return;

    var reader = new FileReader();
    var ext = file.name.split('.').pop().toLowerCase();
    var icones = {
        'pdf': 'pdf.png', 'rar': 'rar.png', 'zip': 'rar.png',
        'doc': 'word.png', 'docx': 'word.png', 'txt': 'word.png',
        'xlsx': 'excel.png', 'xlsm': 'excel.png', 'xls': 'excel.png', 'xml': 'xml.png'
    };

    if (icones[ext]) { $('#target').attr('src', "images/" + icones[ext]); } 
    else {
        reader.onloadend = function () { target.src = reader.result; };
        reader.readAsDataURL(file);
    }
}

$("#form-arquivos").submit(function (e) {
    e.preventDefault();
    var formData = new FormData(this);
    $.ajax({
        url: 'paginas/' + pag + "/arquivos.php",
        type: 'POST',
        data: formData,
        cache: false,
        contentType: false,
        processData: false,
        success: function (mensagem) {
            if (mensagem.trim() == "Inserido com Sucesso") {
                $('#nome-arq').val('');
                $('#arquivo_conta').val('');
                $('#target-arquivos').attr('src', 'images/arquivos/sem-foto.png');
                listarArquivos();
            } else {
                $('#mensagem-arquivo').addClass('text-danger').text(mensagem);
            }
        }
    });
});

function listarArquivos() {
    var id = $('#id-arquivo').val();
    $.ajax({
        url: 'paginas/' + pag + "/listar-arquivos.php",
        method: 'POST',
        data: { id },
        success: function (result) { $("#listar-arquivos").html(result); }
    });
}

function valorBaixar() {
    var ids = $('#ids').val();
    $.ajax({
        url: 'paginas/' + pag + "/valor_baixar.php",
        method: 'POST',
        data: { ids },
        success: function (result) { $("#total_contas").html(result); }
    });
}


// ==========================================
// CÁLCULOS E LAYOUT DO PAINEL
// ==========================================

function getFloatValue(elementId) {
    let element = document.getElementById(elementId);
    if (!element) return 0; 
    let val = element.value;
    if (val === undefined || val === null || val === "") return 0; 
    
    val = String(val).trim();
    if (val.indexOf(',') === -1) {
        return parseFloat(val) || 0;
    }
    
    let valorStr = val.replace(/\./g, '').replace(',', '.');
    return parseFloat(valorStr) || 0;
}

function totalizar() {
    let valorOriginal = getFloatValue('valor-original-baixar');
    let multa = getFloatValue('valor-multa');
    let juros = getFloatValue('valor-juros');
    let acrescimo = getFloatValue('valor-acrescimo');
    let desconto = getFloatValue('valor-desconto');

    let subtotalLiquido = (valorOriginal + multa + juros + acrescimo) - desconto;
    
    if (subtotalLiquido < 0) subtotalLiquido = 0;

    let subtotalInput = document.getElementById('subtotal');
    if (subtotalInput) {
        subtotalInput.value = subtotalLiquido.toLocaleString('pt-BR', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
    }
    
    totalizarPagamentos();
}

function totalizarPagamentos() {
    let totalRecebido = 0;
    const linhas = document.querySelectorAll("#linha-container-pagamento .linha-pagamento");

    linhas.forEach(linha => {
        const valorInput = linha.querySelector(".valor_pagamento");
        if (valorInput && valorInput.value) {
            let valorStr = String(valorInput.value).replace(/\./g, '').replace(',', '.');
            let valorNum = parseFloat(valorStr) || 0;
            totalRecebido += valorNum;
        }
    });

    let lblTotalRecebido = document.getElementById('lbl-total-recebido');
    if (lblTotalRecebido) {
        lblTotalRecebido.textContent = "R$ " + totalRecebido.toLocaleString('pt-BR', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
    }

    let subtotalInput = document.getElementById('subtotal');
    let subtotalStr = subtotalInput ? subtotalInput.value : "0";
    if (!subtotalStr) subtotalStr = "0";

    let subtotalLiquido = parseFloat(String(subtotalStr).replace(/\./g, '').replace(',', '.')) || 0;
    
    let statusLabel = document.getElementById('lbl-status-conta');
    if (!statusLabel) return;

    let diferenca = totalRecebido - subtotalLiquido;

    if (totalRecebido === 0) {
        statusLabel.textContent = "Aguardando...";
        statusLabel.className = "fs-4 fw-bold text-muted";
    } else if (diferenca < 0) {
        statusLabel.className = "fs-4 fw-bold text-danger";
        statusLabel.textContent = "Falta: R$ " + Math.abs(diferenca).toLocaleString('pt-BR', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
    } else if (diferenca > 0) {
        statusLabel.className = "fs-4 fw-bold text-primary";
        statusLabel.textContent = "Troco: R$ " + diferenca.toLocaleString('pt-BR', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
    } else {
        statusLabel.className = "fs-4 fw-bold text-success";
        statusLabel.textContent = "Valor Exato ✓";
    }
}

function mascaraMoedaInput(input) {
    if (!input.value) return;
    let valor = input.value.replace(/\D/g, ''); 
    if (valor === "") {
        input.value = "";
        return;
    }
    valor = (parseFloat(valor) / 100).toFixed(2); 
    valor = valor.replace('.', ','); 
    valor = valor.replace(/(\d)(?=(\d{3})+(?!\d))/g, '$1.'); 
    input.value = valor;
}

document.addEventListener('input', function (e) {
    if (e.target.classList.contains('input-zeravel') || e.target.classList.contains('valor_pagamento')) {
        mascaraMoedaInput(e.target);
        totalizarPagamentos();
    }
}, false);


// ==========================================
// FUNÇÕES DE ABERTURA DO MODAL (BAIXAR) E LINHAS
// ==========================================

document.addEventListener("DOMContentLoaded", () => {
    addNewPagamentoLine();
});

function addNewPagamentoLine() {
    const template = document.getElementById("linha-template-pagamento");
    const container = document.getElementById("linha-container-pagamento");
    if (!template || !container) return;

    const newLine = template.cloneNode(true);
    newLine.style.display = "flex"; 
    newLine.id = ""; 
    
    const inputsText = newLine.querySelectorAll('input[type="text"]');
    inputsText.forEach(input => input.value = "");
    
    const selects = newLine.querySelectorAll('select');
    selects.forEach(select => select.selectedIndex = 0);

    container.appendChild(newLine);
}

function handlePagamentoInput(input) {
    const linha = input.closest(".linha-pagamento");
    const container = document.getElementById("linha-container-pagamento");
    
    const valor = linha.querySelector(".valor_pagamento").value.trim();
    const data = linha.querySelector(".data_pagamento").value.trim();
    const forma = linha.querySelector(".forma_pagamento").value.trim();
    const banco = linha.querySelector(".banco_pagamento").value.trim(); 
    
    const isEssentialFilled = (valor !== "" && data !== "" && forma !== "" && banco !== "");

    if (isEssentialFilled && linha === container.lastElementChild) {
        addNewPagamentoLine();
    }
}

function limparModalBaixar() {
    $('#id-baixar').val('');
    $('#valor-original-baixar').val('');
    $('#valor-multa').val('0');
    $('#valor-juros').val('0');
    $('#valor-desconto').val('0');
    $('#valor-acrescimo').val('0'); 
    $('#subtotal').val('');
    $('#lbl-status-conta').text('-');
    $('#lbl-total-recebido').text('R$ 0,00');
    $('#mensagem-baixar').text('');
    $('#obs-baixar').val('');      

    $('#linha-container-pagamento').empty();
    addNewPagamentoLine();
}

function baixar(id, descricao, valor, vencimento, cliente, romaneio, forma_padrao, status_pagamento) {
    limparModalBaixar();

    $('#id-baixar').val(id);
    $('#descricao-baixar').text(descricao);
    $('#cliente-baixar').val(cliente);
    $('#romaneio-baixar').val(romaneio);
    
    let valorFloat = parseFloat(valor) || 0;
    let valorFormatadoBR = valorFloat.toLocaleString('pt-BR', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
    
    $('#valor-original-baixar').val(valorFormatadoBR);
    $('#vencimento-baixar').val(vencimento);

    if (status_pagamento === 'Pago' || status_pagamento === 'Sim' || status_pagamento === 'Parcial') {
        $('#mensagem-baixar').text('Carregando dados da baixa...');

        $.ajax({
            url: 'paginas/' + pag + "/buscar_baixa.php",
            method: 'POST',
            data: { id: id },
            dataType: "json", 
            success: function (dados) {
                $('#mensagem-baixar').text('');
                
                $('#linha-container-pagamento').empty();

                if (dados.pagamentos && dados.pagamentos.length > 0) {
                    dados.pagamentos.forEach((pgto) => {
                        addNewPagamentoLine();
                        let linhas = document.querySelectorAll("#linha-container-pagamento .linha-pagamento");
                        let ultimaLinha = linhas[linhas.length - 1]; 
                        
                        let vPGTO = parseFloat(pgto.valor) || 0;
                        ultimaLinha.querySelector(".valor_pagamento").value = vPGTO.toLocaleString('pt-BR', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
                        ultimaLinha.querySelector(".data_pagamento").value = pgto.data || '';
                        ultimaLinha.querySelector(".forma_pagamento").value = pgto.forma || '';
                        ultimaLinha.querySelector(".banco_pagamento").value = pgto.banco || '';
                        ultimaLinha.querySelector(".operacao_pagamento").value = pgto.operacao || '';
                    });
                    
                    // Adiciona sempre uma linha extra em branco para o usuário continuar baixando
                    addNewPagamentoLine(); 
                    
                } else {
                    addNewPagamentoLine();
                }

                $('#valor-multa').val(dados.multa);
                $('#valor-juros').val(dados.juros);
                $('#valor-acrescimo').val(dados.acrescimo);
                $('#valor-desconto').val(dados.desconto);
                $('#obs-baixar').val(dados.obs);

                totalizar(); 
                $('#form-baixar button[type="submit"]').text('Editar Baixa').removeClass('btn-success').addClass('btn-warning');
                $('#modalBaixar').modal('show');
            },
            error: function () {
                $('#mensagem-baixar').addClass('text-danger').text('Erro ao buscar os dados da baixa.');
            }
        });

    } else {
        let primeiraLinha = document.querySelector("#linha-container-pagamento .linha-pagamento");
        if (primeiraLinha) {
            primeiraLinha.querySelector(".valor_pagamento").value = ""; 
            
            let selectForma = primeiraLinha.querySelector(".forma_pagamento");
            if(selectForma) {
                selectForma.value = (forma_padrao != "" && forma_padrao != "0" && forma_padrao != null) ? forma_padrao : '1';
            }
        }

        $('#form-baixar button[type="submit"]').text('Confirmar Baixa').removeClass('btn-warning').addClass('btn-success');
        $('#modalBaixar').modal('show');

        setTimeout(function () {
            totalizar(); 
        }, 200);
    }
}