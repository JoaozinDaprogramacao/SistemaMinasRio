

$(document).ready(function () {

    // Função para verificar as datas e mostrar/esconder o campo do banco
    function verificarDatasEExibirBanco() {
        var vencimento = $('#vencimento').val();
        var dataPgto = $('#data_pgto').val();

        // A condição é: data de pagamento não pode ser vazia E precisa ser igual ao vencimento
        if (dataPgto && vencimento && dataPgto === vencimento) {
            $('#div-banco').removeClass('d-none'); // Mostra o campo do banco
        } else {
            $('#div-banco').addClass('d-none'); // Esconde o campo do banco
        }
    }

    // Adiciona um "escutador" para quando o valor dos campos de data mudar
    $('#vencimento, #data_pgto').on('change', function () {
        verificarDatasEExibirBanco();
    });

    // Também é uma boa prática executar a função quando o modal for aberto,
    // caso os dados já venham preenchidos de uma edição.
    $('#modalForm').on('shown.bs.modal', function () {
        verificarDatasEExibirBanco();
    });

    // E garantir que o campo do banco fique escondido ao fechar o modal
    $('#modalForm').on('hidden.bs.modal', function () {
        $('#div-banco').addClass('d-none');
    });

});




$(document).ready(function () {
    $('.sel2').select2({
        dropdownParent: $('#modalForm')
    });

});


function marcarTodos() {
    let checkbox = document.getElementById('input-todos');
    var usuario = $('#id_permissoes').val();

    if (checkbox.checked) {
        adicionarPermissoes(usuario);
    } else {
        limparPermissoes(usuario);
    }
}


function excluir(id) {
    $('#mensagem-excluir').text('Excluindo...')

    $.ajax({
        url: 'paginas/' + pag + "/excluir.php",
        method: 'POST',
        data: {
            id
        },
        dataType: "html",

        success: function (mensagem) {
            if (mensagem.trim() == "Excluído com Sucesso") {
                buscar();
                limparCampos();
            } else {
                $('#mensagem-excluir').addClass('text-danger')
                $('#mensagem-excluir').text(mensagem)
            }
        }
    });
}

