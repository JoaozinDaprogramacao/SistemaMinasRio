function mostrar(id) {
    $.ajax({
        url: 'paginas/romaneio_venda_detalhes/buscar_dados.php',
        method: 'POST',
        data: { id: id },
        dataType: 'json',
        success: function (dados) {
            const r = dados.romaneio;

            // Preenchimento dos campos de texto do modal
            $('#id_dados').text(r.id);
            $('#data_dados').text(r.data ? new Date(r.data).toLocaleDateString('pt-BR') : '-');
            $('#cliente_dados').text(r.nome_cliente);
            $('#nota_fiscal_dados').text(r.nota_fiscal || '-');
            $('#plano_pgto_dados').text(r.nome_plano || '-');
            $('#vencimento_dados').text(r.vencimento ? new Date(r.vencimento).toLocaleDateString('pt-BR') : '-');

            // Tabela de Produtos (Nome - Categoria)
            let htmlProd = '<table class="table table-sm table-bordered"><thead><tr class="table-active"><th>Produto - Categoria</th><th>Qtd</th><th>Tipo</th><th>Vl. Unit</th><th>Subtotal</th></tr></thead><tbody>';
            if (dados.produtos && dados.produtos.length > 0) {
                dados.produtos.forEach(item => {
                    let exibicao = `${item.nome_produto} - ${item.nome_categoria || 'S/V'}`;
                    htmlProd += `<tr>
                        <td>${exibicao}</td>
                        <td>${item.quant}</td>
                        <td>${item.tipo_caixa_completo || '-'}</td>
                        <td>R$ ${parseFloat(item.preco_unit).toLocaleString('pt-BR', { minimumFractionDigits: 2 })}</td>
                        <td>R$ ${parseFloat(item.valor).toLocaleString('pt-BR', { minimumFractionDigits: 2 })}</td>
                    </tr>`;
                });
            } else {
                htmlProd += '<tr><td colspan="5" class="text-center">Nenhum produto.</td></tr>';
            }
            htmlProd += '</tbody></table>';
            $('#itens_dados').html(htmlProd);

            // Tabela de Comissões
            let htmlCom = '<table class="table table-sm table-bordered"><thead><tr class="table-active"><th>Descrição</th><th>Qtd</th><th>Valor</th></tr></thead><tbody>';
            if (dados.comissoes && dados.comissoes.length > 0) {
                dados.comissoes.forEach(item => {
                    htmlCom += `<tr>
                        <td>${item.nome_produto || 'Comissão'}</td>
                        <td>${item.quant_caixa || '0'}</td>
                        <td>R$ ${parseFloat(item.valor).toLocaleString('pt-BR', { minimumFractionDigits: 2 })}</td>
                    </tr>`;
                });
            } else {
                htmlCom += '<tr><td colspan="3" class="text-center">Nenhuma comissão.</td></tr>';
            }
            htmlCom += '</tbody></table>';
            $('#comissoes_dados').html(htmlCom);

            // Tabela de Materiais
            let htmlMat = '<table class="table table-sm table-bordered"><thead><tr class="table-active"><th>Material</th><th>Qtd</th><th>Observações</th></tr></thead><tbody>';
            if (dados.materiais && dados.materiais.length > 0) {
                dados.materiais.forEach(item => {
                    htmlMat += `<tr>
                        <td>${item.nome_material || '-'}</td>
                        <td>${item.quant || '0'}</td>
                        <td>${item.observacoes || '-'}</td>
                    </tr>`;
                });
            } else {
                htmlMat += '<tr><td colspan="3" class="text-center">Nenhum material.</td></tr>';
            }
            htmlMat += '</tbody></table>';
            $('#materiais_dados').html(htmlMat);

            // Rodapé Financeiro
            $('#descricao_a_dados').text(r.descricao_a || 'Nenhuma');
            $('#adicional_dados').text('R$ ' + parseFloat(r.adicional || 0).toLocaleString('pt-BR', { minimumFractionDigits: 2 }));
            $('#descricao_d_dados').text(r.descricao_d || 'Nenhuma');
            $('#desconto_dados').text('R$ ' + parseFloat(r.desconto || 0).toLocaleString('pt-BR', { minimumFractionDigits: 2 }));
            $('#total_liquido_dados').text('R$ ' + parseFloat(r.total_liquido || 0).toLocaleString('pt-BR', { minimumFractionDigits: 2 }));

            $('#modalDados').modal('show');
        }
    });
}