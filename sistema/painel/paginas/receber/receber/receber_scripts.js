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
    // Captura os valores dos campos corretamente pelos IDs do HTML
    var filtro = ""; // Se você tiver um filtro de status (Vencidas/Recebidas) global, coloque aqui
    var dataInicial = $('#dataInicial').val();
    var dataFinal = $('#dataFinal').val();
    var tipo_data = $('#filtrar_por').val(); // ID CORRETO do select de vencimento/lançamento
    var atacadista = $('#atacadista').val();
    var formaPGTO = $('#formaPGTO').val();
    var tipo_conta = $('#tipo_conta').val(); // Você tem esse select no HTML, precisa enviar também

    // Passa para a função listar
    listar(filtro, dataInicial, dataFinal, tipo_data, atacadista, formaPGTO, tipo_conta);
}

function listar(p1, p2, p3, p4, p5, p6, p7) {
    $.ajax({
        url: 'paginas/' + pag + "/listar.php",
        method: 'POST',
        // Enviamos como p1, p2, p3... que é como o seu listar.php está configurado para receber
        data: {
            p1: p1,
            p2: p2,
            p3: p3,
            p4: p4,
            p5: p5,
            p6: p6,
            p7: p7
        },
        dataType: "html",
        success: function (result) {
            $("#listar").html(result);
        }
    });

}

function prepararBaixar(id, valor, descricao, forma_pgto) {
    $('#id-baixar').val(id);
    $('#descricao-baixar').text(descricao);
    $('#valor-baixar').val(valor);
    $('#saida-baixar').val(forma_pgto).change();

    // Chama a função de totalizar que você já tem no formulário
    totalizar();

    // Abre o modal de baixar
    $('#modalBaixar').modal('show');
}

function fecharEditarEAbrirBaixar(id, valor, descricao, forma_pgto) {
    // 1. Fecha o modal de edição
    $('#modalForm').modal('hide');

    // 2. Pequeno delay para o Bootstrap processar o fechamento antes de abrir o outro
    // Isso evita bugs de tela travada
    setTimeout(function () {
        // Aqui você chama a função que preenche e abre o seu modal de BAIXAR
        // (Ajuste o nome da função abaixo conforme a que você já usa no seu sistema)
        prepararBaixar(id, valor, descricao, forma_pgto);
    }, 400);
}

function editar(id, descricao, valor, cliente, vencimento, data_pgto, forma_pgto, frequencia, obs, arquivo) {
    console.log("Log1: entrou");
    $('#mensagem').text('');
    $('#titulo_inserir').text('Editar Registro');

    $('#id').val(id);
    $('#descricao').val(descricao);
    $('#valor').val(valor);
    $('#cliente').val(cliente).change();
    $('#vencimento').val(vencimento);
    $('#data_pgto').val(data_pgto);
    $('#forma_pgto').val(forma_pgto).change();
    $('#frequencia').val(frequencia).change();
    $('#obs').val(obs);
    console.log("Log2: continuou");

    $('#arquivo').val('');
    $('#target').attr('src', 'images/contas/' + arquivo);

    $('#modalForm').modal('show');
    console.log("Log3: finalizou");

    $('#btn-baixar-modal').show();

    // Se o seu botão de baixar precisar dos dados atuais, 
    // podemos passar para uma função global ou setar um atributo
    $('#btn-baixar-modal').attr('onclick', `baixar('${id}', '${valor}', '${descricao}', '${forma_pgto}', '0', '0', '0')`);

    $('#btn-baixar-modal').attr('onclick', `fecharEditarEAbrirBaixar('${id}', '${valor}', '${descricao}', '${forma_pgto}')`);

    $('#modalForm').modal('show');
}


function mostrar(descricao, valor, cliente, vencimento, data_pgto, nome_pgto, frequencia, obs, arquivo, multa, juros, desconto, taxa, total, usu_lanc, usu_pgto, pago, arq) {

    if (data_pgto == "") {
        data_pgto = 'Pendente';
    }

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

    $('#ids').val('');
    $('#btn-deletar').hide();
    $('#btn-baixar-modal').hide();
    $('#btn-baixar').hide();
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

    setTimeout(() => {
        listar();
    }, 1000);

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
            data: {
                novo_id
            },
            dataType: "html",

            success: function (result) {
                //alert(result)

            }
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


function baixar(id, valor, descricao, pgto, taxa, multa, juros) {
    $('#id-baixar').val(id);
    $('#descricao-baixar').text(descricao);
    $('#valor-baixar').val(valor);
    $('#saida-baixar').val(pgto).change();
    $('#subtotal').val(valor);


    $('#valor-juros').val(juros);
    $('#valor-desconto').val('');
    $('#valor-multa').val(multa);
    $('#valor-taxa').val(taxa);

    totalizar()

    $('#modalBaixar').modal('show');
    $('#mensagem-baixar').text('');
}


function mostrarResiduos(id) {

    $.ajax({
        url: 'paginas/' + pag + "/listar-residuos.php",
        method: 'POST',
        data: {
            id
        },
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
        data: {
            id
        },
        dataType: "html",

        success: function (result) {
            alert(result);
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

$(document).on('submit', '#form-baixar', function (e) {
    e.preventDefault();
    console.log("Iniciando baixa..."); // Log para teste

    var formData = new FormData(this);
    $.ajax({
        url: 'paginas/' + pag + "/baixar.php",
        type: 'POST',
        data: formData,
        cache: false,
        contentType: false,
        processData: false,
        success: function (mensagem) {
            console.log("Resposta: " + mensagem); // Log para teste
            if (mensagem.trim() == "Baixado com Sucesso") {
                $('#btn-fechar-baixar').click();
                if (typeof buscar === "function") {
                    buscar();
                } else {
                    listar(); // Caso sua função de recarregar seja listar
                }
            } else {
                $('#mensagem-baixar').addClass('text-danger').text(mensagem);
            }
        },
        error: function(xhr, status, error) {
            console.error("Erro no AJAX: ", error);
            $('#mensagem-baixar').text("Erro interno no servidor.");
        }
    });
});F

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