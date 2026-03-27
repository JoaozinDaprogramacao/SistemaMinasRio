

function aplicarMascaras() {
    if ($.fn.mask) {
        $('#agencia').mask('9999-9', {
            reverse: true,
            placeholder: "0000-0"
        });

        $('#conta').mask('99999999-9', {
            reverse: true,
            placeholder: "00000000-0"
        });
    }
}

function iniciarMascaraMoeda() {
    const $saldo = $('#saldo');

    $saldo.off('.moeda');

    $saldo.on('input.moeda', function () {
        let valor = $(this).val().replace(/\D/g, '');

        if (!valor) {
            $(this).val('0,00');
            return;
        }

        valor = (parseInt(valor, 10) / 100).toFixed(2) + '';
        valor = valor.replace('.', ',');
        valor = valor.replace(/\B(?=(\d{3})+(?!\d))/g, '.');

        $(this).val(valor);
    });

    $saldo.on('focus.moeda', function () {
        if ($(this).val().trim() === '') {
            $(this).val('0,00');
        }
    });

    $saldo.on('blur.moeda', function () {
        if ($(this).val().trim() === '') {
            $(this).val('0,00');
        }
    });
}

function limparCampos() {
    $('#id').val('');
    $('#correntista').val('');
    $('#banco').val('');
    $('#agencia').val('');
    $('#conta').val('');
    $('#saldo').val('0,00');
    $('#ids').val('');
    $('#btn-deletar').hide();
    $('#mensagem').text('');

    aplicarMascaras();
    iniciarMascaraMoeda();
}

function inserir() {
    limparCampos();
    $('#titulo_inserir').text('Inserir Registro');
    $('#modalForm').modal('show');
}

function editar(id) {
    limparCampos();
    $('#titulo_inserir').text('Editar Registro');
    $('#id').val(id);

    $.ajax({
        url: 'paginas/' + pag + '/listar.php',
        type: 'POST',
        dataType: 'json',
        data: {
            id: id,
            action: 'buscar'
        },
        success: function (res) {
            if (res) {
                $('#correntista').val(res.correntista);
                $('#banco').val(res.banco);
                $('#agencia').val(res.agencia);
                $('#conta').val(res.conta);
                $('#saldo').val(res.saldo || '0,00');

                aplicarMascaras();
                iniciarMascaraMoeda();

                $('#modalForm').modal('show');
            }
        },
        error: function (err) {
            console.error("Erro ao buscar dados: ", err.responseText);
        }
    });
}

var carregando = false; // Variável de controle global no script

function excluir(id) {
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

function buscar() {
    var filtro = $('#tipo_data_filtro').val();
    var dataInicial = $('#dataInicial').val();
    var dataFinal = $('#dataFinal').val();
    var tipo_data = $('#tipo_data').val();

    if (typeof listar === "function") {
        listar(filtro, dataInicial, dataFinal, tipo_data);
    }
}

function tipoData(tipo) {
    $('#tipo_data').val(tipo);
    buscar();
}

function validarForm() {
    if ($("#correntista").val().trim() === "") {
        alert("Preencha o Correntista!");
        return false;
    }
    if ($("#banco").val().trim() === "") {
        alert("Preencha o Banco!");
        return false;
    }
    return true;
}

function totalizar() {
    let valor = $('#valor-baixar').val().replace(",", ".");
    let desconto = $('#valor-desconto').val().replace(",", ".");
    let juros = $('#valor-juros').val().replace(",", ".");
    let multa = $('#valor-multa').val().replace(",", ".");
    let taxa = $('#valor-taxa').val().replace(",", ".");

    valor = valor == "" ? 0 : parseFloat(valor);
    desconto = desconto == "" ? 0 : parseFloat(desconto);
    juros = juros == "" ? 0 : parseFloat(juros);
    multa = multa == "" ? 0 : parseFloat(multa);
    taxa = taxa == "" ? 0 : parseFloat(taxa);

    let subtotal = valor + juros + taxa + multa - desconto;
    $('#subtotal').val(subtotal);
}

function calcularTaxa() {
    let pgto = $('#saida-baixar').val();
    let valor = $('#valor-baixar').val();
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

function carregarImg(input, targetId) {
    var target = document.getElementById(targetId);
    var file = input.files[0];
    if (!file) {
        target.src = "";
        return;
    }

    var reader = new FileReader();
    reader.onloadend = function () {
        target.src = reader.result;
    };
    reader.readAsDataURL(file);
}

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