<?php
require_once("../../../conexao.php");

// Query simplificada com JOIN direto
$query = $pdo->query("SELECT b.*, rv.data, c.nome as nome_atacadista 
    FROM baldeio b  
    LEFT JOIN romaneio_venda rv ON b.id_romaneio = rv.id  
    LEFT JOIN clientes c ON rv.atacadista = c.id 
    ORDER BY b.id DESC");
$res = $query->fetchAll(PDO::FETCH_ASSOC);

echo '<table class="table table-bordered table-striped" id="tabela">
        <thead>
            <tr>
                <th>ID</th>
                <th>Romaneio</th>
                <th>Data</th>
                <th>Atacadista</th>
                <th>Total</th>
                <th>Local</th>
                <th>Ações</th>
            </tr>
        </thead>
        <tbody>';

if (count($res) > 0) {
    foreach($res as $dados) {
        $id = $dados['id'];
        $id_romaneio = $dados['id_romaneio'];
        $total = number_format($dados['total'], 2, ',', '.');
        $data = $dados['data'] ? implode('/', array_reverse(explode('-', substr($dados['data'], 0, 10)))) : 'N/D';
        $nome_atacadista = $dados['nome_atacadista'] ?? 'Não informado';

        echo '<tr>
        <td>'.$id.'</td>
        <td>'.$id_romaneio.'</td>
        <td>'.$data.'</td>
        <td>'.$nome_atacadista.'</td>
        <td>R$ '.$total.'</td>
        <td>'.$dados['local'].'</td>
        <td>
            <a href="#" onclick="editar('.$id.')" title="Editar Registro">
                <i class="bi bi-pencil-square text-primary"></i>
            </a>
            <a href="#" onclick="excluir('.$id.')" title="Excluir Registro">
                <i class="bi bi-trash text-danger"></i>
            </a>
        </td>
    </tr>';
    }
} 

echo '</tbody>
    </table>';

// Log para debug
error_log("Quantidade de registros encontrados: " . count($res));
?>

<script type="text/javascript">
	$(document).ready( function () {		
    $('#tabela').DataTable({
    	"language" : {
            //"url" : '//cdn.datatables.net/plug-ins/1.13.2/i18n/pt-BR.json'
        },
        "ordering": false,
		"stateSave": true
    });
} );
</script>

<script type="text/javascript">
	function editar(id, atacadista, data){

		$('#mensagem').text('');
    	$('#titulo_inserir').text('Editar Registro');

    	$('#id').val(id);
    	$('#atacadista').val(atacadista); 
    	$('#data').val(data)
    

    	$('#modalForm').modal('show');
	}


	function mostrar(id) {
		$.ajax({
			url: 'paginas/romaneio_venda/buscar_dados.php',
			method: 'POST',
			data: {id: id},
			dataType: 'json',
			success: function(dados) {
				$('#id_dados').text(dados.id);
				$('#data_dados').text(dados.data);
				$('#cliente_dados').text(dados.cliente);
				$('#nota_fiscal_dados').text(dados.nota_fiscal);
				$('#plano_pgto_dados').text(dados.plano_pgto);
				$('#vencimento_dados').text(dados.vencimento);
				
				// Produtos
				let htmlProdutos = '<table class="table table-striped"><thead><tr><th>Produto</th><th>Qtd</th><th>Tipo Cx</th><th>Preço KG</th><th>Preço Unit</th><th>Valor</th></tr></thead><tbody>';
				dados.produtos.forEach(function(item) {
					htmlProdutos += `<tr>
						<td>${item.nome_produto || '-'}</td>
						<td>${item.quant || '0'}</td>
						<td>${item.tipo_caixa || '-'}</td>
						<td>R$ ${parseFloat(item.preco_kg || 0).toFixed(2).replace('.', ',')}</td>
						<td>R$ ${parseFloat(item.preco_unit || 0).toFixed(2).replace('.', ',')}</td>
						<td>R$ ${parseFloat(item.valor || 0).toFixed(2).replace('.', ',')}</td>
					</tr>`;
				});
				htmlProdutos += '</tbody></table>';
				$('#itens_dados').html(htmlProdutos);
				
				// Comissões
				let htmlComissoes = '<table class="table table-striped"><thead><tr><th>Descrição</th><th>Qtd Cx</th><th>Tipo Cx</th><th>Preço KG</th><th>Preço Unit</th><th>Valor</th></tr></thead><tbody>';
				dados.comissoes.forEach(function(item) {
					htmlComissoes += `<tr>
						<td>${item.descricao || '-'}</td>
						<td>${item.quant_caixa || '0'}</td>
						<td>${item.tipo_caixa || '-'}</td>
						<td>R$ ${parseFloat(item.preco_kg || 0).toFixed(2).replace('.', ',')}</td>
						<td>R$ ${parseFloat(item.preco_unit || 0).toFixed(2).replace('.', ',')}</td>
						<td>R$ ${parseFloat(item.valor || 0).toFixed(2).replace('.', ',')}</td>
					</tr>`;
				});
				htmlComissoes += '</tbody></table>';
				$('#comissoes_dados').html(htmlComissoes);
				
				// Materiais
				let htmlMateriais = '<table class="table table-striped"><thead><tr><th>Observações</th><th>Material</th><th>Qtd</th><th>Preço Unit</th><th>Valor</th></tr></thead><tbody>';
				dados.materiais.forEach(function(item) {
					htmlMateriais += `<tr>
						<td>${item.observacoes}</td>
						<td>${item.nome_material}</td>
						<td>${item.quant}</td>
						<td>R$ ${item.preco_unit}</td>
						<td>R$ ${item.valor}</td>
					</tr>`;
				});
				htmlMateriais += '</tbody></table>';
				$('#materiais_dados').html(htmlMateriais);
				
				// Valores finais
				$('#adicional_dados').text('R$ ' + dados.adicional);
				$('#descricao_a_dados').text(dados.descricao_a);
				$('#desconto_dados').text('R$ ' + dados.desconto);
				$('#descricao_d_dados').text(dados.descricao_d);
				$('#total_liquido_dados').text('R$ ' + dados.total_liquido);
				
				$('#modalDados').modal('show');
			},
			error: function(xhr, status, error) {
				console.error('Erro:', error);
			}
		});
	}

	function imprimir(id) {
		window.open('rel/gerar_pdf_romaneio.php?id=' + id, '_blank');
	}

	function limparCampos(){
		$('#id').val('');
    	$('#atacadista').val(''); 
    	$('#data').val('00-00-00');

    	$('#ids').val('');
    	$('#btn-deletar').hide();	
	}

	function selecionar(id){

		var ids = $('#ids').val();

		if($('#seletor-'+id).is(":checked") == true){
			var novo_id = ids + id + '-';
			$('#ids').val(novo_id);
		}else{
			var retirar = ids.replace(id + '-', '');
			$('#ids').val(retirar);
		}

		var ids_final = $('#ids').val();
		if(ids_final == ""){
			$('#btn-deletar').hide();
		}else{
			$('#btn-deletar').show();
		}
	}

	function deletarSel(){
		var ids = $('#ids').val();
		var id = ids.split("-");
		
		for(i=0; i<id.length-1; i++){
			excluirMultiplos(id[i]);			
		}

		setTimeout(() => {
		  	listar();	
		}, 1000);

		limparCampos();
	}

	function relatorio() {
		window.open('rel/romaneio_venda_rel.php', '_blank');
	}

</script>


</script>