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
        let precoKgVal = parseFloat(precoKgField.value.replace(/\./g, '').replace(',', '.')) || 0;
        let tipoCxVal = parseFloat(tipoCxField.value.replace(',', '.')) || 0;
        let quantCxVal = parseInt(quantCxField.value || '0', 10);

        if (isNaN(precoKgVal) || isNaN(tipoCxVal) || isNaN(quantCxVal)) {
            precoUnitField.value = "0,00";
            valorField.value = "0,00";
            return;
        }

        const precoUnit = Math.round(precoKgVal * tipoCxVal * 100) / 100;
        const valorTotal = Math.round(precoUnit * quantCxVal * 100) / 100;

        precoUnitField.value = precoUnit.toLocaleString('pt-BR', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
        valorField.value = valorTotal.toLocaleString('pt-BR', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
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
        let temDesconto = selectedText.toUpperCase().includes("VISTA");

        let totalBrutoCents = 0;
        let totalCaixaSoma = 0;
        let totalKgSoma = 0;

        document.querySelectorAll(".linha_1").forEach((linha) => {
            if (linha.style.display === 'none') return;
            const q = parseFloat(linha.querySelector(".quant_caixa_1").value.replace(',', '.')) || 0;
            const t = parseFloat(linha.querySelector(".tipo_cx_1").value.replace(',', '.')) || 0;
            const v = Math.round(parseBrasil(linha.querySelector(".valor_1").value) * 100);
            
            totalCaixaSoma += q;
            totalBrutoCents += v;
            totalKgSoma += (q * t);
        });

        let descontoCents = 0;
        if (temDesconto) {
            const dPerc = parseFloat(descAvistaField.value.replace(',', '.')) || 0;
            if (dPerc <= 0) {
                totalGeralField.innerHTML = "DESC. OBRIGATÓRIO";
                totalGeralField.classList.add("danger");
            } else {
                totalGeralField.classList.remove("danger");
                descontoCents = Math.round(totalBrutoCents * (dPerc / 100));
            }
        } else {
            totalGeralField.classList.remove("danger");
        }

        let totalGeralCents = totalBrutoCents - descontoCents;

        totalCaixaField.innerHTML = Math.round(totalCaixaSoma) + " CXS";
        totalKgField.innerHTML = totalKgSoma.toLocaleString('pt-BR', { minimumFractionDigits: 2 });
        totalBrutoField.innerHTML = "R$ " + (totalBrutoCents / 100).toLocaleString('pt-BR', { minimumFractionDigits: 2 });
        totalDescField.innerHTML = "R$ " + (descontoCents / 100).toLocaleString('pt-BR', { minimumFractionDigits: 2 });

        if (!totalGeralField.classList.contains("danger")) {
            const finalStr = (totalGeralCents / 100).toLocaleString('pt-BR', { minimumFractionDigits: 2 });
            totalGeralField.innerHTML = finalStr;
            inputHidden.value = finalStr;
        }
    } catch (e) { }
    calcularTotalAbatimentos();
}

function calcularTotalAbatimentos() {
    const totals = getTotals();
    let somaAbatimentosCents = 0;

    document.querySelectorAll(".linha-abatimentos").forEach(linha => {
        const inputDesc = linha.querySelector('input[id^="desc_"]');
        const selInfo = linha.querySelector('select[name^="info_"], input[name^="info_"]');
        const selPreco = linha.querySelector('select[name^="preco_unit_"]');
        const campoResultado = linha.querySelector('input[name^="valor_"]');

        if (!inputDesc || !selInfo || !selPreco || !campoResultado) return;

        const desc = inputDesc.value.toUpperCase().trim();
        const infoVal = selInfo.value.toLowerCase().trim();
        const precoRaw = selPreco.value;
        let pUnit = parseFloat(precoRaw.replace(/\./g, '').replace(',', '.').replace('%', '').trim()) || 0;
        
        let valorCalculadoCents = 0;

        if (desc === 'TAXA ADM') {
            let taxaInput = parseFloat(infoVal.replace(',', '.')) || 0;
            valorCalculadoCents = Math.round((taxaInput * pUnit) * 100);
        } else if (desc === 'FUNRURAL') {
            let base = infoVal.includes('bruto') ? totals.bruto : totals.liquido;
            valorCalculadoCents = Math.round((base * 100) * (pUnit / 100));
        } else {
            if (infoVal === 'kg') {
                valorCalculadoCents = Math.round((pUnit * totals.kg) * 100);
            } else if (infoVal === 'cx' || infoVal === 'caixa' || infoVal === 'cxs') {
                valorCalculadoCents = Math.round((pUnit * totals.caixas) * 100);
            } else {
                if (precoRaw.includes('%')) {
                    valorCalculadoCents = Math.round((totals.bruto * 100) * (pUnit / 100));
                } else {
                    valorCalculadoCents = Math.round(pUnit * 100);
                }
            }
        }

        campoResultado.value = (valorCalculadoCents / 100).toLocaleString('pt-BR', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
        somaAbatimentosCents += valorCalculadoCents;
    });

    const totalComissaoField = document.getElementById("total_comissao");
    if (totalComissaoField) totalComissaoField.textContent = (somaAbatimentosCents / 100).toLocaleString('pt-BR', { minimumFractionDigits: 2 });

    updateLiquidPayable();
}

function parseBrasil(str) {
    if (!str) return 0;
    str = str.replace(/[^\d,]/g, '').replace(',', '.');
    return parseFloat(str) || 0;
}

function getTotals() {
    // Busca o texto, remove "R$", espaços e pontos de milhar, troca vírgula por ponto
    const getVal = (selector) => {
        const el = document.querySelector(selector);
        if (!el) return 0;
        let txt = el.textContent || el.value || "0";
        return parseFloat(txt.replace('R$', '').replace(/\./g, '').replace(',', '.').trim()) || 0;
    };

    return {
        bruto: getVal('#total_bruto'),
        liquido: getVal('#valor_liquido'),
        caixas: parseInt(document.querySelector('#total_caixa')?.textContent.replace(/\D/g, '') || '0', 10),
        kg: getVal('#total_kg')
    };
}

function addDiscountLine() {
    const tpl = document.getElementById("discount-template");
    const container = document.getElementById("discount-container");
    const newLine = tpl.cloneNode(true);
    newLine.style.display = "block";
    newLine.id = "";
    newLine.querySelector('.desconto-type').addEventListener('change', calcularDescontosDiversos);
    newLine.querySelector('.desconto-valor').addEventListener('input', function (e) {
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
    const liquidBaseCents = Math.round(parseBrasil(document.getElementById('valor_liquido').value) * 100);
    const comissaoCents = Math.round(parseBrasil(document.getElementById('total_comissao').textContent) * 100);
    const descontosCents = Math.round(parseBrasil(document.getElementById('total_descontos_diversos').textContent) * 100);
    
    const finalLiquidoCents = liquidBaseCents - comissaoCents + descontosCents;
    
    document.getElementById('total_liquido_pagar').textContent = (finalLiquidoCents / 100).toLocaleString('pt-BR', { minimumFractionDigits: 2 });
}