document.addEventListener("DOMContentLoaded", () => {
    addNewLine1();
    addDiscountLine();
});

function addNewLine1() {
    const template = document.getElementById("linha-template_1");
    const container = document.getElementById("linha-container_1");
    const newLine = template.cloneNode(true);
    newLine.style.display = "flex";
    newLine.id = "";
    container.appendChild(newLine);
}

function handleInput(input) {
    const linha = input.closest(".linha_1");
    const container = document.getElementById("linha-container_1");
    const allInputsFilled = [...linha.querySelectorAll("input, select")].every((field) => field.value.trim() !== "");
    if (allInputsFilled) {
        const isLastLine = linha === container.lastElementChild;
        if (isLastLine) {
            addNewLine1();
        }
    }
}

function mascara_moeda(el) {
    let digits = el.value.replace(/\D/g, "");
    digits = digits.replace(/^0+(?=\d)/, "");
    let intPart, centPart;
    if (digits.length <= 2) {
        intPart = "0";
        centPart = digits.padStart(2, "0");
    } else {
        intPart = digits.slice(0, -2);
        centPart = digits.slice(-2);
    }
    intPart = intPart.replace(/(\d)(?=(\d{3})+(?!\d))/g, "$1.");
    const formatted = intPart + "," + centPart;
    el.value = formatted;
    el.selectionStart = el.selectionEnd = formatted.length;
}

function calcularValores(linha) {
    const precoKgField = linha.querySelector(".preco_kg_1");
    const tipoCxField = linha.querySelector(".tipo_cx_1");
    const quantCxField = linha.querySelector(".quant_caixa_1");
    const precoUnitField = linha.querySelector(".preco_unit_1");
    const valorField = linha.querySelector(".valor_1");

    try {
        let precoKgValor = precoKgField.value.trim().replace(/[^\d,\.]/g, '').replace(',', '.');
        const precoKg = parseFloat(precoKgValor || '0');
        let tipoCxValor = tipoCxField.value.trim().replace(',', '.');
        const tipoCx = parseFloat(tipoCxValor || '0');
        const quantCx = parseInt(quantCxField.value || '0', 10);

        if (isNaN(precoKg) || isNaN(tipoCx) || isNaN(quantCx)) {
            precoUnitField.value = "0,00";
            valorField.value = "0,00";
            return;
        }

        const precoUnit = precoKg * tipoCx;
        const valorTotal = precoUnit * quantCx;

        precoUnitField.value = precoUnit.toFixed(2).replace(".", ",");
        valorField.value = valorTotal.toFixed(2).replace(".", ",");
    } catch (e) {
        precoUnitField.value = "0,00";
        valorField.value = "0,00";
    }
    calculaTotais();
}

function calculaTotais() {
    try {
        const totalCaixaField = document.querySelector("#total_caixa");
        const totalKgField = document.querySelector("#total_kg");
        const totalBrutoField = document.querySelector("#total_bruto");
        const descAvistaField = document.querySelector("#desc-avista");
        const totalDescField = document.querySelector("#total-desc");
        const totalGeralField = document.querySelector("#total-geral");
        const inputHidden = document.getElementById('valor_liquido');

        const planoPgto = document.querySelector("#plano_pgto");
        let selectedText = planoPgto.options[planoPgto.selectedIndex]?.text || "";
        let desconto = selectedText.toUpperCase().includes("VISTA");

        let totalBrutoSoma = 0, totalCaixaSoma = 0, totalKgSoma = 0;

        document.querySelectorAll(".linha_1").forEach((linha) => {
            if (linha.style.display === 'none') return;
            const q = parseFloat(linha.querySelector(".quant_caixa_1").value.replace(',', '.')) || 0;
            const t = parseFloat(linha.querySelector(".tipo_cx_1").value.replace(',', '.')) || 0;
            const v = parseFloat(linha.querySelector(".valor_1").value.replace(/[^\d,]/g, '').replace(',', '.')) || 0;
            totalCaixaSoma += q;
            totalBrutoSoma += v;
            totalKgSoma += (q * t);
        });

        let descontoAvista = 0;
        if (desconto) {
            const dPerc = parseFloat(descAvistaField.value.replace(',', '.')) || 0;
            if (dPerc <= 0) {
                totalGeralField.innerHTML = "DESC. OBRIGATÓRIO";
                totalGeralField.classList.add("danger");
            } else {
                totalGeralField.classList.remove("danger");
                descontoAvista = (totalBrutoSoma * (dPerc / 100));
            }
        }

        let totalGeralSoma = totalBrutoSoma - descontoAvista;
        totalCaixaField.innerHTML = Math.round(totalCaixaSoma) + " CXS";
        totalKgField.innerHTML = totalKgSoma.toFixed(2).replace('.', ',') + " KG";
        totalBrutoField.innerHTML = "R$ " + totalBrutoSoma.toFixed(2).replace('.', ',');
        totalDescField.innerHTML = "R$ " + descontoAvista.toFixed(2).replace('.', ',');
        
        if (!totalGeralField.classList.contains("danger")) {
            totalGeralField.innerHTML = totalGeralSoma.toFixed(2).replace('.', ',');
            inputHidden.value = totalGeralSoma.toFixed(2).replace('.', ',');
        }
    } catch (e) {}
    updateLiquidPayable();
}

