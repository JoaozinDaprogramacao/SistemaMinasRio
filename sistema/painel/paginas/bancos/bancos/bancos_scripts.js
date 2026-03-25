var pag = "<?= $pag ?>"


$(document).ready(function () {
    $('.sel2').select2({
        dropdownParent: $('#modalForm')
    });

    // Máscara para dinheiro
    $('#saldo').maskMoney({
        prefix: 'R$ ',
        allowNegative: false,
        thousands: '.',
        decimal: ',',
        affixesStay: true
    });

    // Máscara para agência
    $('#agencia').mask('9999-9', {
        reverse: true,
        placeholder: "0000-0"
    });

    // Máscara para conta
    $('#conta').mask('99999999-9', {
        reverse: true,
        placeholder: "00000000-0"
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
    listar(filtro, dataInicial, dataFinal, tipo_data)

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



$(document).ready(function () {
    // Máscaras
    $('#saldo').maskMoney({
        prefix: 'R$ ',
        allowNegative: false,
        thousands: '.',
        decimal: ',',
        affixesStay: true
    });
    $('#agencia').mask('9999-9', {
        reverse: true,
        placeholder: "0000-0"
    });
    $('#conta').mask('99999999-9', {
        reverse: true,
        placeholder: "00000000-0"
    });

    // Inicialização do Listar (se houver essa função no ajax.js)
    if (typeof listar === "function") {
        listar();
    }
});

function inserir() {
    limparCampos();
    $('#titulo_inserir').text('Inserir Registro');
    $('#modalForm').modal('show');
}

function editar(id, correntista, banco, agencia, conta, saldo) {
    limparCampos();
    $('#titulo_inserir').text('Editar Registro');

    $('#id').val(id);
    $('#correntista').val(correntista);
    $('#banco').val(banco);
    $('#agencia').val(agencia);
    $('#conta').val(conta);

    // IMPORTANTE: Preenche o valor e avisa o maskMoney para formatar
    $('#saldo').val(saldo);

    $('#modalForm').modal('show');

    // Delayzinho para garantir que o modal abriu antes de formatar
    setTimeout(function () {
        aplicarMascaras();
    }, 300);
}

function aplicarMascaras() {
    $('#saldo').maskMoney({
        prefix: 'R$ ',
        allowNegative: false,
        thousands: '.',
        decimal: ',',
        affixesStay: true
    });

    $('#agencia').mask('9999-9', {
        reverse: true,
        placeholder: "0000-0"
    });
    $('#conta').mask('99999999-9', {
        reverse: true,
        placeholder: "00000000-0"
    });

    // Força a formatação do valor que já estiver no campo
    $('#saldo').maskMoney('mask');
}

$(document).ready(function () {
    aplicarMascaras();

    $('.sel2').select2({
        dropdownParent: $('#modalForm')
    });

    if (typeof listar === "function") {
        listar();
    }
});

function limparCampos() {
    $('#id').val('');
    $('#correntista').val('');
    $('#banco').val('');
    $('#agencia').val('');
    $('#conta').val('');
    $('#saldo').val('0,00'); // Coloque valor numérico base
    $('#ids').val('');
    $('#btn-deletar').hide();
    $('#mensagem').text('');

    // Re-aplica a máscara após limpar
    setTimeout(function () {
        aplicarMascaras();
    }, 200);
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
    if ($("#agencia").val().trim() === "" || $("#agencia").val() === "0000-0") {
        alert("Agência inválida!");
        return false;
    }
    if ($("#conta").val().trim() === "" || $("#conta").val() === "00000000-0") {
        alert("Conta inválida!");
        return false;
    }
    return true;
}

function excluir(id) {
    $('#mensagem-excluir').text('Excluindo...');
    $.ajax({
        url: 'paginas/' + pag + "/excluir.php",
        method: 'POST',
        data: {
            id
        },
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
