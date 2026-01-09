function listar() {
    var dataInicial = $('#dataInicial').val();
    var dataFinal = $('#dataFinal').val();
    var cliente = $('#cliente').val();

    // Sincroniza filtros com o formulário de relatório PDF
    $('#dataInicialRel').val(dataInicial);
    $('#dataFinalRel').val(dataFinal);
    $('#clienteRel').val(cliente);

    // Atualiza a listagem principal (Cards/Linhas)
    $.ajax({
        url: 'paginas/' + pag + '/listar.php',
        method: 'POST',
        data: { dataInicial, dataFinal, cliente },
        dataType: "html",
        success: function (result) {
            $("#listar").html(result);
        }
    });

    // Atualiza a tabela de resumo de produtos (Filtragem Dinâmica / Alta Performance)
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

function mostrar(id) {
    $.ajax({
        url: 'paginas/romaneio_venda_detalhes/buscar_dados.php',
        method: 'POST',
        data: { id: id },
        dataType: 'json',
        success: function (dados) {
            $('#id_dados').text(dados.romaneio.id);

            let dataFormatada = new Date(dados.romaneio.data).toLocaleDateString('pt-BR');
            let vencimentoFormatado = new Date(dados.romaneio.vencimento).toLocaleDateString('pt-BR');

            $('#data_dados').text(dataFormatada);
            $('#cliente_dados').text(dados.romaneio.nome_cliente);
            $('#nota_fiscal_dados').text(dados.romaneio.nota_fiscal || '-');
            $('#plano_pgto_dados').text(dados.romaneio.nome_plano);
            $('#vencimento_dados').text(vencimentoFormatado);

            // Tabela de Produtos
            let htmlProdutos = '<table class="table table-striped"><thead><tr><th>Produto - Variedade</th><th>Qtd</th><th>Tipo Cx</th><th>Preço KG</th><th>Preço Unit</th><th>Valor</th></tr></thead><tbody>';
            if (dados.produtos && dados.produtos.length > 0) {
                dados.produtos.forEach(function (item) {
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

            // Tabela de Comissões
            let htmlComissoes = '<table class="table table-striped"><thead><tr><th>Descrição</th><th>Qtd Cx</th><th>Tipo Cx</th><th>Preço KG</th><th>Preço Unit</th><th>Valor</th></tr></thead><tbody>';
            if (dados.comissoes && dados.comissoes.length > 0) {
                dados.comissoes.forEach(function (item) {
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

            // Tabela de Materiais
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

            // Rodapé Financeiro
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

function limparFiltros() {
    // Reseta os valores dos inputs
    $('#cliente').val('').trigger('change.select2'); // Caso use select2
    $('#cliente').val(''); // Garantia para select comum
    $('#dataInicial').val('');
    $('#dataFinal').val('');

    // Chama a função listar para atualizar as tabelas sem filtros
    listar();
}