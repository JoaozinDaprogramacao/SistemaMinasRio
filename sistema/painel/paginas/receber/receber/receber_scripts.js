$(document).ready(function () {
    // Forçar a inicialização mesmo que o elemento demore a aparecer
    function initDatePicker() {
        var start = moment(dataInicialPadrao);
        var end = moment(dataFinalPadrao);

        function cb(start, end) {
            $('#reportrange span').html(start.format('DD/MM/YYYY') + ' - ' + end.format('DD/MM/YYYY'));
            $('#dataInicial').val(start.format('YYYY-MM-DD'));
            $('#dataFinal').val(end.format('YYYY-MM-DD'));

            // Só chama buscar se a função existir para não dar erro no console
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

    // Executa a inicialização
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
    });
});

// 1. Função chamada quando o utilizador mexe nos inputs de data manualmente
function alteracaoManualData() {
    // Muda o select para "" (Personalizado)
    document.getElementById('select_periodo').value = "";
    // Executa a busca com as novas datas
    buscar();
}

// 2. Função para definir períodos rápidos via Select
function definirPeriodo(valor) {
    if (valor === "") return; // Se for personalizado, não faz nada

    const hoje = new Date();
    let dataIni = new Date();
    let dataFim = new Date();

    // Lógica de cálculo de datas (JS Puro)
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

    // Formata data para o padrão do input date: YYYY-MM-DD
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
    var filtro = $('#tipo_data_filtro').val() || '';
    var dataInicial = $('#dataInicial').val();
    var dataFinal = $('#dataFinal').val();
    var tipo_data = $('#tipo_data').val();
    var atacadista = $('#atacadista').val();
    var formaPGTO = $('#formaPGTO').val();

    listar(filtro, dataInicial, dataFinal, tipo_data, atacadista, formaPGTO);
}

function listar(filtro, dataInicial, dataFinal, tipo_data, atacadista, formaPGTO) {
    $.ajax({
        url: 'paginas/' + pag + "/listar.php",
        method: 'POST',
        data: { filtro, dataInicial, dataFinal, tipo_data, atacadista, formaPGTO },
        dataType: "html",
        success: function (result) {
            $("#listar").html(result);
        }
    });
}

function tipoData(tipo) {
    $('#tipo_data').val(tipo);
    buscar();
}

$(document).on('click', '#relatorio', function (e) {
    e.preventDefault();
    var dataInicial = $('#dataInicial').val();
    var dataFinal = $('#dataFinal').val();
    var tipo_data = $('#tipo_data').val();
    var atacadista = $('#atacadista').val();
    var formaPGTO = $('#formaPGTO').val();

    var url = 'rel/receber_class.php?dataInicial=' + dataInicial +
        '&dataFinal=' + dataFinal +
        '&tipo_data=' + tipo_data +
        '&atacadista=' + atacadista +
        '&formaPGTO=' + formaPGTO;
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

$("#form-baixar").submit(function (e) {
    e.preventDefault();
    var formData = new FormData(this);
    $.ajax({
        url: 'paginas/' + pag + "/baixar.php",
        type: 'POST',
        data: formData,
        cache: false,
        contentType: false,
        processData: false,
        success: function (mensagem) {
            if (mensagem.trim() == "Baixado com Sucesso") {
                $('#btn-fechar-baixar').click();
                buscar();
            } else {
                $('#mensagem-baixar').addClass('text-danger').text(mensagem);
            }
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

function totalizar() {
    var valor = ($('#valor-baixar').val() || "0").replace(",", ".");
    var desconto = ($('#valor-desconto').val() || "0").replace(",", ".");
    var juros = ($('#valor-juros').val() || "0").replace(",", ".");
    var multa = ($('#valor-multa').val() || "0").replace(",", ".");
    var taxa = ($('#valor-taxa').val() || "0").replace(",", ".");

    var subtotal = parseFloat(valor) + parseFloat(juros) + parseFloat(taxa) + parseFloat(multa) - parseFloat(desconto);
    $('#subtotal').val(subtotal.toFixed(2));
}

function calcularTaxa() {
    var pgto = $('#saida-baixar').val();
    var valor = $('#valor-baixar').val();
    $.ajax({
        url: 'paginas/' + pag + "/calcular_taxa.php",
        method: 'POST',
        data: { valor, pgto },
        success: function (result) {
            $('#valor-taxa').val(result);
            totalizar();
        }
    });
}

function marcarTodos() {
    let checkbox = document.getElementById('input-todos');
    var usuario = $('#id_permissoes').val();
    if (checkbox.checked) {
        adicionarPermissoes(usuario);
    } else {
        limparPermissoes(usuario);
    }
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

    if (icones[ext]) {
        $('#target').attr('src', "images/" + icones[ext]);
    } else {
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
        success: function (result) {
            $("#listar-arquivos").html(result);
        }
    });
}

function valorBaixar() {
    var ids = $('#ids').val();
    $.ajax({
        url: 'paginas/' + pag + "/valor_baixar.php",
        method: 'POST',
        data: { ids },
        success: function (result) {
            $("#total_contas").html(result);
        }
    });
}