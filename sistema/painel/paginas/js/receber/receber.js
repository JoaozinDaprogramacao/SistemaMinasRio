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
