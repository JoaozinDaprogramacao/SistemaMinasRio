
document.addEventListener("DOMContentLoaded", () => {
    addNewLine1();
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

function calculaTotais() {

    const totalCaixaField = document.querySelector("#total_caixa");
    const totalKgField = document.querySelector("#total_kg");
    const totalBrutoField = document.querySelector("#total_bruto");
    const descAvistaField = document.querySelector("#desc-avista");
    const totalDescField = document.querySelector("#total-desc");
    const TotalGeralField = document.querySelector("#total-geral");


    const inputHidden = document.getElementById('valor_liquido');


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


                console.log(TotalGeralSoma.toFixed(2).replace(".", ","));
                valor_liquido.value = TotalGeralSoma.toFixed(2).replace(".", ",");
                TotalGeralField.innerHTML = TotalGeralSoma.toFixed(2).replace(".", ",");
            } else {
                console.error("Valores inválidos: totalBrutoSoma ou descAvistaField.value");
                totalDescSoma = 0; // Valor padrão em caso de erro
            }
        }

    } else {
        
        console.log(TotalGeralSoma.toFixed(2).replace(".", ","));
        valor_liquido.value = TotalGeralSoma.toFixed(2).replace(".", ",");
        TotalGeralField.innerHTML = TotalGeralSoma.toFixed(2).replace(".", ",");
    }

    // Atualiza os campos com os totais
    totalCaixaField.innerHTML = totalCaixaSoma + " CXS";
    totalKgField.innerHTML = totalKgSoma.toFixed(2).replace(".", ",") + " KG";
    totalBrutoField.innerHTML = "R$ " + totalBrutoSoma.toFixed(2).replace(".", ",");
    totalDescField.innerHTML = "R$ " + totalDescSoma.toFixed(2).replace(".", ",");
}


