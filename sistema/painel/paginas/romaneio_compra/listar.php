<?php
$tabela = 'romaneio_compra';
require_once("../../../conexao.php");

// Variáveis de filtro
$dataInicial = @$_POST['p1'];
$dataFinal = @$_POST['p2'];
$fornecedor = @$_POST['p3'];

// Inicializa a cláusula WHERE
$where = [];
$params = [];

// Adiciona filtro de data
if (!empty($dataInicial) && !empty($dataFinal)) {
	$where[] = "data >= :dataInicial AND data <= :dataFinal";
	$params[':dataInicial'] = $dataInicial;
	$params[':dataFinal'] = $dataFinal;
}

// Adiciona filtro de fornecedor
if (!empty($fornecedor)) {
	$where[] = "fornecedor = :fornecedor";
	$params[':fornecedor'] = $fornecedor;
}

// Combina as condições do WHERE
$filtrar = '';
if (count($where) > 0) {
	$filtrar = ' WHERE ' . implode(' AND ', $where);
}

// Consulta SQL com filtros
$query = $pdo->prepare("SELECT rc.*, f.nome_atacadista as nome_fornecedor 
	FROM $tabela rc
	LEFT JOIN fornecedores f ON rc.fornecedor = f.id" . 
	$filtrar . " ORDER BY rc.id DESC");

