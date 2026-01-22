function listar() {
    var dataInicial = $('#dataInicial').val();
    var dataFinal = $('#dataFinal').val();
    var cliente = $('#cliente').val();

    $('#dataInicialRel').val(dataInicial);
    $('#dataFinalRel').val(dataFinal);
    $('#clienteRel').val(cliente);

    $.ajax({
        url: 'paginas/' + pag + '/listar.php',
        method: 'POST',
        data: { dataInicial, dataFinal, cliente },
        dataType: "html",
        success: function (result) {
            $("#listar").html(result);
        }
    });

    $.ajax({
        url: 'paginas/' + pag + '/listar-resumo.php',
        method: 'POST',
        data: { dataInicial, dataFinal, cliente },
        dataType: "html",
        success: function (result) {
            $("#listar-resumo").html(result);
        }
    });
}

function buscar() {
    listar();
}

window.mockRomaneio = {
    id: 151,
    data: "2026-01-17 00:00:00",
    nome_cliente: "adqeDA",
    nota_fiscal: "32132",
    nome_plano: null,
    vencimento: "2026-01-21 00:00:00",
};

window.mockLinhaComissoes = [
    {
        id: 59,
        descricao: "8",
        quant_caixa: 501,
        preco_kg: 0.35,
        tipo_caixa: 7,
        preco_unit: 5.25,
        valor: 2630.25,
        id_romaneio: 151,
    },
    {
        id: 60,
        descricao: "8",
        quant_caixa: 240,
        preco_kg: 0.35,
        tipo_caixa: 6,
        preco_unit: 7.0,
        valor: 1680.0,
        id_romaneio: 151,
    },
];

window.mockMateriaisUsados = [
    {
        id: 101,
        observacoes: "Uso no carregamento",
        descricao: 3,
        quant: 2,
        preco_unit: 12.5,
        valor: 25.0,
        id_romaneio: 151,
    },
    {
        id: 102,
        observacoes: "Reposição",
        descricao: 3,
        quant: 1,
        preco_unit: 12.5,
        valor: 12.5,
        id_romaneio: 151,
    },
    {
        id: 103,
        observacoes: "Outro material que não quero mostrar agora",
        descricao: 5,
        quant: 1,
        preco_unit: 30.0,
        valor: 30.0,
        id_romaneio: 151,
    },
];

window.mockMateriaisCatalogo = [
    { id: 3, nome: "FITA" },
    { id: 5, nome: "SACO" },
];

window.mockMaterialSelecionadoId = 3;

function parseMySQLDateTime(dt) {
    if (!dt) return null;
    const iso = String(dt).replace(" ", "T");
    const d = new Date(iso);
    return isNaN(d.getTime()) ? null : d;
}

function moneyBR(v) {
    const n = Number(v || 0);
    return n.toLocaleString("pt-BR", { minimumFractionDigits: 2 });
}

function getMaterialNomeById(idMaterial) {
    const cat = window.mockMateriaisCatalogo || [];
    const found = cat.find((m) => Number(m.id) === Number(idMaterial));
    return found ? found.nome : "MATERIAL";
}

