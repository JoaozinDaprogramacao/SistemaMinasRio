document.addEventListener("DOMContentLoaded", () => {
    // Adiciona uma linha inicial para cada seção
    addNewLine1();
    addNewLine2();
    addNewLine3();
});

// Função para adicionar uma nova linha de Produtos
function addNewLine1() {
    const template = document.getElementById("linha-template_1");
    const container = document.getElementById("linha-container_1");
    if (!template || !container) return;

    const newLine = template.cloneNode(true);
    newLine.style.display = "flex";
    newLine.id = "";
    container.appendChild(newLine);
}

// Função para adicionar uma nova linha de Comissões
function addNewLine2() {
    const template = document.getElementById("linha-template_2");
    const container = document.getElementById("linha-container_2");
    if (!template || !container) return;

    const newLine = template.cloneNode(true);
    newLine.style.display = "flex";
    newLine.id = "";
    container.appendChild(newLine);
}

// Função para adicionar uma nova linha de Materiais
function addNewLine3() {
    const template = document.getElementById("linha-template_3");
    const container = document.getElementById("linha-container_3");
    if (!template || !container) return;

    const newLine = template.cloneNode(true);
    newLine.style.display = "flex";
    newLine.id = "";
    container.appendChild(newLine);
}

// Adiciona nova linha de Produtos se a última for preenchida
function handleInput(input) {
    const linha = input.closest(".linha_1");
    const container = document.getElementById("linha-container_1");
    const allInputsFilled = [...linha.querySelectorAll("input, select")].every((field) => field.value.trim() !== "");
    if (allInputsFilled && linha === container.lastElementChild) {
        addNewLine1();
    }
}

// Adiciona nova linha de Comissões se a última for preenchida
function handleInput2(input) {
    const linha = input.closest(".linha_2");
    const container = document.getElementById("linha-container_2");
    const allInputsFilled = [...linha.querySelectorAll("input, select")].every((field) => field.value.trim() !== "");
    if (allInputsFilled && linha === container.lastElementChild) {
        addNewLine2();
    }
}

// Adiciona nova linha de Materiais se a última for preenchida
function handleInput3(input) {
    const linha = input.closest(".linha_3");
    const container = document.getElementById("linha-container_3");
    const produtoField = linha.querySelector(".material");
    const quantidadeField = linha.querySelector(".quant_3");
    const precoUnitField = linha.querySelector(".preco_unit_3");

    const allRequiredFilled =
        produtoField?.value.trim() !== "" &&
        quantidadeField?.value.trim() !== "" &&
        precoUnitField?.value.trim() !== "";

    if (allRequiredFilled && linha === container.lastElementChild) {
        addNewLine3();
    }
}

// Função para mascarar valores monetários
function mascara_moeda(el) {
    var v = el.value;
    v = v.replace(/\D/g, "");
    v = v.replace(/(\d+)(\d{2})$/, "$1,$2");
    v = v.replace(/(\d)(?=(\d{3})+(?!\d))/g, "$1.");
    el.value = v;
}

/**
 * Extrai o valor numérico do texto de uma <option> de um <select>.
 * Ex: "18.00 KG" retorna 18.00
 * @param {HTMLSelectElement} selectElement O elemento <select>.
 * @returns {number} O valor numérico extraído.
 */
function getNumberFromSelectText(selectElement) {
    if (!selectElement || selectElement.selectedIndex < 0) return 0;
    const selectedOption = selectElement.options[selectElement.selectedIndex];
    const optionText = selectedOption.text;
    const match = optionText.match(/(\d+[\.,]?\d*)/);
    return match ? parseFloat(match[0].replace(',', '.')) : 0;
}


// Calcula os valores da linha de Produtos
function calcularValores(linha) {
    const precoKgField = linha.querySelector(".preco_kg_1");
    const tipoCxField = linha.querySelector(".tipo_cx_1");
    const quantCxField = linha.querySelector(".quant_caixa_1");
    const precoUnitField = linha.querySelector(".preco_unit_1");
    const valorField = linha.querySelector(".valor_1");

    // Extrai o peso da caixa corretamente a partir do texto do <select>
    const tipoCx = getNumberFromSelectText(tipoCxField);

    const precoKg = parseFloat(precoKgField.value.replace(",", ".") || 0);
    const quantCx = parseFloat(quantCxField.value || 0);

    const precoUnit = precoKg * tipoCx;
    const valorTotal = precoUnit * quantCx;

    precoUnitField.value = precoUnit.toFixed(2).replace(".", ",");
    valorField.value = valorTotal.toFixed(2).replace(".", ",");

    calculaTotais();
}

