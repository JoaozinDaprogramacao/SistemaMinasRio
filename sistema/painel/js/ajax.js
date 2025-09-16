$(document).ready(function () {
    $('#listar').text("Carregando Dados...");
    listar();
});

function listar(p1, p2, p3, p4, p5, p6, p7, p8) {
    $.ajax({
        url: 'paginas/' + pag + "/listar.php",
        method: 'POST',
        data: { p1, p2, p3, p4, p5, p6 , p7, p8},
        dataType: "html",

        success: function (result) {
            $("#listar").html(result);
            $('#mensagem-excluir').text('');
        }
    });
}

function inserir() {
    $('#mensagem').text('');
    $('#titulo_inserir').text('Inserir Registro');
    $('#modalForm').modal('show');
    limparCampos();
}

// Variável global para armazenar o valor do salário mínimo
let salarioMinimoAtual = 0;

// Função para buscar e armazenar o salário mínimo
function carregarSalarioMinimo() {
    // Faz uma requisição para a nossa API interna
    fetch('buscar_salario.php')
        .then(response => response.json())
        .then(data => {
            if (data && data.valor > 0) {
                salarioMinimoAtual = data.valor;
                console.log('Salário mínimo carregado: R$', salarioMinimoAtual);
            } else {
                // Usa um valor de fallback se a API falhar
                salarioMinimoAtual = 1518.00; 
            }
        })
        .catch(error => {
            console.error('Erro ao buscar salário mínimo:', error);
            // Usa um valor de fallback em caso de erro de rede
            salarioMinimoAtual = 1518.00;
        });
}

// Função de cálculo que agora usa a variável atualizada
function calcularSalarioFolha() {
    if (salarioMinimoAtual === 0) {
        // Se o salário ainda não carregou, não faz o cálculo
        return; 
    }

    const inputDescricao = document.getElementById('descricao_salario');
    const inputSalarioFolha = document.getElementById('salario_folha');
    const multiplicador = parseFloat(inputDescricao.value.replace(',', '.')) || 0;
    
    const salarioCalculado = multiplicador * salarioMinimoAtual;

    inputSalarioFolha.value = salarioCalculado.toFixed(2).replace('.', '.'); // Garante o ponto
}


// Chame esta função assim que a página carregar
document.addEventListener('DOMContentLoaded', function() {
    carregarSalarioMinimo();
});

function mascara_decimal_ponto(el) {
    // 1. Pega o valor e remove tudo que não for dígito
    let valor = el.value.replace(/\D/g, '');

    // Se o campo estiver vazio, não faz nada
    if (valor === '') {
        el.value = '';
        return;
    }

    // 2. Converte para número e depois para string para remover
    //    zeros à esquerda desnecessários (ex: "00251" vira "251")
    valor = String(Number(valor));

    // 3. Adiciona zeros à esquerda novamente, se necessário, para
    //    garantir que temos casas decimais (ex: "5" vira "005" => 0.05)
    while (valor.length < 3) {
        valor = '0' + valor;
    }

    // 4. Separa a parte inteira dos decimais
    let parteInteira = valor.slice(0, -2);
    let centavos = valor.slice(-2);
    
    // Garante que a parte inteira seja '0' se não houver nada
    if (parteInteira === '') {
        parteInteira = '0';
    }

    // 5. Monta o valor final e atualiza o campo
    el.value = parteInteira + '.' + centavos;
}   


$("#form").submit(function () {

    event.preventDefault();
    var formData = new FormData(this);

    $('#mensagem').text('Salvando...')
    $('#btn_salvar').hide();

    $.ajax({
        url: 'paginas/' + pag + "/salvar.php",
        type: 'POST',
        data: formData,

        success: function (mensagem) {
            $('#mensagem').text('');
            $('#mensagem').removeClass()
            if (mensagem.trim() == "Salvo com Sucesso") {

                $('#btn-fechar').click();
                listar();

                $('#mensagem').text('')

            } else {

                $('#mensagem').addClass('text-danger')
                $('#mensagem').text(mensagem)
            }

            $('#btn_salvar').show();

        },

        cache: false,
        contentType: false,
        processData: false,

    });

});




function excluir(id) {
    $('#mensagem-excluir').text('Excluindo...')

    $.ajax({
        url: 'paginas/' + pag + "/excluir.php",
        method: 'POST',
        data: { id },
        dataType: "html",

        success: function (mensagem) {
            if (mensagem.trim() == "Excluído com Sucesso") {
                listar();
                limparCampos()
            } else {
                $('#mensagem-excluir').addClass('text-danger')
                $('#mensagem-excluir').text(mensagem)
            }
        }
    });
}




function excluirMultiplos(id) {
    $('#mensagem-excluir').text('Excluindo...')

    $.ajax({
        url: 'paginas/' + pag + "/excluir.php",
        method: 'POST',
        data: { id },
        dataType: "html",

        success: function (mensagem) {
            if (mensagem.trim() == "Excluído com Sucesso") {
                //listar();
                limparCampos()
            } else {
                $('#mensagem-excluir').addClass('text-danger')
                $('#mensagem-excluir').text(mensagem)
            }
        }
    });
}



function ativar(id, acao) {
    $.ajax({
        url: 'paginas/' + pag + "/mudar-status.php",
        method: 'POST',
        data: { id, acao },
        dataType: "html",

        success: function (mensagem) {
            if (mensagem.trim() == "Alterado com Sucesso") {
                listar();
            } else {
                $('#mensagem-excluir').addClass('text-danger')
                $('#mensagem-excluir').text(mensagem)
            }
        }
    });
}




function mascara_moeda(el) {
    // el pode ser this (o próprio <input>) ou um seletor jQuery
    var $el = $(el);
    var v   = $el.val() || '';
    
    // 1) tira tudo que não for dígito
    v = v.replace(/\D/g, '');
    // 2) se vazio, vira "0"
    if (v === '') v = '0';
    // 3) garante no mínimo 3 dígitos
    while (v.length < 3) v = '0' + v;
    // 4) separa reais / centavos
    var inteiro  = v.slice(0, -2);
    var centavos = v.slice(-2);
    // 5) separador de milhares (opcional)
    inteiro = inteiro.replace(/\B(?=(\d{3})+(?!\d))/g, '.');
    // 6) atualiza campo
    $el.val(inteiro + ',' + centavos);
  
    // 7) recálculo de totais
    if (typeof calculaTotais === 'function') calculaTotais();
  }


  function mascara_decimal(el) {
    // el é o próprio <input> que chamou a função
    var $input = $(el);
    var valor = $input.val() || "";

    // 1) remove tudo que não for dígito
    valor = valor.replace(/\D/g, "");

    // 2) garante pelo menos 3 dígitos (para ter sempre R$0,01 como mínimo)
    while (valor.length < 3) {
        valor = "0" + valor;
    }

    // 3) separa parte inteira e centavos
    var parteInteira  = valor.slice(0, -2);
    var centavos      = valor.slice(-2);

    // 4) pontua milhares
    parteInteira = parteInteira.replace(/\B(?=(\d{3})+(?!\d))/g, ".");

    // 5) monta string final e atualiza o campo
    $input.val(parteInteira + "," + centavos);
}




