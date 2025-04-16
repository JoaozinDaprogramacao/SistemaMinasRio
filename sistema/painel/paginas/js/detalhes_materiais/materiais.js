
document.addEventListener("DOMContentLoaded", () => {
    addNewLine1();
    addNewLine2();
    addNewLine3();
});


// Função para adicionar uma nova linha
function addNewLine1() {
    const template = document.getElementById("linha-template_1");
    const container = document.getElementById("linha-container_1");

    // Clona o template
    const newLine = template.cloneNode(true);
    newLine.style.display = "flex"; // Torna a linha visível
    newLine.id = ""; // Remove o ID do clone para evitar duplicação

    // Adiciona ao container
    container.appendChild(newLine);
}

// Função para adicionar uma nova linha
function addNewLine2() {
    const template = document.getElementById("linha-template_2");
    const container = document.getElementById("linha-container_2");

    // Clona o template
    const newLine = template.cloneNode(true);
    newLine.style.display = "flex"; // Torna a linha visível
    newLine.id = ""; // Remove o ID do clone para evitar duplicação

    // Adiciona ao container
    container.appendChild(newLine);
}

// Função para adicionar uma nova linha
function addNewLine3() {
    const template = document.getElementById("linha-template_3");
    const container = document.getElementById("linha-container_3");

    // Clona o template
    const newLine = template.cloneNode(true);
    newLine.style.display = "flex"; // Torna a linha visível
    newLine.id = ""; // Remove o ID do clone para evitar duplicação

    // Adiciona ao container
    container.appendChild(newLine);
}

// Função chamada ao preencher um campo
function handleInput(input) {
    const linha = input.closest(".linha_1");
    const container = document.getElementById("linha-container_1");

    // Verifica se a linha é a última e está preenchida
    const allInputsFilled = [...linha.querySelectorAll("input, select")].every((field) => field.value.trim() !== "");
    if (allInputsFilled) {
        const isLastLine = linha === container.lastElementChild;
        if (isLastLine) {
            addNewLine1();
        }
    }
}

function handleInput2(input) {
    const linha = input.closest(".linha_2");
    const container = document.getElementById("linha-container_2");

    // Verifica se a linha é a última e está preenchida
    const allInputsFilled = [...linha.querySelectorAll("input, select")].every((field) => field.value.trim() !== "");
    if (allInputsFilled) {
        const isLastLine = linha === container.lastElementChild;
        if (isLastLine) {
            addNewLine2();
        }
    }
}

function handleInput3(input) {
    const linha = input.closest(".linha_3");
    const container = document.getElementById("linha-container_3");

    // Verifica se a linha é a última e está preenchida
    const allInputsFilled = [...linha.querySelectorAll("input, select")].every((field) => field.value.trim() !== "");
    if (allInputsFilled) {
        const isLastLine = linha === container.lastElementChild;
        if (isLastLine) {
            addNewLine3();
        }
    }
}

function mascara_moeda(valor) {
    var valorAlterado = $('#' + valor).val();
    valorAlterado = valorAlterado.replace(/\D/g, ""); // Remove todos os não dígitos
    valorAlterado = valorAlterado.replace(/(\d+)(\d{2})$/, "$1,$2"); // Adiciona a parte de centavos
    valorAlterado = valorAlterado.replace(/(\d)(?=(\d{3})+(?!\d))/g, "$1."); // Adiciona pontos a cada três dígitos
    valorAlterado = valorAlterado;
    $('#' + valor).val(valorAlterado);
}

function calcularValores(linha) {

    // Obter os campos da linha fornecida
    const precoKgField = linha.querySelector(".preco_kg_1");
    const tipoCxField = linha.querySelector(".tipo_cx_1");
    const quantCxField = linha.querySelector(".quant_caixa_1");
    const precoUnitField = linha.querySelector(".preco_unit_1");
    const valorField = linha.querySelector(".valor_1");

    // Extrair valores e converter para números
    const precoKg = parseFloat(precoKgField.value.replace(",", ".") || 0);
    const tipoCx = parseFloat(tipoCxField.value || 0); // Valor padrão de tipo_cx como 1
    const quantCx = parseFloat(quantCxField.value || 0);

    // Calcular o preço unitário e o valor total
    const precoUnit = precoKg * tipoCx;
    const valorTotal = precoUnit * quantCx;

    // Atualizar os campos na linha
    precoUnitField.value = precoUnit.toFixed(2).replace(".", ",");
    valorField.value = valorTotal.toFixed(2).replace(".", ",");

    calculaTotais();
}