// Calcula os valores da linha de Comissões
function calcularValores2(linha) {
    const precoKgField = linha.querySelector(".preco_kg_2");
    const tipoCxField = linha.querySelector(".tipo_cx_2");
    const quantCxField = linha.querySelector(".quant_caixa_2");
    const precoUnitField = linha.querySelector(".preco_unit_2");
    const valorField = linha.querySelector(".valor_2");

    // Extrai o peso da caixa corretamente a partir do texto do <select>
    const tipoCx = getNumberFromSelectText(tipoCxField);

    const precoKg = parseFloat(precoKgField.value.replace(",", ".") || 0);
    const quantCx = parseFloat(quantCxField.value || 0);

    const precoUnit = precoKg * tipoCx;
    const valorTotal = precoUnit * quantCx;

    precoUnitField.value = precoUnit.toFixed(2).replace(".", ",");
    valorField.value = valorTotal.toFixed(2).replace(".", ",");

    // Atualiza o total de comissões
    calculaTotais2();
}

// Calcula os valores da linha de Materiais
function calcularValores3(linha) {
    const quantidadeField = linha.querySelector(".quant_3");
    const precoUnitField = linha.querySelector(".preco_unit_3");
    const valorField = linha.querySelector(".valor_3");

    const quantidade = parseFloat(quantidadeField.value.replace(",", ".") || 0);
    const preco = parseFloat(precoUnitField.value.replace(",", ".") || 0);

    const valorTotal = quantidade * preco;
    valorField.value = valorTotal.toFixed(2).replace(".", ",");

    calculaTotais3();
}

// Calcula os totais da seção de Produtos
function calculaTotais() {
    const totalCaixaField = document.querySelector("#total_caixa");
    const totalKgField = document.querySelector("#total_kg");
    const totalBrutoField = document.querySelector("#total_bruto");
    const descAvistaField = document.querySelector("#desc-avista");
    const totalDescField = document.querySelector("#total-desc");
    const TotalGeralField = document.querySelector("#total-geral");
    const planoPgto = document.querySelector("#plano_pgto");

    if (!planoPgto) return;

    const selectedOption = planoPgto.options[planoPgto.selectedIndex];
    const isDescontoAVista = selectedOption ? selectedOption.text.toUpperCase().includes("VISTA") : false;

    let totalBrutoSoma = 0;
    let totalCaixaSoma = 0;
    let totalKgSoma = 0;

    const linhas = document.querySelectorAll("#linha-container_1 .linha_1");

    linhas.forEach(linha => {
        // Pula a linha de template
        if (linha.id === 'linha-template_1' || linha.style.display === 'none') return;

        const quantCaixaInput = linha.querySelector(".quant_caixa_1");
        const tipoCaixaInput = linha.querySelector(".tipo_cx_1");
        const totalInput = linha.querySelector(".valor_1");

        const quantCaixa = parseFloat(quantCaixaInput.value.replace(",", ".")) || 0;
        const totalValor = parseFloat(totalInput.value.replace(",", ".")) || 0;

        // --- CORREÇÃO APLICADA AQUI ---
        // Pega o peso da caixa corretamente para somar o KG total
        const pesoDaCaixa = getNumberFromSelectText(tipoCaixaInput);
        // --- FIM DA CORREÇÃO ---

        totalCaixaSoma += quantCaixa;
        totalBrutoSoma += totalValor;
        totalKgSoma += (quantCaixa * pesoDaCaixa);
    });

    let totalDescSoma = 0;
    let TotalGeralSoma = totalBrutoSoma;

    if (isDescontoAVista) {
        const descontoValor = parseFloat(descAvistaField.value.replace(",", ".") || 0);
        if (descontoValor <= 0) {
            TotalGeralField.textContent = "DESC. Inválido";
            TotalGeralField.classList.add("danger");
        } else {
            totalDescSoma = (totalBrutoSoma * (descontoValor / 100));
            TotalGeralSoma = totalBrutoSoma - totalDescSoma;
            TotalGeralField.classList.remove("danger");
            TotalGeralField.textContent = TotalGeralSoma.toFixed(2).replace(".", ",");
        }
    } else {
        TotalGeralField.classList.remove("danger");
        TotalGeralField.textContent = TotalGeralSoma.toFixed(2).replace(".", ",");
    }

    totalCaixaField.textContent = totalCaixaSoma + " CXS";
    totalKgField.textContent = totalKgSoma.toFixed(2).replace(".", ",") + " KG";
    totalBrutoField.textContent = "R$ " + totalBrutoSoma.toFixed(2).replace(".", ",");
    totalDescField.textContent = "R$ " + totalDescSoma.toFixed(2).replace(".", ",");

    calculaTotaisFinal();
}