function calcularTotalAbatimentos() {
    const { bruto, liquido, caixas, kg } = getTotals();
    let somaAbatimentos = 0;

    document.querySelectorAll(".linha-abatimentos").forEach(linha => {
        const inputDesc = linha.querySelector('input[id^="desc_"]');
        const selInfo = linha.querySelector('select[id^="info_"], input[id^="taxa_adm_val_"]');
        const selPreco = linha.querySelector('select[id^="preco_unit_"]');
        const campoResultado = linha.querySelector('input[id^="valor_"]');

        if(!inputDesc || !selInfo || !selPreco || !campoResultado) return;

        const desc = inputDesc.value.toUpperCase();
        const infoVal = selInfo.value.toLowerCase();
        const precoRaw = selPreco.value;

        // Limpa o valor para cálculo (ex: "0,0025 %" -> 0.0025)
        let pUnit = parseFloat(precoRaw.replace(',', '.').replace('%', '').trim()) || 0;
        let isPercent = precoRaw.includes('%');
        let valorCalculado = 0;

        if (desc === 'TAXA ADM') {
            let taxaInput = parseFloat(infoVal.replace(',', '.')) || 0;
            valorCalculado = taxaInput * pUnit;
        } else if (desc === 'FUNRURAL') {
            let base = (infoVal.includes('bruto')) ? bruto : (infoVal.includes('liquido')) ? liquido : 0;
            valorCalculado = base * (pUnit / 100);
        } else {
            // Lógica para ABANORTE, IMA e outros
            if (infoVal === 'kg') {
                // Se INFO é KG, multiplica Preço Unitário pelo Total de KG
                // Mesmo que tenha %, tratamos como valor nominal por unidade de medida
                valorCalculado = pUnit * kg;
            } else if (infoVal === 'cx' || infoVal === 'caixa') {
                valorCalculado = pUnit * caixas;
            } else if (infoVal === '1' || infoVal === 'um') {
                valorCalculado = pUnit;
            } else {
                // Caso não tenha uma unidade de medida clara e seja %, aplica sobre o bruto
                if (isPercent) {
                    valorCalculado = bruto * (pUnit / 100);
                } else {
                    valorCalculado = pUnit;
                }
            }
        }

        campoResultado.value = valorCalculado.toFixed(2).replace('.', ',');
        somaAbatimentos += valorCalculado;
    });

    const totalComissaoField = document.getElementById("total_comissao");
    if(totalComissaoField) totalComissaoField.textContent = somaAbatimentos.toFixed(2).replace(".", ",");
    
    updateLiquidPayable();
}

function parseBrasil(str) {
    if (!str) return 0;
    str = str.replace(/[^\d,]/g, '').replace(',', '.');
    return parseFloat(str) || 0;
}

function getTotals() {
    return {
        bruto: parseBrasil(document.querySelector('#total_bruto')?.textContent || ''),
        liquido: parseBrasil(document.getElementById('valor_liquido')?.value || ''),
        caixas: parseInt((document.querySelector('#total_caixa')?.textContent || '').replace(/\D/g, ''), 10) || 0,
        kg: parseBrasil((document.querySelector('#total_kg')?.textContent || '').replace(/[^\d,]/g, '')),
    };
}

function addDiscountLine() {
    const tpl = document.getElementById("discount-template");
    const container = document.getElementById("discount-container");
    const newLine = tpl.cloneNode(true);
    newLine.style.display = "block";
    newLine.id = "";
    newLine.querySelector('.desconto-type').addEventListener('change', calcularDescontosDiversos);
    newLine.querySelector('.desconto-valor').addEventListener('input', function(e) {
        mascara_moeda(e.target);
        calcularDescontosDiversos();
    });
    container.appendChild(newLine);
}

function removeDiscountLine(btn) {
    btn.closest(".linha_3").remove();
    calcularDescontosDiversos();
}

function calcularDescontosDiversos() {
    let total = 0;
    document.querySelectorAll('#discount-container .linha_3').forEach(linha => {
        const tipo = linha.querySelector('.desconto-type').value;
        const valor = parseBrasil(linha.querySelector('.desconto-valor').value);
        total += (tipo === '+' ? valor : -valor);
    });
    document.getElementById('total_descontos_diversos').textContent = total.toFixed(2).replace('.', ',');
    updateLiquidPayable();
}

function updateLiquidPayable() {
    const liquidBase = parseBrasil(document.getElementById('valor_liquido').value);
    const comissao = parseBrasil(document.getElementById('total_comissao').textContent);
    const descontos = parseBrasil(document.getElementById('total_descontos_diversos').textContent);
    const finalLiquido = liquidBase - comissao + descontos;
    document.getElementById('total_liquido_pagar').textContent = finalLiquido.toFixed(2).replace('.', ',');
}