function calcularValores2(linha) {

    // Obter os campos da linha fornecida
    const precoKgField = linha.querySelector(".preco_kg_2");
    const tipoCxField = linha.querySelector(".tipo_cx_2");
    const quantCxField = linha.querySelector(".quant_caixa_2");
    const precoUnitField = linha.querySelector(".preco_unit_2");
    const valorField = linha.querySelector(".valor_2");

    // Extrair valores e converter para números
    const precoKg = parseFloat(precoKgField.value.replace(",", ".") || 0);
    const tipoCx = parseFloat(tipoCxField.value || 0); // Valor padrão de tipo_cx como 1
    const quantCx = parseFloat(quantCxField.value || 0);

    // Calcular o preço unitário e o valor total
    const precoUnit = precoKg * tipoCx;
    const valorTotal = precoUnit * quantCx;

    // Atualizar os campos na linha
    precoUnitField.value = precoUnit.toFixed(2).replace(".", ",");
    valorField.value = valorTotal.toFixed(2).replace(".", ",");

    calculaTotais2();
}

function calcularValores3(linha) {

    const quantidadeField = linha.querySelector(".quant_3");
    const precoUnitField = linha.querySelector(".preco_unit_3");
    const valor = linha.querySelector(".valor_3");

    // Extrair valores e converter para números
    const quantidade = parseFloat(quantidadeField.value.replace(",", ".") || 0);
    const preco = parseFloat(precoUnitField.value.replace(",", ".") || 0);


    const valorTotal = quantidade * preco;

    // Atualizar os campos na linha
    valor.value = valorTotal.toFixed(2).replace(".", ",");


    calculaTotais3();
}


function calculaTotais() {

    const totalCaixaField = document.querySelector("#total_caixa");
    const totalKgField = document.querySelector("#total_kg");
    const totalBrutoField = document.querySelector("#total_bruto");
    const descAvistaField = document.querySelector("#desc-avista");
    const totalDescField = document.querySelector("#total-desc");
    const TotalGeralField = document.querySelector("#total-geral");


    const planoPgto = document.querySelector("#plano_pgto");
    let selectedText = planoPgto.children[planoPgto.selectedIndex].text;
    selectedText = selectedText.toUpperCase();

    let desconto = false;

    if (selectedText.includes("VISTA")) {
        desconto = true;
    }

    let totalBrutoSoma = 0;
    let totalCaixaSoma = 0;
    let totalKgSoma = 0;
    let totalDescSoma = 0;
    let TotalGeralSoma = 0;

    const linhas = document.querySelectorAll(".linha_1");

    for (let index = 0; index < linhas.length; index++) {
        const quantCaixaInput = linhas[index].querySelector(".quant_caixa_1");
        const tipoCaixaInput = linhas[index].querySelector(".tipo_cx_1");
        const totalInput = linhas[index].querySelector(".valor_1");

        if (quantCaixaInput) {
            // Converte os valores para números corretamente, lidando com vírgulas
            const quantCaixa = parseFloat(quantCaixaInput.value.replace(",", ".")) || 0;
            const tipoCaixa = parseFloat(tipoCaixaInput.value.replace(",", ".")) || 0;
            const totalValor = parseFloat(totalInput.value.replace(",", ".")) || 0;

            totalCaixaSoma += quantCaixa;
            totalBrutoSoma += totalValor;

            // Calcula o total em KG
            const calculo = quantCaixa * tipoCaixa;
            totalKgSoma += calculo;
        } else {
            console.log(`Linha ${index + 1}: Campo quant_caixa não encontrado.`);
        }
    }

    TotalGeralSoma = totalBrutoSoma;
    if (desconto) {
        const descontoValor = parseFloat(descAvistaField.value) || 0; // Garante que seja um número válido

        if (descontoValor <= 0) {
            TotalGeralField.innerHTML = "DESC. Invalid.";
            TotalGeralField.classList.add("danger");

        } else {
            if (!isNaN(totalBrutoSoma) && !isNaN(descontoValor)) { // Verifica se os valores são válidos
                totalDescSoma = (totalBrutoSoma * (descontoValor / 100));
                TotalGeralSoma = totalBrutoSoma - totalDescSoma;
                TotalGeralField.classList.remove("danger");
                TotalGeralField.innerHTML = TotalGeralSoma.toFixed(2).replace(".", ",");
            } else {
                console.error("Valores inválidos: totalBrutoSoma ou descAvistaField.value");
                totalDescSoma = 0; // Valor padrão em caso de erro
            }
        }

    } else {
        TotalGeralField.innerHTML = TotalGeralSoma.toFixed(2).replace(".", ",");
    }

    // Atualiza os campos com os totais
    totalCaixaField.innerHTML = totalCaixaSoma + " CXS";
    totalKgField.innerHTML = totalKgSoma.toFixed(2).replace(".", ",") + " KG";
    totalBrutoField.innerHTML = "R$ " + totalBrutoSoma.toFixed(2).replace(".", ",");
    totalDescField.innerHTML = "R$ " + totalDescSoma.toFixed(2).replace(".", ",");

    calculaTotaisFinal();
}