function abrirModalRomaneioMock() {
    const rom = window.mockRomaneio || {};

    $("#id_dados").text(rom.id ?? "-");
    const dataRom = parseMySQLDateTime(rom.data);
    $("#data_dados").text(dataRom ? dataRom.toLocaleDateString("pt-BR") : "-");
    $("#cliente_dados").text(rom.nome_cliente || "-");
    $("#nota_fiscal_dados").text(rom.nota_fiscal || "-");
    $("#plano_pgto_dados").text(rom.nome_plano || "A Vista");
    const venc = parseMySQLDateTime(rom.vencimento);
    $("#vencimento_dados").text(venc ? venc.toLocaleDateString("pt-BR") : "-");

    const linhasCom = window.mockLinhaComissoes || [];
    let totalComissoes = 0;
    let htmlCom = `
      <table class="table table-striped table-sm">
        <thead>
          <tr>
            <th>Descrição</th>
            <th class="text-center">Qtd Caixas</th>
            <th class="text-center">Preço Kg</th>
            <th class="text-center">Tipo Caixa</th>
            <th class="text-center">Preço Unit</th>
            <th class="text-center">Valor Total</th>
          </tr>
        </thead>
        <tbody>
    `;

    if (linhasCom.length > 0) {
        linhasCom.forEach((c) => {
            const valor = Number(c.valor || 0);
            totalComissoes += valor;
            htmlCom += `
              <tr>
                <td>${c.descricao ?? "-"}</td>
                <td class="text-center">${c.quant_caixa ?? 0}</td>
                <td class="text-center">R$ ${moneyBR(c.preco_kg)}</td>
                <td class="text-center">${c.tipo_caixa ?? "-"}</td>
                <td class="text-center">R$ ${moneyBR(c.preco_unit)}</td>
                <td class="text-center font-weight-bold">R$ ${moneyBR(valor)}</td>
              </tr>
            `;
        });
        htmlCom += `
          <tr style="background:#e8f5e9" class="font-weight-bold">
            <td colspan="5" class="text-right text-uppercase">Subtotal Comissões:</td>
            <td class="text-center text-success">R$ ${moneyBR(totalComissoes)}</td>
          </tr>
        `;
    } else {
        htmlCom += `<tr><td colspan="6" class="text-center">Nenhuma comissão encontrada.</td></tr>`;
    }
    htmlCom += `</tbody></table>`;
    $("#itens_dados").html(htmlCom);

    const materiais = window.mockMateriaisUsados || [];
    const idMaterial = window.mockMaterialSelecionadoId;
    const materiaisFiltrados = materiais.filter(m => Number(m.descricao) === Number(idMaterial));
    let totalMateriais = 0;
    let htmlMat = "";

    if (materiaisFiltrados.length > 0) {
        materiaisFiltrados.forEach((m) => {
            const nomeMaterial = getMaterialNomeById(m.descricao);
            const valor = Number(m.valor || 0);
            totalMateriais += valor;
            htmlMat += `
              <tr>
                <td class="text-left font-weight-bold" style="color:#1e5600">${nomeMaterial}</td>
                <td class="text-left">${m.observacoes || "-"}</td>
                <td class="text-center">${m.quant ?? 0}</td>
                <td class="text-center">R$ ${moneyBR(m.preco_unit)}</td>
                <td class="text-center font-weight-bold">R$ ${moneyBR(valor)}</td>
              </tr>
            `;
        });
        htmlMat += `
          <tr style="background:#fff3e0" class="font-weight-bold">
            <td colspan="4" class="text-right text-uppercase">Subtotal Materiais:</td>
            <td class="text-center text-warning">R$ ${moneyBR(totalMateriais)}</td>
          </tr>
        `;
    } else {
        htmlMat = `<tr><td colspan="5" class="text-center">Nenhum material encontrado.</td></tr>`;
    }
    $("#corpo_materiais_detalhado").html(htmlMat);

    const totalComissaoMaterial = totalComissoes + totalMateriais;
    const totalBananaLiquido = totalComissoes;
    const adicional = 0;
    const desconto = 0;
    const totalLiquidoAReceber = totalComissaoMaterial + adicional - desconto;

    let htmlResumo = `
        <td class="text-center">R$ ${moneyBR(totalMateriais)}</td>
        <td class="text-center">R$ ${moneyBR(totalComissaoMaterial)}</td>
        <td class="text-center">R$ ${moneyBR(totalBananaLiquido)}</td>
        <td class="text-center" style="background-color: #e2efda; color: #1e5600;">R$ ${moneyBR(totalLiquidoAReceber)}</td>
    `;
    $("#resumo_consolidado_dados").html(htmlResumo);

    $("#adicional_dados").text("R$ " + moneyBR(adicional));
    $("#desconto_dados").text("R$ " + moneyBR(desconto));
    $("#total_liquido_dados_footer").text("R$ " + moneyBR(totalLiquidoAReceber));

    $("#modalDados").modal("show");
}


window.abrirModalRomaneioMock = abrirModalRomaneioMock;

