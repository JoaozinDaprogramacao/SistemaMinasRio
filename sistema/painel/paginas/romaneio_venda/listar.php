<?php
$tabela = 'romaneio_venda';
require_once("../../../conexao.php");

// Variáveis de filtro
$dataInicial = @$_POST['p1'];
$dataFinal = @$_POST['p2'];
$cliente = @$_POST['p3'];

// Se as datas estiverem vazias, não aplicar filtro de data
if (empty($dataInicial) || empty($dataFinal)) {
    $filtro_data = "";
} else {
    $filtro_data = "WHERE data BETWEEN '$dataInicial' AND '$dataFinal'";
}

// Definir a variável $tipo_data
$tipo_data = 'data'; // Substitua pelo nome da coluna que você deseja usar para filtrar as datas

// Valores padrão para datas, caso não sejam fornecidos
if ($dataInicial == "") {
    $dataInicial = date('Y-m-01'); // Início do mês atual
}

if ($dataFinal == "") {
    $dataFinal = date('Y-m-t'); // Final do mês atual
}

// Inicializa a cláusula WHERE
$where = [];
$params = [];

// Adiciona filtro de cliente
if (!empty($cliente)) {
    $where[] = "atacadista = :cliente";
    $params[':cliente'] = $cliente;
}

// Combina as condições do WHERE
$filtrar = '';
if (count($where) > 0) {
    $filtrar = ' WHERE ' . implode(' AND ', $where); // Adiciona espaço antes de WHERE
}

// Consulta SQL com filtros
$sql = "SELECT * FROM $tabela" . $filtrar . " ORDER BY id DESC"; 

// Função para debug da query
function debugQuery($query, $params) {
    $keys = array();
    $values = $params;
    
    // Ordena os parâmetros pelo tamanho do nome
    krsort($params);
    
    foreach ($params as $key => $value) {
        // Remove os dois pontos dos placeholders
        $clean_key = str_replace(':', '', $key);
        
        // Trata diferentes tipos de dados
        if (is_string($value)) {
            $value = "'" . addslashes($value) . "'";
        } elseif (is_null($value)) {
            $value = 'NULL';
        } elseif (is_bool($value)) {
            $value = $value ? 'TRUE' : 'FALSE';
        }
        
        // Substitui os placeholders na query
        $query = str_replace(':' . $clean_key, $value, $query);
    }
    
    return $query;
}

// Debug da query antes da execução
error_log("Query Debug: " . debugQuery($sql, $params));
echo "<script>console.log('Query Debug:', " . json_encode(debugQuery($sql, $params)) . ")</script>";

$query = $pdo->prepare($sql);
$query->execute($params);
$res = $query->fetchAll(PDO::FETCH_ASSOC);
$linhas = @count($res);
if($linhas > 0){
echo <<<HTML

	<table class="table table-bordered text-nowrap border-bottom dt-responsive" id="tabela">
	<thead> 
	<tr> 
	<th align="center" width="5%" class="text-center">Selecionar</th>
	<th>Room N°</th>	
	<th>Atacadista</th>	
	<th>Data</th>	
	<th>Ações</th>
	</tr> 
	</thead> 
	<tbody>	
HTML;

for($i=0; $i<$linhas; $i++){
	$id = $res[$i]['id'];
	$atacadista = $res[$i]['atacadista'];
	$data = $res[$i]['data'];

	$dataF = implode('/', array_reverse(@explode('-', $data)));

			// Consulta para pegar o nome do fornecedor
		$query_nome_fornecedor = $pdo->query("SELECT nome FROM clientes WHERE id = '$atacadista'");

		// Fetch o resultado da consulta
		$fornecedor_nome_array = $query_nome_fornecedor->fetch(PDO::FETCH_ASSOC);

		// Verifique se o resultado foi encontrado e extraia o nome do fornecedor
		if ($fornecedor_nome_array) {
			$fornecedor_nome = $fornecedor_nome_array['nome'];
		} else {
			// Caso o fornecedor não seja encontrado
			$fornecedor_nome = "Fornecedor não encontrado";
		}


echo <<<HTML
<tr style="">
<td align="center">
<div class="custom-checkbox custom-control">
<input type="checkbox" class="custom-control-input" id="seletor-{$id}" onchange="selecionar('{$id}')">
<label for="seletor-{$id}" class="custom-control-label mt-1 text-dark"></label>
</div>
</td>
<td>{$id}</td>
<td>{$fornecedor_nome}</td>
<td>{$data}</td>

<td>
	<big><a class="btn btn-info btn-sm" href="#" onclick="editar('{$id}','{$atacadista}','{$dataF}')" title="Editar Dados"><i class="fa fa-edit "></i></a></big>

	<div class="dropdown" style="display: inline-block;">                      
                        <a class="btn btn-danger btn-sm" href="#" aria-expanded="false" aria-haspopup="true" data-bs-toggle="dropdown" class="dropdown"><i class="fa fa-trash "></i> </a>
                        <div  class="dropdown-menu tx-13">
                        <div style="width: 240px; padding:15px 5px 0 10px;" class="dropdown-item-text">
                        <p>Confirmar Exclusão? <a href="#" onclick="excluir('{$id}')"><span class="text-danger">Sim</span></a></p>
                        </div>
                        </div>
                        </div>


<big><a class="btn btn-primary btn-sm" href="#" onclick="mostrar('{$id}')" title="Mostrar Dados"><i class="fa fa-info-circle "></i></a></big>
<big><a class="btn btn-primary btn-sm" href="#" onclick="imprimir('{$id}')" title="Imprimir"><i class="fa fa-file-pdf-o"></i></a></big>
</td>
</tr>
HTML;

}

}else{
	echo 'Não possui nenhum cadastro!';
}


echo <<<HTML
</tbody>
<small><div align="center" id="mensagem-excluir"></div></small>
</table>

<br>		
			<p align="right" style="margin-top: -10px">
				<span style="margin-right: 10px">Total Itens  <span > {$linhas} </span></span>
				
			</p>

HTML;
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
						<td>${item.nome_produto || item.descricao || '-'}</td>
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
						<td>${item.descricao_completa || '-'}</td>
						<td>${item.nome_material || '-'}</td>
						<td>${item.quantidade || '0'}</td>
						<td>R$ ${parseFloat(item.preco_unit || 0).toFixed(2).replace('.', ',')}</td>
						<td>R$ ${parseFloat(item.valor || 0).toFixed(2).replace('.', ',')}</td>
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
		console.log("aqui limpar 13123")
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