function calculaTotais2() {
    const totalComissaoField = document.querySelector("#total_comissao");

    let totalComissaoSoma = 0;

    const linhas = document.querySelectorAll(".linha_2");

    for (let index = 0; index < linhas.length; index++) {
        const totalInput = linhas[index].querySelector(".valor_2");

        // Acumula os valores
        totalComissaoSoma += parseFloat(totalInput.value.replace(",", ".")) || 0;
    }

    // Atualiza o campo total de comissão
    totalComissaoField.innerHTML = totalComissaoSoma.toFixed(2).replace(".", ",");

    // Chama a próxima função
    calculaTotaisFinal();
}


function calculaTotais3() {
    const totalMateriais = document.querySelector("#total_materiais");

    let totalMateriaisSoma = 0;

    const linhas = document.querySelectorAll(".linha_3");

    for (let index = 0; index < linhas.length; index++) {
        const totalInput = linhas[index].querySelector(".valor_3");

        // Acumula os valores corretamente
        totalMateriaisSoma += parseFloat(totalInput.value.replace(",", ".")) || 0;
    }

    // Atualiza o campo total de materiais
    totalMateriais.innerHTML = totalMateriaisSoma.toFixed(2).replace(".", ",");

    // Chama a função calculaTotaisFinal
    calculaTotaisFinal();
}

function calculaTotaisFinal() {
    const totalGeral = document.querySelector("#total-geral");
    const totalComissao = document.querySelector("#total_comissao");
    const totalMateriais = document.querySelector("#total_materiais");
    const inputHidden = document.getElementById('valor_liquido');



    const totalCarga = document.querySelector("#total_carga");
    const totalLiquido = document.querySelector("#total_liquido");

    // Use textContent ou innerText para obter o texto do elemento
    const geralFloat = parseFloat(totalGeral.textContent.replace(",", ".")) || 0;
    const totalComissaoFloat = parseFloat(totalComissao.textContent.replace(",", ".")) || 0;
    const totalmateriaisFloat = parseFloat(totalMateriais.textContent.replace(",", ".")) || 0;

    console.log(geralFloat); // Verificar o valor após o parseFloat
    console.log(totalGeral); // Verificar o elemento

    let soma = geralFloat + totalComissaoFloat + totalmateriaisFloat;

    totalCarga.innerHTML = soma.toFixed(2).replace(".", ",");
    valor_liquido.value = soma.toFixed(2).replace(".", ",");
    totalLiquido.innerHTML = soma.toFixed(2).replace(".", ",");
}

let adicional;

// Seleciona os elementos dos rádios
// Funções específicas para cada rádio
function adicionalAtivado() {
    adicional = true;
    calcularValorDA();
}

function descontoAtivado() {
    adicional = false;
    calcularValorDA();
}

function calcularValorDA() {
    const totalCarga = document.querySelector("#total_carga");
    const totalLiquido = document.querySelector("#total_liquido");
    const valorInput = document.querySelector("#valor");

    const totalCargaFloat = parseFloat(totalCarga.textContent.replace(",", ".")) || 0;
    const valorPreenchido = parseFloat(valorInput.value.replace(",", ".")) || 0;

    let totalcalculo = 0
    if (adicional) {
        
        totalcalculo = totalCargaFloat + valorPreenchido;
    } else {
        totalcalculo = totalCargaFloat - valorPreenchido;
    }
    console.log(totalcalculo);

    totalLiquido.innerHTML = totalcalculo.toFixed(2).replace(".", ",");
}