function mostrar(id) {
    $.ajax({
        url: 'paginas/' + pag + '/buscar_dados.php',
        method: 'POST',
        data: { id },
        dataType: "json",
        success: function (dados) {
            if (dados.error) {
                alert(dados.error);
                return;
            }

            const rom = dados.romaneio || {};
            const comissoes = dados.comissoes || [];
            const materiais = dados.materiais || [];

            // 1. CABEÇALHO
            $("#id_dados").text(rom.id ?? "-");
            const dataRom = parseMySQLDateTime(rom.data);
            $("#data_dados").text(dataRom ? dataRom.toLocaleDateString("pt-BR") : "-");
            $("#cliente_dados").text(rom.nome_cliente || "-");
            $("#nota_fiscal_dados").text(rom.nota_fiscal || "-");
            $("#plano_pgto_dados").text(rom.nome_plano || "A Vista");
            const venc = parseMySQLDateTime(rom.vencimento);
            $("#vencimento_dados").text(venc ? venc.toLocaleDateString("pt-BR") : "-");

            // 2. TABELA DE COMISSÕES
            let totalComissoes = 0;
            let htmlCom = `
                <table class="table table-striped table-sm">
                    <thead>
                        <tr>
                            <th>Descrição</th>
                            <th class="text-center">Qtd Caixas</th>
                            <th class="text-center">Preço Kg</th>
                            <th class="text-center">Tipo Caixa</th>
                            <th class="text-center">Preço Unit</th>
                            <th class="text-center">Valor Total</th>
                        </tr>
                    </thead>
                    <tbody>`;

            if (comissoes.length > 0) {
                comissoes.forEach((c) => {
                    const valor = Number(c.valor || 0);
                    totalComissoes += valor;
                    htmlCom += `
                        <tr>
                            <td>${c.nome_comissao ?? "-"}</td>
                            <td class="text-center">${c.quant_caixa ?? 0}</td>
                            <td class="text-center">R$ ${moneyBR(c.preco_kg)}</td>
                            <td class="text-center">${c.peso_caixa ?? "-"}</td>
                            <td class="text-center">R$ ${moneyBR(c.preco_unit)}</td>
                            <td class="text-center font-weight-bold">R$ ${moneyBR(valor)}</td>
                        </tr>`;
                });
                htmlCom += `
                    <tr style="background:#e8f5e9" class="font-weight-bold">
                        <td colspan="5" class="text-right text-uppercase">Subtotal Comissões:</td>
                        <td class="text-center text-success">R$ ${moneyBR(totalComissoes)}</td>
                    </tr>`;
            } else {
                htmlCom += `<tr><td colspan="6" class="text-center">Nenhuma comissão encontrada.</td></tr>`;
            }
            htmlCom += `</tbody></table>`;
            $("#itens_dados").html(htmlCom);

            // 3. TABELA DE MATERIAIS
            let totalMateriais = 0;
            let htmlMat = "";

            if (materiais.length > 0) {
                materiais.forEach((m) => {
                    const valor = Number(m.valor || 0);
                    totalMateriais += valor;
                    htmlMat += `
                        <tr>
                            <td class="text-left font-weight-bold" style="color:#1e5600">${m.nome_material}</td>
                            <td class="text-left">${m.observacoes || "-"}</td>
                            <td class="text-center">${m.quant ?? 0}</td>
                            <td class="text-center">R$ ${moneyBR(m.preco_unit)}</td>
                            <td class="text-center font-weight-bold">R$ ${moneyBR(valor)}</td>
                        </tr>`;
                });
                htmlMat += `
                    <tr style="background:#fff3e0" class="font-weight-bold">
                        <td colspan="4" class="text-right text-uppercase">Subtotal Materiais:</td>
                        <td class="text-center text-warning">R$ ${moneyBR(totalMateriais)}</td>
                    </tr>`;
            } else {
                htmlMat = `<tr><td colspan="5" class="text-center">Nenhum material encontrado.</td></tr>`;
            }
            $("#corpo_materiais_detalhado").html(htmlMat);

            // 4. RESUMO CONSOLIDADO E CÁLCULOS
            const totalComissaoMaterial = totalComissoes + totalMateriais;
            const totalBananaLiquido = totalComissoes; // Definição de Banana Líquido
            const adicional = Number(rom.adicional || 0);
            const desconto = Number(rom.desconto || 0);
            const totalLiquidoAReceber = totalComissaoMaterial + adicional - desconto;

            let htmlResumo = `
                <td class="text-center text-danger">R$ ${moneyBR(totalMateriais)}</td>
                <td class="text-center">R$ ${moneyBR(totalComissaoMaterial)}</td>
                <td class="text-center text-primary">R$ ${moneyBR(totalBananaLiquido)}</td>
                <td class="text-center text-success" style="background-color: #e2efda; font-size:1.1rem">R$ ${moneyBR(totalLiquidoAReceber)}</td>
            `;
            $("#resumo_consolidado_dados").html(htmlResumo);

            // 5. RODAPÉ DE AJUSTES
            $("#adicional_dados").text("R$ " + moneyBR(adicional));
            $("#descricao_a_dados").text(rom.obs_adicional || "Adicional");
            $("#desconto_dados").text("R$ " + moneyBR(desconto));
            $("#descricao_d_dados").text(rom.obs_desconto || "Desconto");
            $("#total_liquido_dados_footer").text("R$ " + moneyBR(totalLiquidoAReceber));

            $("#modalDados").modal("show");
        },
        error: function () {
            alert("Erro ao buscar dados do servidor.");
        }
    });
}

function limparFiltros() {
    $('#cliente').val('').trigger('change.select2');
    $('#cliente').val('');
    $('#dataInicial').val('');
    $('#dataFinal').val('');
    listar();
}
