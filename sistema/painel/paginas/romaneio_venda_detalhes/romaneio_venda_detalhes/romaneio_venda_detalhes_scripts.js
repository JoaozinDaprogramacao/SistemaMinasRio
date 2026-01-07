function mostrar(id) {
    $.ajax({
        url: 'paginas/romaneio_venda_detalhes/buscar_dados.php',
        method: 'POST',
        data: {
            id: id
        },
        dataType: 'json',
        success: function (dados) {
            console.log("DADOS COMPLETOS:", dados);

            // Cabeçalho Principal
            $('#id_dados').text(dados.romaneio.id);

            let dataFormatada = new Date(dados.romaneio.data).toLocaleDateString('pt-BR');
            let vencimentoFormatado = new Date(dados.romaneio.vencimento).toLocaleDateString('pt-BR');

            $('#data_dados').text(dataFormatada);
            $('#cliente_dados').text(dados.romaneio.nome_cliente);
            $('#nota_fiscal_dados').text(dados.romaneio.nota_fiscal || '-');
            $('#plano_pgto_dados').text(dados.romaneio.nome_plano);
            $('#vencimento_dados').text(vencimentoFormatado);

            // --- TABELA DE PRODUTOS (PRODUTO - VARIEDADE) ---
            let htmlProdutos = '<table class="table table-striped"><thead><tr><th>Produto - Variedade</th><th>Qtd</th><th>Tipo Cx</th><th>Preço KG</th><th>Preço Unit</th><th>Valor</th></tr></thead><tbody>';
            if (dados.produtos && dados.produtos.length > 0) {
                dados.produtos.forEach(function (item) {
                    // Unindo Produto e Variedade (Categoria) conforme sua lógica
                    let nomeExibicao = `${item.nome_produto || '-'} - ${item.nome_variedade || ''}`;

                    htmlProdutos += `<tr>
            <td>${nomeExibicao}</td>
            <td>${item.quant || '0'}</td>
            <td>${item.tipo_caixa_completo || '-'}</td>
            <td>R$ ${parseFloat(item.preco_kg || 0).toFixed(2).replace('.', ',')}</td>
            <td>R$ ${parseFloat(item.preco_unit || 0).toFixed(2).replace('.', ',')}</td>
            <td>R$ ${parseFloat(item.valor || 0).toFixed(2).replace('.', ',')}</td>
          </tr>`;
                });
            } else {
                htmlProdutos += '<tr><td colspan="6" class="text-center">Nenhum produto encontrado.</td></tr>';
            }
            htmlProdutos += '</tbody></table>';
            $('#itens_dados').html(htmlProdutos);

            // --- TABELA DE COMISSÕES (PRODUTO - VARIEDADE) ---
            let htmlComissoes = '<table class="table table-striped"><thead><tr><th>Descrição</th><th>Qtd Cx</th><th>Tipo Cx</th><th>Preço KG</th><th>Preço Unit</th><th>Valor</th></tr></thead><tbody>';
            if (dados.comissoes && dados.comissoes.length > 0) {
                dados.comissoes.forEach(function (item) {
                    // Aplicando a mesma lógica de nome composto para comissões
                    let descComissao = item.nome_produto ? `${item.nome_produto} - ${item.nome_variedade || ''}` : `Comissão ID ${item.descricao}`;

                    htmlComissoes += `<tr>
            <td>${descComissao}</td>
            <td>${item.quant_caixa || '0'}</td>
            <td>${item.tipo_caixa_completo || '-'}</td>
            <td>R$ ${parseFloat(item.preco_kg || 0).toFixed(2).replace('.', ',')}</td>
            <td>R$ ${parseFloat(item.preco_unit || 0).toFixed(2).replace('.', ',')}</td>
            <td>R$ ${parseFloat(item.valor || 0).toFixed(2).replace('.', ',')}</td>
          </tr>`;
                });
            } else {
                htmlComissoes += '<tr><td colspan="6" class="text-center">Nenhuma comissão encontrada.</td></tr>';
            }
            htmlComissoes += '</tbody></table>';
            $('#comissoes_dados').html(htmlComissoes);

            // --- TABELA DE MATERIAIS ---
            let htmlMateriais = '<table class="table table-striped"><thead><tr><th>Observações</th><th>Material</th><th>Qtd</th><th>Preço Unit</th><th>Valor</th></tr></thead><tbody>';
            if (dados.materiais && dados.materiais.length > 0) {
                dados.materiais.forEach(function (item) {
                    htmlMateriais += `<tr>
            <td>${item.observacoes || '-'}</td>
            <td>${item.nome_material || '-'}</td>
            <td>${item.quant || '0'}</td> 
            <td>R$ ${parseFloat(item.preco_unit || 0).toFixed(2).replace('.', ',')}</td>
            <td>R$ ${parseFloat(item.valor || 0).toFixed(2).replace('.', ',')}</td>
          </tr>`;
                });
            } else {
                htmlMateriais += '<tr><td colspan="5" class="text-center">Nenhum material encontrado.</td></tr>';
            }
            htmlMateriais += '</tbody></table>';
            $('#materiais_dados').html(htmlMateriais);

            // --- RODAPÉ FINANCEIRO ---
            $('#adicional_dados').text('R$ ' + parseFloat(dados.romaneio.adicional || 0).toFixed(2).replace('.', ','));
            $('#descricao_a_dados').text(dados.romaneio.descricao_a || '-');
            $('#desconto_dados').text('R$ ' + parseFloat(dados.romaneio.desconto || 0).toFixed(2).replace('.', ','));
            $('#descricao_d_dados').text(dados.romaneio.descricao_d || '-');
            $('#total_liquido_dados').text('R$ ' + parseFloat(dados.romaneio.total_liquido || 0).toFixed(2).replace('.', ','));

            $('#modalDados').modal('show');
        },
        error: function (xhr, status, error) {
            console.error('Erro na requisição AJAX:', status, error);
            alert('Ocorreu um erro ao buscar os dados.');
        }
    });
}

function buscar() {
    var dataInicial = $('#dataInicial').val();
    var dataFinal = $('#dataFinal').val();
    var cliente = $('#cliente').val();

    // Atualiza os campos ocultos do formulário de relatório (PDF)
    $('#dataInicialRel').val(dataInicial);
    $('#dataFinalRel').val(dataFinal);
    $('#clienteRel').val(cliente);

    listar(dataInicial, dataFinal, cliente);
}

function listar(dataInicial, dataFinal, cliente) {
    $.ajax({
        url: 'paginas/' + pag + '/listar.php', // Certifique-se que o caminho está correto
        method: 'POST',
        data: {
            dataInicial: dataInicial,
            dataFinal: dataFinal,
            cliente: cliente
        },
        dataType: "html",
        success: function (result) {
            $("#listar").html(result);
        }
    });
}