$query->execute($params);
$res = $query->fetchAll(PDO::FETCH_ASSOC);
$linhas = @count($res);
if ($linhas > 0) {
	echo <<<HTML

	<table class="table table-bordered text-nowrap border-bottom dt-responsive" id="tabela">
	<thead> 
	<tr> 
	<th align="center" width="5%" class="text-center">Selecionar</th>
	<th>Room N°</th>	
	<th>Fornecedor</th>	
	<th>Data</th>	
	<th>Ações</th>
	</tr> 
	</thead> 
	<tbody>	
HTML;

	for ($i = 0; $i < $linhas; $i++) {
		$id = $res[$i]['id'];
		$fornecedor = $res[$i]['fornecedor'];
		$data = $res[$i]['data'];

		$dataF = implode('/', array_reverse(@explode('-', $data)));

		// Consulta para pegar o nome do fornecedor
		$query_nome_fornecedor = $pdo->query("SELECT nome_atacadista FROM fornecedores WHERE id = '$fornecedor'");

		// Fetch o resultado da consulta
		$fornecedor_nome_array = $query_nome_fornecedor->fetch(PDO::FETCH_ASSOC);

		// Verifique se o resultado foi encontrado e extraia o nome do fornecedor
		if ($fornecedor_nome_array) {
			$fornecedor_nome = $fornecedor_nome_array['nome_atacadista'];
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
	<big><a class="btn btn-info btn-sm" href="#" onclick="editar('{$id}','{$fornecedor}','{$dataF}')" title="Editar Dados"><i class="fa fa-edit "></i></a></big>

	<div class="dropdown" style="display: inline-block;">                      
                        <a class="btn btn-danger btn-sm" href="#" aria-expanded="false" aria-haspopup="true" data-bs-toggle="dropdown" class="dropdown"><i class="fa fa-trash "></i> </a>
                        <div  class="dropdown-menu tx-13">
                        <div style="width: 240px; padding:15px 5px 0 10px;" class="dropdown-item-text">
                        <p>Confirmar Exclusão? <a href="#" onclick="excluir('{$id}')"><span class="text-danger">Sim</span></a></p>
                        </div>
                        </div>
                        </div>


<button type="button" class="btn btn-info btn-sm" onclick="mostrar('{$id}')" title="Ver Dados">
    <i class="bi bi-info-circle"></i>
</button>

<big><a class="btn btn-primary btn-sm" href="#" onclick="imprimir('{$id}')" title="Imprimir"><i class="fa fa-file-pdf-o"></i></a>
</td>
</tr>
HTML;
	}
} else {
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
	$(document).ready(function() {
		$('#tabela').DataTable({
			"language": {
				//"url" : '//cdn.datatables.net/plug-ins/1.13.2/i18n/pt-BR.json'
			},
			"ordering": false,
			"stateSave": true
		});
	});
</script>

<script type="text/javascript">
		function imprimir(id) {
		window.open('rel/gerar_pdf_romaneio_compra.php?id=' + id, '_blank');
	}

	let isEditing = false;

	function editar(id) {
  console.debug('=== editar() iniciado para ID:', id);

  // 1) Limpa tudo e prepara o modal
  limparFormulario();
  $('#titulo_inserir').text('Editar Registro');
  $('#id').val(id);

  // 2) Busca dados
  $.ajax({
    url: 'paginas/romaneio_compra/buscar_dados.php',
    type: 'POST',
    dataType: 'json',
    data: { id },
    success: function(res) {
      console.debug('Resposta AJAX buscar_dados:', res);
      const r = res.romaneio;

      // ----- Cabeçalho -----
      $('.data_atual').val(r.data.split(' ')[0]);
      $('#vencimento').val(r.vencimento.split(' ')[0]);
      $('#nota_fiscal').val(r.nota_fiscal || '');
      $('#plano_pgto').val(r.plano_pgto);
      $('#quant_dias').val(r.quant_dias);
      $('#fornecedor').val(r.fornecedor);
      $('#fazenda').val(r.fazenda);
      $('#cliente').val(r.cliente);

      // Desconto à vista
      console.debug('desc_avista RAW:', r.desc_avista);
      $('#desc-avista').val(
        (parseFloat(r.desc_avista) || 0).toFixed(2).replace('.', ',')
      );

      // ----- Produtos -----
      $('#linha-container_1').empty();
      res.produtos.forEach((item, idx) => {
        addNewLine1();
        const $linha = $('#linha-container_1 .linha_1').eq(idx);

        console.debug(`--- Produto ${idx}`, item);

        $linha.find('.quant_caixa_1').val(item.quant);
        $linha.find('.produto_1').val(item.variedade);
        $linha.find('.preco_kg_1').val(
          parseFloat(item.preco_kg).toFixed(2).replace('.', ',')
        );

        // pega só o número antes do espaço
        const rawTipo = item.tipo_caixa;       // ex: "14.50 G"
        const numTipo = rawTipo.split(' ')[0]; // ex: "14.50"
        console.debug(`tipo_caixa raw [${idx}]:`, rawTipo, '→ num:', numTipo);
        $linha.find('.tipo_cx_1').val(numTipo);

        $linha.find('.preco_unit_1').val(
          parseFloat(item.preco_unit).toFixed(2).replace('.', ',')
        );
        $linha.find('.valor_1').val(
          parseFloat(item.valor).toFixed(2).replace('.', ',')
        );

        // dispara recálculo desta linha
        calcularValores($linha.get(0));
      });

      // ----- Comissões fixas -----
      console.debug('Preenchendo abatimentos fixos');
      $('#valor_funrural').val(
        parseFloat(r.desc_funrural).toFixed(2).replace('.', ',')
      );
      $('#valor_ima').val(
        parseFloat(r.desc_ima).toFixed(2).replace('.', ',')
      );
      $('#valor_abanorte').val(
        parseFloat(r.desc_abanorte).toFixed(2).replace('.', ',')
      );
      $('#valor_taxa_adm').val(
        parseFloat(r.desc_taxaadm).toFixed(2).replace('.', ',')
      );
      calculaTotais2();  // atualiza total de comissões e carga

      // ----- Descontos Diversos -----
      console.debug('Preenchendo descontos diversos:', r.descontos_diversos);
      $('#discount-container').empty();
      let descontos = [];
      try {
        descontos = JSON.parse(r.descontos_diversos || '[]');
      } catch (e) {
        console.warn('JSON inválido em descontos_diversos', e);
      }
      descontos.forEach((d, i) => {
        console.debug(`– desconto ${i}`, d);
        addDiscountLine();
        const $dlinha = $('#discount-container .linha_3').eq(i);
        $dlinha.find('.desconto-type').val(d.tipo);
        $dlinha.find('.desconto-valor').val(
          d.valor.toFixed(2).replace('.', ',')
        );
        $dlinha.find('.desconto-obs').val(d.obs);
      });
      // após inserir linhas, recalcula totais gerais
      calcularDescontosDiversos();
      updateLiquidPayable();

      // 4) Exibe o modal
      $('#modalForm').modal('show');
      console.debug('Modal de edição aberto');
    },
    error: function(err) {
      console.error('Erro ao buscar dados do romaneio:', err);
      alert('Não foi possível carregar os detalhes. Veja o console para mais informações.');
    }
  });
}



// --- fazemos um guard dentro do handleInput para não adicionar linhas durante o editar() ---
function handleInput(input) {
  if (isEditing) return;  // IGNORA auto-add no fluxo de edição
  const linha = input.closest(".linha_1");
  const container = document.getElementById("linha-container_1");
  const allFilled = [...linha.querySelectorAll("input, select")].every(f=>f.value.trim()!=="");
  if (allFilled && linha === container.lastElementChild) {
    addNewLine1();
  }
}


	function formatarData(data) {
		if (!data) return '-';
		return new Date(data).toLocaleDateString('pt-BR');
	}

	function formatarNumero(valor) {
		if (!valor) return '0,00';
		return parseFloat(valor).toFixed(2).replace('.', ',');
	}

	function mostrar(id) {
  $.ajax({
    url: 'paginas/romaneio_compra/buscar_dados.php',
    type: 'POST',
    dataType: 'json',
    data: { id },
    success: function(res) {
      console.log('Retorno buscar_dados:', res);

      const r = res.romaneio;

      // Cabeçalho
      $('#fornecedor_modal').text(r.nome_fornecedor || '-');
      $('#data_modal').text(formatarData(r.data) || '-');
      $('#nota_modal').text(r.nota_fiscal || '-');
      $('#plano_modal').text(r.nome_plano || '-');
      $('#vencimento_modal').text(formatarData(r.vencimento) || '-');
      $('#quant_dias_modal').text(r.quant_dias || '-');
      $('#fazenda_modal').text(r.fazenda || '-');
      $('#cliente_modal').text(r.nome_cliente || '-');
      $('#total_liquido_modal').text('R$ ' + formatarNumero(r.total_liquido));

      // Produtos
      let html = '';
      res.produtos.forEach(item => {
        html += `
          <tr>
            <td>${item.nome_produto}</td>
            <td>${item.tipo_caixa}</td>
			<td>${item.quant}</td>	
            <td>${formatarNumero(item.preco_kg)}</td>
            <td>${formatarNumero(item.preco_unit)}</td>
            <td>${formatarNumero(item.valor)}</td>
          </tr>`;
      });
      $('#produtos_modal').html(html);

      // Comissões
      $('#desc_funrural_modal').text('R$ ' + formatarNumero(r.desc_funrural));
      $('#desc_ima_modal').text('R$ ' + formatarNumero(r.desc_ima));
      $('#desc_abanorte_modal').text('R$ ' + formatarNumero(r.desc_abanorte));
      $('#desc_taxaadm_modal').text('R$ ' + formatarNumero(r.desc_taxaadm));

      // Descontos Diversos (JSON string -> array)
      let descontos = [];
      try { descontos = JSON.parse(r.descontos_diversos || '[]'); }
      catch(e){ console.warn('JSON inválido em descontos_diversos', e); }
      let htmlDesc = '';
      if (descontos.length) {
        descontos.forEach(d => {
          htmlDesc += `<tr>
            <td>${d.tipo === '+' ? 'Adicionar' : 'Subtrair'}</td>
            <td>R$ ${formatarNumero(d.valor)}</td>
            <td>${d.obs || ''}</td>
          </tr>`;
        });
      } else {
        htmlDesc = '<tr><td colspan="3">Nenhum desconto</td></tr>';
      }
      $('#descontos_modal').html(htmlDesc);

      // Abre o modal
      $('#modalMostrarDados').modal('show');
    },
    error: function(err) {
      console.error('Erro ao buscar dados do romaneio:', err);
      alert('Não foi possível carregar os detalhes. Veja o console para mais detalhes.');
    }
  });
}

// Helpers (se ainda não tiver)
function formatarData(d) {
  if (!d) return '';
  const dt = new Date(d);
  return dt.toLocaleDateString('pt-BR');
}
function formatarNumero(v) {
  return (parseFloat(v) || 0).toFixed(2).replace('.', ',');
}


// Helper para formatar datas em pt-BR
function formatarData(d) {
  if (!d) return '';
  const dt = new Date(d);
  return dt.toLocaleDateString('pt-BR');
}

	function limparCampos() {
		$('#id').val('');
		$('#fornecedor').val('');
		$('#data').val('00-00-00');

		$('#ids').val('');
		$('#btn-deletar').hide();
	}

	function selecionar(id) {
		var ids = $('#ids').val();

		if ($('#seletor-' + id).is(":checked") == true) {
			var novo_id = ids + id + '-';
			$('#ids').val(novo_id);
		} else {
			var retirar = ids.replace(id + '-', '');
			$('#ids').val(retirar);
		}

		var ids_final = $('#ids').val();
		if (ids_final == "") {
			$('#btn-deletar').hide();
		} else {
			$('#btn-deletar').show();
		}
	}

	function deletarSel() {
		var ids = $('#ids').val();
		var id = ids.split("-");

		for (i = 0; i < id.length - 1; i++) {
			excluirMultiplos(id[i]);
		}

		setTimeout(() => {
			listar();
		}, 1000);

		limparCampos();
	}
</script>


</script>