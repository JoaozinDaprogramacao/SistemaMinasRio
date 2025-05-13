

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

function mascara_moeda(el) {
  // 1) pega só dígitos
  let digits = el.value.replace(/\D/g, "");
  // 2) remove zeros extras no início (mas deixa um "0" se for todo zero)
  digits = digits.replace(/^0+(?=\d)/, "");

  // 3) separa parte inteira e centavos
  let intPart, centPart;
  if (digits.length <= 2) {
    intPart  = "0";
    centPart = digits.padStart(2, "0");
  } else {
    intPart  = digits.slice(0, -2);
    centPart = digits.slice(-2);
  }

  // 4) adiciona ponto de milhares na parte inteira
  intPart = intPart.replace(/(\d)(?=(\d{3})+(?!\d))/g, "$1.");

  const formatted = intPart + "," + centPart;
  el.value = formatted;

  // mantém o cursor no fim do campo
  el.selectionStart = el.selectionEnd = formatted.length;
}




function calcularValores(linha) {
    // Obter os campos da linha fornecida
    const precoKgField = linha.querySelector(".preco_kg_1");
    const tipoCxField = linha.querySelector(".tipo_cx_1");
    const quantCxField = linha.querySelector(".quant_caixa_1");
    const precoUnitField = linha.querySelector(".preco_unit_1");
    const valorField = linha.querySelector(".valor_1");

    try {
        // Extrair valores e converter para números corretamente
        let precoKgValor = precoKgField.value.trim();
        // Garantir que temos um valor numérico válido
        precoKgValor = precoKgValor.replace(/[^\d,\.]/g, '');
        // Substituir vírgula por ponto para cálculos
        if (precoKgValor.indexOf(',') !== -1) {
            precoKgValor = precoKgValor.replace(',', '.');
        }
        
        const precoKg = parseFloat(precoKgValor || '0');
        
        // Obter valor de tipo_cx evitando valores inválidos
        let tipoCxValor = tipoCxField.value.trim();
        if (tipoCxValor.indexOf(',') !== -1) {
            tipoCxValor = tipoCxValor.replace(',', '.');
        }
        const tipoCx = parseFloat(tipoCxValor || '0');
        
        // Obter quantidade com validação
        const quantCx = parseInt(quantCxField.value || '0', 10);

        // Validar valores antes de calcular
        if (isNaN(precoKg) || isNaN(tipoCx) || isNaN(quantCx)) {
            console.error("Valores inválidos para cálculo:", { precoKg, tipoCx, quantCx });
            // Definir valores padrão em caso de erro
            precoUnitField.value = "0,00";
            valorField.value = "0,00";
            return;
        }

        // Calcular o preço unitário e o valor total
        const precoUnit = precoKg * tipoCx;
        const valorTotal = precoUnit * quantCx;

        // Atualizar os campos na linha com formatação correta
        precoUnitField.value = precoUnit.toFixed(2).replace(".", ",");
        valorField.value = valorTotal.toFixed(2).replace(".", ",");
    } catch (e) {
        console.error("Erro ao calcular valores:", e);
        // Definir valores padrão em caso de erro
        precoUnitField.value = "0,00";
        valorField.value = "0,00";
    }

    // Recalcular totais
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

function calculaTotais() {
    try {
        const totalCaixaField = document.querySelector("#total_caixa");
        const totalKgField = document.querySelector("#total_kg");
        const totalBrutoField = document.querySelector("#total_bruto");
        const descAvistaField = document.querySelector("#desc-avista");
        const totalDescField = document.querySelector("#total-desc");
        const totalGeralField = document.querySelector("#total-geral");
        const inputHidden = document.getElementById('valor_liquido');

        // Verificar se o plano de pagamento é à vista
        const planoPgto = document.querySelector("#plano_pgto");
        let selectedText = planoPgto.options[planoPgto.selectedIndex]?.text || "";
        selectedText = selectedText.toUpperCase();
        let desconto = selectedText.includes("VISTA");

        // Inicializar totais
        let totalBrutoSoma = 0;
        let totalCaixaSoma = 0;
        let totalKgSoma = 0;
        let totalDescSoma = 0;
        let totalGeralSoma = 0;

        // Calcular totais de todas as linhas
        const linhas = document.querySelectorAll(".linha_1");
        
        // Somar valores de todas as linhas visíveis
        linhas.forEach((linha, index) => {
            // Pular linhas que estão com display:none
            if (linha.style.display === 'none') return;
            
            const quantCaixaInput = linha.querySelector(".quant_caixa_1");
            const tipoCaixaInput = linha.querySelector(".tipo_cx_1");
            const totalInput = linha.querySelector(".valor_1");

            if (quantCaixaInput && tipoCaixaInput && totalInput) {
                // Extrair e converter valores
                let quantCaixaStr = quantCaixaInput.value.trim();
                let tipoCaixaStr = tipoCaixaInput.value.trim();
                let totalStr = totalInput.value.trim();
                
                // Converter para formato de cálculo (ponto decimal)
                quantCaixaStr = quantCaixaStr.replace(/[^\d,\.]/g, '').replace(',', '.');
                tipoCaixaStr = tipoCaixaStr.replace(/[^\d,\.]/g, '').replace(',', '.');
                totalStr = totalStr.replace(/[^\d,\.]/g, '').replace(',', '.');
                
                // Converter para números
                const quantCaixa = parseFloat(quantCaixaStr) || 0;
                const tipoCaixa = parseFloat(tipoCaixaStr) || 0;
                const totalValor = parseFloat(totalStr) || 0;

                // Atualizar totais
                totalCaixaSoma += quantCaixa;
                totalBrutoSoma += totalValor;
                totalKgSoma += (quantCaixa * tipoCaixa);
            }
        });

        // Calcular desconto à vista se aplicável
        let descontoAvista = 0;
        if (desconto) {
            const descontoStr = descAvistaField.value.trim().replace(/[^\d,\.]/g, '').replace(',', '.');
            const descontoPercentual = parseFloat(descontoStr) || 0;
            
            if (descontoPercentual <= 0) {
                totalGeralField.innerHTML = "DESC. OBRIGATÓRIO";
                totalGeralField.classList.add("danger");
            } else {
                totalGeralField.classList.remove("danger");
                descontoAvista = (totalBrutoSoma * (descontoPercentual / 100));
            }
        } else {
            totalGeralField.classList.remove("danger");
        }
        
        // Calcular o total após todos os descontos
        totalDescSoma = descontoAvista;
        totalGeralSoma = totalBrutoSoma - totalDescSoma;

        // Formatar valores para exibição
        const formatarValor = (valor) => valor.toFixed(2).replace('.', ',');
        
        // Atualizar os campos com os totais formatados
        totalCaixaField.innerHTML = Math.round(totalCaixaSoma) + " CXS";
        totalKgField.innerHTML = formatarValor(totalKgSoma) + " KG";
        totalBrutoField.innerHTML = "R$ " + formatarValor(totalBrutoSoma);
        totalDescField.innerHTML = "R$ " + formatarValor(totalDescSoma);
        
        if (!totalGeralField.classList.contains("danger")) {
            totalGeralField.innerHTML = formatarValor(totalGeralSoma);
            inputHidden.value = formatarValor(totalGeralSoma);
        }
    } catch (e) {
        console.error("Erro ao calcular totais:", e);
    }
}


function calculaTotais2() {
  // 1) acumula comissão
  const totalComissaoField = document.getElementById("total_comissao");
  let soma = 0;
  document.querySelectorAll(".linha_2 .valor_2").forEach(input => {
    soma += parseFloat(input.value.replace(",", ".")) || 0;
  });
  totalComissaoField.textContent = soma.toFixed(2).replace(".", ",");

  // 2) atualiza o cálculo geral de carga (bruto, líquido, etc.)
  if (typeof calculaTotais === "function") {
    calculaTotais();
  }

  // 3) atualiza o Total Líquido a Pagar (caso tenha implementado)
  if (typeof updateLiquidPayable === "function") {
    updateLiquidPayable();
  }
}


document.addEventListener("DOMContentLoaded", function() {
    // Garantir que os campos decimais sejam formatados corretamente antes do envio
    document.getElementById("form-romaneio").addEventListener("submit", function(e) {
        try {
            // Formatar campos de desconto
            formatarCampoDecimal("desc-funrural");
            formatarCampoDecimal("desc-ima");
            
            // Formatar valores em todos os campos com máscara decimal
            const camposDecimais = document.querySelectorAll('input[onkeyup*="mascara_decimal"]');
            camposDecimais.forEach(function(campo) {
                const idCampo = campo.id;
                if (idCampo) {
                    formatarCampoDecimal(idCampo);
                }
            });
            
            // Formatar todos os valores nas linhas de produtos
            const linhas = document.querySelectorAll(".linha_1:not([style*='display: none'])");
            linhas.forEach(function(linha) {
                const precoKgField = linha.querySelector(".preco_kg_1");
                const precoUnitField = linha.querySelector(".preco_unit_1");
                const valorField = linha.querySelector(".valor_1");
                
                if (precoKgField && precoKgField.value) {
                    precoKgField.value = formatarNumero(precoKgField.value);
                }
                
                if (precoUnitField && precoUnitField.value) {
                    precoUnitField.value = formatarNumero(precoUnitField.value);
                }
                
                if (valorField && valorField.value) {
                    valorField.value = formatarNumero(valorField.value);
                }
            });
            
            console.log("Formulário formatado com sucesso antes do envio");
        } catch (error) {
            console.error("Erro ao formatar campos antes do envio:", error);
        }
    });
    
    function formatarCampoDecimal(idCampo) {
        const campo = document.getElementById(idCampo);
        if (campo && campo.value) {
            // Normalizar valor para cálculos
            let valor = campo.value.trim();
            
            // Remover caracteres não numéricos exceto vírgula e ponto
            valor = valor.replace(/[^\d,.]/g, '');
            
            // Substituir vírgula por ponto
            if (valor.indexOf(',') !== -1) {
                valor = valor.replace(',', '.');
            }
            
            // Converter para número e voltar para string formatada
            const numero = parseFloat(valor);
            if (!isNaN(numero)) {
                campo.value = numero.toFixed(2).replace('.', ',');
            } else {
                campo.value = "0,00";
            }
        }
    }
    
    function formatarNumero(valor) {
        if (!valor) return "0,00";
        
        // Limpar o valor
        valor = valor.toString().trim().replace(/[^\d,.]/g, '');
        
        // Converter vírgula para ponto
        if (valor.indexOf(',') !== -1) {
            valor = valor.replace(',', '.');
        }
        
        // Converter para número
        const numero = parseFloat(valor);
        
        // Retornar formatado
        return isNaN(numero) ? "0,00" : numero.toFixed(2).replace('.', ',');
    }
});
// ——————— Helpers ———————
// Converte "1.234,56" ou "1234,56" → Number
function parseBrasil(str) {
  if (!str) return 0;
  // tira tudo que não for dígito ou vírgula
  str = str.replace(/[^0-9,]/g, '');
  // remove pontos de milhar (se houver)
  str = str.replace(/\./g, '');
  // vírgula vira ponto
  str = str.replace(',', '.');
  return parseFloat(str) || 0;
}

// Formata Number → "0,00"
function fmt(num) {
  return num.toFixed(2).replace('.', ',');
}

// Lê totais da tela: #total_bruto, #valor_liquido, #total_caixa, #total_kg
function getTotals() {
  return {
    bruto:   parseBrasil(document.querySelector('#total_bruto')?.textContent || ''),
    liquido: parseBrasil(document.getElementById('valor_liquido')?.value || ''),
    caixas:  parseInt((document.querySelector('#total_caixa')?.textContent || '').replace(/\D/g,''), 10) || 0,
    kg:      parseBrasil((document.querySelector('#total_kg')?.textContent || '').replace(/[^0-9,]/g,'')),
  };
}

// Lê valor puro de um <select> ou <input> (usa ponto decimal)
function getNum(id) {
  const el = document.getElementById(id);
  if (!el) return 0;
  return parseFloat(el.value.replace(',', '.')) || 0;
}


// ——————— FUNRURAL ———————
function calcularTaxaFunrural() {
  const { bruto, liquido } = getTotals();
  const info = document.getElementById('info_funrural')?.value;
  
  // pct em decimal (1.50 → 0.015)
  const pct = getNum('preco_unit_funrural') / 100;
  const base = info === 'bruto' ? bruto : info === 'liquido' ? liquido : 0;
  
  document.getElementById('valor_funrural').value = fmt(base * pct);
  if (typeof calculaTotais2 === 'function') calculaTotais2();
}


// ——————— IMA ———————
function calcularTaxaIma() {
  // Leitura dos totais
  const { caixas } = getTotals();
  const info = document.getElementById('info_ima')?.value;
  
  // Preço unitário puro (por caixa)
  const pu = parseFloat(
    document.getElementById('preco_unit_ima')?.value
      .replace(',', '.')
  ) || 0;
  
  // Se for “por caixa”, multiplica direto; se for “1”, só usa o valor puro
  let valor = 0;
  if (info === 'cx') {
    valor = pu * caixas;
  } else if (info === 'um') {
    valor = pu;
  }
  
  // Atualiza campo e total geral
  document.getElementById('valor_ima').value = fmt(valor);
  if (typeof calculaTotais2 === 'function') calculaTotais2();
}


// ——————— ABANORTE ———————
function calcularTaxaAbanorte() {
  // 1) pega total de KG da tela
  const { kg } = getTotals();

  // 2) lê se é por KG ou unidade
  const info = document.getElementById('info_abanorte')?.value;

  // 3) lê o preço unitário puro (ex.: 52.8 → R$ 52,80 por KG)
  const pu = parseFloat(
    document.getElementById('preco_unit_abanorte')?.value
      .replace(',', '.')
  ) || 0;

  // 4) calcula: se for por KG, multiplica por total de KG; se for “1”, só usa PU
  let valor = 0;
  if (info === 'kg') {
    valor = pu * kg;
  } else if (info === 'um') {
    valor = pu;
  }

  // 5) preenche o campo e atualiza o total geral
  document.getElementById('valor_abanorte').value = fmt(valor);
  if (typeof calculaTotais2 === 'function') calculaTotais2();
}



// ——————— TAXA ADM ———————
function calcularTaxaAdm() {
  // 1) Lê a taxa digitada (ex.: "5,00" → 5)
  const taxaStr = document
    .getElementById('taxa_adm_percent')
    .value
    .replace(/\./g, '')   // remove pontos de milhar
    .replace(',', '.');   // vírgula → ponto
  const taxaNum = parseFloat(taxaStr) || 0;

  // 2) Lê o preço unitário selecionado (ex.: "5" → 5)
  const puStr = document
    .getElementById('preco_unit_taxa_adm')
    .value
    .replace(',', '.');   
  const puNum = parseFloat(puStr) || 0;

  // 3) Multiplica direto: 5 × 5 = 25
  const resultado = taxaNum * puNum;

  // 4) Formata e escreve no campo
  document.getElementById('valor_taxa_adm').value =
    resultado.toFixed(2).replace('.', ',');

  // 5) Atualiza o total geral
  if (typeof calculaTotais2 === 'function') calculaTotais2();
}

// === Inicializa com uma linha vazia ===
document.addEventListener("DOMContentLoaded", () => {
  addDiscountLine();
});

// Cria uma nova linha de desconto
function addDiscountLine() {
  const tpl = document.getElementById("discount-template");
  const container = document.getElementById("discount-container");
  const newLine = tpl.cloneNode(true);
  newLine.style.display = "block";
  newLine.id = ""; 

  // Anexa handlers sem usar atributos inline
  const tipoEl  = newLine.querySelector('.desconto-type');
  const valorEl = newLine.querySelector('.desconto-valor');
  tipoEl.addEventListener('change', calcularDescontosDiversos);
  valorEl.addEventListener('input', function(e){
    mascara_moeda(e.target);
    calcularDescontosDiversos();
  });
  container.appendChild(newLine);

  // e já roda uma vez para atualizar a soma
  calcularDescontosDiversos();
}


// Remove uma linha de desconto
function removeDiscountLine(btn) {
  const linha = btn.closest(".linha_3");
  linha.remove();
  calcularDescontosDiversos();
}

// Formata número para "0,00"
function fmtBrasil(num) {
  return num.toFixed(2).replace(".", ",");
}

// Recalcula o total de descontos diversos
function calcularDescontosDiversos() {
  const linhas = document.querySelectorAll('#discount-container .linha_3');
  let total = 0;

  linhas.forEach(linha => {
    const tipo = linha.querySelector('.desconto-type').value;  // '+' ou '-'
    let txt = linha.querySelector('.desconto-valor').value || '0';

    // 1) mantém só dígitos, vírgula e sinal de menos
    txt = txt.replace(/[^0-9\-,]/g, '');

    // 2) remove todos os pontos de milhar (ex.: "1.234,56" → "1234,56")
    txt = txt.replace(/\./g, '');

    // 3) transforma vírgulas em ponto (ex.: "1234,56" → "1234.56")
    txt = txt.replace(/,/g, '.');

    const valor = parseFloat(txt) || 0;
    total += (tipo === '+' ? valor : -valor);
  });

  // 4) atualiza o campo com formatação pt-BR
  const out = document.getElementById('total_descontos_diversos');
  out.textContent = total
    .toFixed(2)       // duas casas
    .replace('.', ','); // vírgula decimal

  // Se você tiver uma rotina de recálculo geral, chama de novo:
  if (typeof calculaTotais === 'function') {
    calculaTotais();
  }
}



function parseBrasilComSinal(str) {
  if (!str) return 0;
  // detecta sinal
  const negativo = str.trim().startsWith("-");
  // remove tudo que não é dígito ou vírgula
  let s = str.replace(/[^0-9,]/g, "");
  s = s.replace(",", ".");
  let num = parseFloat(s) || 0;
  return negativo ? -num : num;
}


// —––––––––––––––––––––––––––––––––––––––––––––
// Atualiza o Total Líquido a Pagar
function updateLiquidPayable() {
  // 1) Base líquida da carga (já com descontos de produtos)
  const liquidBase = parseBrasil(
    document.getElementById('valor_liquido').value
  );

  // 2) Total de comissões
  const comissao = parseBrasil(
    document.getElementById('total_comissao').textContent
  );

  // 3) Total de descontos diversos
  const descontos = parseBrasilComSinal(
    document.getElementById('total_descontos_diversos').textContent
  );  

  // 4) Cálculo final
  const finalLiquido = liquidBase - comissao + descontos;

  // 5) Preenche no formulário
  document.getElementById('total_liquido_pagar').textContent =
    fmt(finalLiquido);
}

// —––––––––––––––––––––––––––––––––––––––––––––
// Dispare sempre que comissão mudar:
const originalCalculaTotais2 = window.calculaTotais2;
window.calculaTotais2 = function(...args) {
  originalCalculaTotais2?.apply(this, args);
  updateLiquidPayable();
};

// —––––––––––––––––––––––––––––––––––––––––––––
// Dispare sempre que descontos diversos mudarem:
const originalCalcDescontos = window.calcularDescontosDiversos;
window.calcularDescontosDiversos = function(...args) {
  originalCalcDescontos?.apply(this, args);
  updateLiquidPayable();
};

// —––––––––––––––––––––––––––––––––––––––––––––
// E também no cálculo inicial de totais de produtos
const originalCalculaTotais = window.calculaTotais;
window.calculaTotais = function(...args) {
  originalCalculaTotais?.apply(this, args);
  updateLiquidPayable();
};


/**
 * Chame esta função passando o id do romaneio:
 *   mostrar(123);
 */