document.getElementById('relatorio').addEventListener('click', function (event) {
    event.preventDefault(); // Impede o comportamento padrão do botão

    var filtro = $('#tipo_data_filtro').val();
    var dataInicial = $('#dataInicial').val();
    var dataFinal = $('#dataFinal').val();
    var tipo_data = $('#tipo_data').val();
    var atacadista = $('#atacadista').val();
    var formaPGTO = $('#formaPGTO').val();


    // Cria um novo objeto FormData
    var formData = new FormData();

    // Adiciona campos manualmente ao FormData
    formData.append('filtro', filtro);
    formData.append('dataInicial', dataInicial);
    formData.append('dataFinal', dataFinal);
    formData.append('tipo_data', tipo_data);
    formData.append('atacadista', atacadista);
    formData.append('formaPGTO', formaPGTO);


    // Envia o FormData via AJAX
    $.ajax({
        url: 'rel/receber_class.php", // URL do script que processará a requisição',
			type: 'POST',
        data: formData, // Envia o FormData
        processData: false, // Impede o jQuery de processar os dados
        contentType: false, // Impede o jQuery de definir o contentType
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



function carregarImg() {
    var target = document.getElementById('target');
    var file = document.querySelector("#arquivo").files[0];

    var arquivo = file['name'];
    resultado = arquivo.split(".", 2);

    if (resultado[1] === 'pdf') {
        $('#target').attr('src', "images/pdf.png");
        return;
    }

    if (resultado[1] === 'rar' || resultado[1] === 'zip') {
        $('#target').attr('src', "images/rar.png");
        return;
    }

    if (resultado[1] === 'doc' || resultado[1] === 'docx' || resultado[1] === 'txt') {
        $('#target').attr('src', "images/word.png");
        return;
    }


    if (resultado[1] === 'xlsx' || resultado[1] === 'xlsm' || resultado[1] === 'xls') {
        $('#target').attr('src', "images/excel.png");
        return;
    }


    if (resultado[1] === 'xml') {
        $('#target').attr('src', "images/xml.png");
        return;
    }



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


function buscar() {
    var filtro = $('#tipo_data_filtro').val();
    var dataInicial = $('#dataInicial').val();
    var dataFinal = $('#dataFinal').val();
    var tipo_data = $('#tipo_data').val();
    var atacadista = $('#atacadista').val();
    var formaPGTO = $('#formaPGTO').val();

    // Chama a função listar com todos os filtros
    listar(filtro, dataInicial, dataFinal, tipo_data, atacadista, formaPGTO);
}


function tipoData(tipo) {
    $('#tipo_data').val(tipo);
    buscar();
}


function totalizar() {
    valor = $('#valor-baixar').val();
    desconto = $('#valor-desconto').val();
    juros = $('#valor-juros').val();
    multa = $('#valor-multa').val();
    taxa = $('#valor-taxa').val();

    valor = valor.replace(",", ".");
    desconto = desconto.replace(",", ".");
    juros = juros.replace(",", ".");
    multa = multa.replace(",", ".");
    taxa = taxa.replace(",", ".");

    if (valor == "") {
        valor = 0;
    }

    if (desconto == "") {
        desconto = 0;
    }

    if (juros == "") {
        juros = 0;
    }

    if (multa == "") {
        multa = 0;
    }

    if (taxa == "") {
        taxa = 0;
    }

    subtotal = parseFloat(valor) + parseFloat(juros) + parseFloat(taxa) + parseFloat(multa) - parseFloat(desconto);


    console.log(subtotal)

    $('#subtotal').val(subtotal);

}

function calcularTaxa() {
    pgto = $('#saida-baixar').val();
    valor = $('#valor-baixar').val();
    $.ajax({
        url: 'paginas/' + pag + "/calcular_taxa.php",
        method: 'POST',
        data: {
            valor,
            pgto
        },
        dataType: "html",

        success: function (result) {
            $('#valor-taxa').val(result);
            totalizar();
        }
    });


}



$("#form-baixar").submit(function () {
    event.preventDefault();
    var formData = new FormData(this);

    $.ajax({
        url: 'paginas/' + pag + "/baixar.php",
        type: 'POST',
        data: formData,

        success: function (mensagem) {
            $('#mensagem-baixar').text('');
            $('#mensagem-baixar').removeClass()
            if (mensagem.trim() == "Baixado com Sucesso") {
                $('#btn-fechar-baixar').click();
                buscar();
            } else {
                $('#mensagem-baixar').addClass('text-danger')
                $('#mensagem-baixar').text(mensagem)
            }

        },

        cache: false,
        contentType: false,
        processData: false,

    });

});



$("#form-parcelar").submit(function () {
    event.preventDefault();
    var formData = new FormData(this);

    $.ajax({
        url: 'paginas/' + pag + "/parcelar.php",
        type: 'POST',
        data: formData,

        success: function (mensagem) {
            $('#mensagem-parcelar').text('');
            $('#mensagem-parcelar').removeClass()
            if (mensagem.trim() == "Parcelado com Sucesso") {
                $('#btn-fechar-parcelar').click();
                buscar();
            } else {
                $('#mensagem-parcelar').addClass('text-danger')
                $('#mensagem-parcelar').text(mensagem)
            }

        },

        cache: false,
        contentType: false,
        processData: false,

    });

});


function valorBaixar() {
    var ids = $('#ids').val();

    $.ajax({
        url: 'paginas/' + pag + "/valor_baixar.php",
        method: 'POST',
        data: {
            ids
        },
        dataType: "html",

        success: function (result) {
            $("#total_contas").html(result);

        }
    });
}



$("#form-arquivos").submit(function () {
    event.preventDefault();
    var formData = new FormData(this);

    $.ajax({
        url: 'paginas/' + pag + "/arquivos.php",
        type: 'POST',
        data: formData,

        success: function (mensagem) {
            $('#mensagem-arquivo').text('');
            $('#mensagem-arquivo').removeClass()
            if (mensagem.trim() == "Inserido com Sucesso") {
                //$('#btn-fechar-arquivos').click();
                $('#nome-arq').val('');
                $('#arquivo_conta').val('');
                $('#target-arquivos').attr('src', 'images/arquivos/sem-foto.png');
                listarArquivos();
            } else {
                $('#mensagem-arquivo').addClass('text-danger')
                $('#mensagem-arquivo').text(mensagem)
            }

        },

        cache: false,
        contentType: false,
        processData: false,

    });

});

function listarArquivos() {
    var id = $('#id-arquivo').val();
    $.ajax({
        url: 'paginas/' + pag + "/listar-arquivos.php",
        method: 'POST',
        data: {
            id
        },
        dataType: "text",

        success: function (result) {
            $("#listar-arquivos").html(result);
        }
    });
}




function carregarImgArquivos() {
    var target = document.getElementById('target-arquivos');
    var file = document.querySelector("#arquivo_conta").files[0];

    var arquivo = file['name'];
    resultado = arquivo.split(".", 2);

    if (resultado[1] === 'pdf') {
        $('#target-arquivos').attr('src', "images/pdf.png");
        return;
    }

    if (resultado[1] === 'rar' || resultado[1] === 'zip') {
        $('#target-arquivos').attr('src', "images/rar.png");
        return;
    }

    if (resultado[1] === 'doc' || resultado[1] === 'docx' || resultado[1] === 'txt') {
        $('#target-arquivos').attr('src', "images/word.png");
        return;
    }


    if (resultado[1] === 'xlsx' || resultado[1] === 'xlsm' || resultado[1] === 'xls') {
        $('#target-arquivos').attr('src', "images/excel.png");
        return;
    }


    if (resultado[1] === 'xml') {
        $('#target-arquivos').attr('src', "images/xml.png");
        return;
    }



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

$(function () {
    // Definir datas iniciais baseadas no seu PHP (opcional)
    var start = moment().subtract(29, 'days');
    var end = moment();

    function cb(start, end, label) {
        // Atualiza o texto exibido no botão
        $('#reportrange span').html(start.format('DD/MM/YYYY') + ' - ' + end.format('DD/MM/YYYY'));

        // Atualiza os inputs ocultos para o seu formulário/busca
        $('#dataInicial').val(start.format('YYYY-MM-DD'));
        $('#dataFinal').val(end.format('YYYY-MM-DD'));

        // Se você quiser disparar a busca automaticamente ao selecionar
        if (label) {
            buscar();
        }
    }

    $('#reportrange').daterangepicker({
        startDate: start,
        endDate: end,
        opens: 'left', // Abre para a esquerda ou direita
        locale: {
            format: 'DD/MM/YYYY',
            applyLabel: "Aplicar",
            cancelLabel: "Cancelar",
            fromLabel: "De",
            toLabel: "Até",
            customRangeLabel: "Personalizado", // Nome para o intervalo manual
            daysOfWeek: ["Dom", "Seg", "Ter", "Qua", "Qui", "Sex", "Sáb"],
            monthNames: ["Janeiro", "Fevereiro", "Março", "Abril", "Maio", "Junho", "Julho", "Agosto", "Setembro", "Outubro", "Novembro", "Dezembro"]
        },
        ranges: {
            'Hoje': [moment(), moment()],
            'Ontem': [moment().subtract(1, 'days'), moment().subtract(1, 'days')],
            'Últimos 7 Dias': [moment().subtract(6, 'days'), moment()],
            'Últimos 30 Dias': [moment().subtract(29, 'days'), moment()],
            'Este Mês': [moment().startOf('month'), moment().endOf('month')],
            'Mês Passado': [moment().subtract(1, 'month').startOf('month'), moment().subtract(1, 'month').endOf('month')],
            'Este Ano': [moment().startOf('year'), moment().endOf('year')]
        }
    }, cb);

    cb(start, end);
});