// Calcula os totais da seção de Comissões
function calculaTotais2() {
    const totalComissaoField = document.querySelector("#total_comissao");
    let totalComissaoSoma = 0;
    const linhas = document.querySelectorAll("#linha-container_2 .linha_2");

    linhas.forEach(linha => {
        if (linha.id === 'linha-template_2' || linha.style.display === 'none') return;
        const totalInput = linha.querySelector(".valor_2");
        totalComissaoSoma += parseFloat(totalInput.value.replace(",", ".")) || 0;
    });

    totalComissaoField.textContent = totalComissaoSoma.toFixed(2).replace(".", ",");
    calculaTotaisFinal();
}

// Calcula os totais da seção de Materiais
function calculaTotais3() {
    const totalMateriaisField = document.querySelector("#total_materiais");
    let totalMateriaisSoma = 0;
    const linhas = document.querySelectorAll("#linha-container_3 .linha_3");

    linhas.forEach(linha => {
        if (linha.id === 'linha-template_3' || linha.style.display === 'none') return;
        const totalInput = linha.querySelector(".valor_3");
        totalMateriaisSoma += parseFloat(totalInput.value.replace(",", ".")) || 0;
    });

    totalMateriaisField.textContent = totalMateriaisSoma.toFixed(2).replace(".", ",");
    calculaTotaisFinal();
}

// Calcula o total geral da carga e o valor líquido final
function calculaTotaisFinal() {
    const totalGeral = document.querySelector("#total-geral");
    const totalComissao = document.querySelector("#total_comissao");
    const totalMateriais = document.querySelector("#total_materiais");
    const inputHidden = document.getElementById('valor_liquido');
    const totalCarga = document.querySelector("#total_carga");
    const totalLiquido = document.querySelector("#total_liquido");

    const geralFloat = parseFloat(totalGeral.textContent.replace(/[^\d,-]/g, '').replace(",", ".")) || 0;
    const totalComissaoFloat = parseFloat(totalComissao.textContent.replace(/[^\d,-]/g, '').replace(",", ".")) || 0;
    const totalMateriaisFloat = parseFloat(totalMateriais.textContent.replace(/[^\d,-]/g, '').replace(",", ".")) || 0;

    let soma = geralFloat + totalComissaoFloat + totalMateriaisFloat;

    totalCarga.textContent = soma.toFixed(2).replace(".", ",");
    
    // Chama a função para atualizar o valor líquido com adicionais/descontos
    atualizarValorLiquido();
}

// Variáveis globais para controle dos campos de adicional/desconto
let valorAdicionalAtivo = false;
let valorDescontoAtivo = false;

// Atualiza o valor líquido final considerando Adicional/Desconto
function atualizarValorLiquido() {
    const totalCarga = document.getElementById('total_carga');
    const totalLiquido = document.getElementById('total_liquido');
    const valorAdicional = document.getElementById('valor_adicional');
    const valorDesconto = document.getElementById('valor_desconto');
    const inputHidden = document.getElementById('valor_liquido');

    let valorBase = parseFloat(totalCarga.textContent.replace(/[R$\s]/g, '').replace(',', '.')) || 0;

    if (valorAdicionalAtivo) {
        valorBase += parseFloat(valorAdicional.value.replace(',', '.')) || 0;
    }

    if (valorDescontoAtivo) {
        valorBase -= parseFloat(valorDesconto.value.replace(',', '.')) || 0;
    }

    const valorFormatado = valorBase.toFixed(2).replace('.', ',');
    totalLiquido.textContent = valorFormatado;
    inputHidden.value = valorFormatado;
}

// Função para formatar campos de valor decimal
function mascara_decimal(campo) {
    let valor = campo.value.replace(/\D/g, '');
    valor = (parseFloat(valor) / 100).toFixed(2);
    valor = valor.replace('.', ',');
    campo.value = valor;
    atualizarValorLiquido();
}

// Ativa/desativa campos de Adicional
function adicionalAtivado() {
    const checkbox = document.getElementById('adicional_ativo');
    const descricaoInput = document.getElementById('descricao_adicional');
    const valorInput = document.getElementById('valor_adicional');

    valorAdicionalAtivo = checkbox.checked;
    descricaoInput.disabled = !checkbox.checked;
    valorInput.disabled = !checkbox.checked;

    if (!checkbox.checked) {
        descricaoInput.value = '';
        valorInput.value = '';
    }
    atualizarValorLiquido();
}

// Ativa/desativa campos de Desconto
function descontoAtivado() {
    const checkbox = document.getElementById('desconto_ativo');
    const descricaoInput = document.getElementById('descricao_desconto');
    const valorInput = document.getElementById('valor_desconto');

    valorDescontoAtivo = checkbox.checked;
    descricaoInput.disabled = !checkbox.checked;
    valorInput.disabled = !checkbox.checked;

    if (!checkbox.checked) {
        descricaoInput.value = '';
        valorInput.value = '';
    }
    atualizarValorLiquido();
}