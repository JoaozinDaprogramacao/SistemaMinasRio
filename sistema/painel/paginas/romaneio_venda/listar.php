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
function debugQuery($query, $params)
{
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
if ($linhas > 0) {
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

  for ($i = 0; $i < $linhas; $i++) {
    // --- DADOS BÁSICOS ---
    $id = $res[$i]['id'];
    $atacadista = $res[$i]['atacadista'];

    // --- NOVOS DADOS DO ROMANEIO (ALTERADO) ---
    // Captura de todos os campos da tabela romaneio_venda
    $data_db = $res[$i]['data'];
    $total_liquido = $res[$i]['total_liquido'];
    $nota_fiscal = $res[$i]['nota_fiscal'];
    $vencimento_db = $res[$i]['vencimento'];
    $plano_pgto = $res[$i]['plano_pgto'];
    $quant_dias = $res[$i]['quant_dias'];
    $adicional = $res[$i]['adicional'];
    $descricao_a = $res[$i]['descricao_a'];
    $desconto = $res[$i]['desconto'];
    $descricao_d = $res[$i]['descricao_d'];

    // --- FORMATAÇÃO DE DATAS (ALTERADO) ---
    // Formata a data para o formato YYYY-MM-DD, ideal para inputs type="date"
    $data_input = date('Y-m-d', strtotime($data_db));
    // Formata o vencimento também, tratando se for nulo
    $vencimento_input = $vencimento_db ? date('Y-m-d', strtotime($vencimento_db)) : '';

    // Formatação de data para exibição na tabela (DD/MM/YYYY)
    $data_exibicao = date('d/m/Y', strtotime($data_db));

    // Consulta para pegar o nome do fornecedor (atacadista)
    $query_nome_fornecedor = $pdo->query("SELECT nome FROM clientes WHERE id = '$atacadista'");
    $fornecedor_nome_array = $query_nome_fornecedor->fetch(PDO::FETCH_ASSOC);
    $fornecedor_nome = $fornecedor_nome_array ? $fornecedor_nome_array['nome'] : "Não encontrado";

    // Preparar strings para passar para JS de forma segura
    $nota_fiscal_js = htmlspecialchars($nota_fiscal, ENT_QUOTES);
    $descricao_a_js = htmlspecialchars($descricao_a, ENT_QUOTES);
    $descricao_d_js = htmlspecialchars($descricao_d, ENT_QUOTES);



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
<td></td>

<td>
<big><a class="btn btn-info btn-sm" href="#" onclick="editar('{$id}')" title="Editar Dados"><i class="fa fa-edit"></i></a></big>


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
  function mostrar(id) {
    $.ajax({
      url: 'paginas/romaneio_venda/buscar_dados.php',
      method: 'POST',
      data: {
        id: id
      },
      dataType: 'json',
      success: function(dados) {
        // CORREÇÃO: Acessar os dados dentro do objeto 'romaneio'
        console.log("ID: " + dados.romaneio.id);
        $('#id_dados').text(dados.romaneio.id);

        // Formatando a data para o padrão brasileiro (opcional, mas recomendado)
        let dataFormatada = new Date(dados.romaneio.data).toLocaleDateString('pt-BR');
        let vencimentoFormatado = new Date(dados.romaneio.vencimento).toLocaleDateString('pt-BR');

        $('#data_dados').text(dataFormatada);
        $('#cliente_dados').text(dados.romaneio.nome_cliente); // CORREÇÃO: O nome da chave é 'nome_cliente'
        $('#nota_fiscal_dados').text(dados.romaneio.nota_fiscal || '-'); // Usar || '-' para campos vazios
        $('#plano_pgto_dados').text(dados.romaneio.nome_plano); // CORREÇÃO: Usar 'nome_plano' para mostrar o texto
        $('#vencimento_dados').text(vencimentoFormatado);

        // Os loops para produtos, comissões e materiais já estavam corretos,
        // pois eles estão no nível principal do JSON.
        // Apenas um pequeno ajuste no nome da chave em materiais.

        // Produtos
        let htmlProdutos = '<table class="table table-striped"><thead><tr><th>Produto</th><th>Qtd</th><th>Tipo Cx</th><th>Preço KG</th><th>Preço Unit</th><th>Valor</th></tr></thead><tbody>';
        if (dados.produtos && dados.produtos.length > 0) {
          dados.produtos.forEach(function(item) {
            htmlProdutos += `<tr>
            <td>${item.nome_produto || '-'}</td>
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

        // Comissões
        let htmlComissoes = '<table class="table table-striped"><thead><tr><th>Descrição</th><th>Qtd Cx</th><th>Tipo Cx</th><th>Preço KG</th><th>Preço Unit</th><th>Valor</th></tr></thead><tbody>';
        if (dados.comissoes && dados.comissoes.length > 0) {
          dados.comissoes.forEach(function(item) {
            // No seu JSON, o nome vem como null, a descrição é um número. Adicionei uma lógica para exibir algo útil.
            htmlComissoes += `<tr>
            <td>${item.nome_produto || `Comissão ID ${item.descricao}` || '-'}</td>
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

        // Materiais
        let htmlMateriais = '<table class="table table-striped"><thead><tr><th>Observações</th><th>Material</th><th>Qtd</th><th>Preço Unit</th><th>Valor</th></tr></thead><tbody>';
        if (dados.materiais && dados.materiais.length > 0) {
          dados.materiais.forEach(function(item) {
            htmlMateriais += `<tr>
            <td>${item.observacoes || '-'}</td>
            <td>${item.nome_material || '-'}</td>
            <td>${item.quant || '0'}</td> <td>R$ ${parseFloat(item.preco_unit || 0).toFixed(2).replace('.', ',')}</td>
            <td>R$ ${parseFloat(item.valor || 0).toFixed(2).replace('.', ',')}</td>
          </tr>`;
          });
        } else {
          htmlMateriais += '<tr><td colspan="5" class="text-center">Nenhum material encontrado.</td></tr>';
        }
        htmlMateriais += '</tbody></table>';
        $('#materiais_dados').html(htmlMateriais);

        // Valores finais - CORREÇÃO: Acessar de 'dados.romaneio'
        $('#adicional_dados').text('R$ ' + parseFloat(dados.romaneio.adicional || 0).toFixed(2).replace('.', ','));
        $('#descricao_a_dados').text(dados.romaneio.descricao_a);
        $('#desconto_dados').text('R$ ' + parseFloat(dados.romaneio.desconto || 0).toFixed(2).replace('.', ','));
        $('#descricao_d_dados').text(dados.romaneio.descricao_d);
        $('#total_liquido_dados').text('R$ ' + parseFloat(dados.romaneio.total_liquido || 0).toFixed(2).replace('.', ','));

        $('#modalDados').modal('show');
      },
      error: function(xhr, status, error) {
        // É uma boa prática mostrar um erro para o usuário também
        console.error('Erro na requisição AJAX:', status, error);
        console.error('Resposta do servidor:', xhr.responseText);
        alert('Ocorreu um erro ao buscar os dados. Verifique o console para mais detalhes.');
      }
    });
  }

  function imprimir(id) {
    window.open('rel/gerar_pdf_romaneio.php?id=' + id, '_blank');
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

  function relatorio() {
    window.open('rel/romaneio_venda_rel.php', '_blank');
  }
</script>

<script type="text/javascript">
  $(document).ready(function() {
    $('#tabela').DataTable({
      "destroy": true,
      "language": {
        // "url": "//cdn.datatables.net/plug-ins/1.13.2/i18n/pt-BR.json"
      },
      "ordering": false,
      "stateSave": true
    });

    // Seus outros listeners
  });

  // Helper para formatar números
  function formatarNumeroBR(valor) {
    if (valor === null || valor === undefined || valor === '') return '0,00';
    let num = parseFloat(valor);
    if (isNaN(num)) return '0,00';
    return num.toLocaleString('pt-BR', {
      minimumFractionDigits: 2,
      maximumFractionDigits: 2
    });
  }

  /**
   * Função EDITAR: CORRIGIDA para garantir que os options dos selects existam
   */
  // Dentro do seu arquivo romaneio.js (ou similar)

  function editar(id) {
    console.debug('=== editar() iniciado para ID:', id);

    // 1) Limpa e prepara
    limparCampos();
    $('#titulo_inserir').text(`Editar Romaneio de Venda Nº ${id}`);
    $('#id').val(id);

    // 2) Busca dados
    $.ajax({
      url: 'paginas/romaneio_venda/buscar_dados.php',
      type: 'POST',
      dataType: 'json',
      data: {
        id
      },
      success(res) {
        if (!res || !res.romaneio) {
          alert('Não foi possível carregar os dados.');
          return;
        }
        const r = res.romaneio;

        // 3) Abre modal
        $('#modalForm').modal('show');

        // 4) Aguarda DOM
        setTimeout(() => {
          // 4.1) Desativa o onchange embutido DO SELECT
          $('#plano_pgto').removeAttr('onchange');

          // ----- Cabeçalho -----
          $('input[name="data"]').val(r.data?.split(' ')[0] || '');
          $('input[name="vencimento"]').val(r.vencimento?.split(' ')[0] || '');
          $('input[name="nota_fiscal"]').val(r.nota_fiscal || '');
          $('#quant_dias').val(r.quant_dias || 0);

          // ----- Plano Pgto (nome → ID) -----
          const normalize = s => s.normalize('NFD').replace(/[\u0300-\u036f]/g, '').toLowerCase().trim();
          const target = normalize(r.nome_plano || '');
          let planoId = '';
          $('#plano_pgto option').each(function() {
            if (normalize($(this).text()) === target) {
              planoId = this.value;
              return false;
            }
          });
          $('#plano_pgto').val(planoId);


          $('#cliente_modal').val(r.atacadista || '').trigger('change');

          // ----- Produtos -----
          $('#linha-container_1').empty();
          if (res.produtos?.length) {
            res.produtos.forEach((item, idx) => {
              console.debug('addNewLine1 para produto', idx);
              addNewLine1();
              const $ln = $('#linha-container_1 .linha_1').eq(idx);
              $ln.find('.quant_caixa_1').val(item.quant || '');
              $ln.find('.produto_1').val(item.variedade || '');
              $ln.find('.preco_kg_1').val(item.preco_kg ?
                parseFloat(item.preco_kg).toFixed(2).replace('.', ',') :
                '0,00');
              $ln.find('.tipo_cx_1').val(item.tipo_caixa || '');
              $ln.find('.preco_unit_1').val(item.preco_unit ?
                parseFloat(item.preco_unit).toFixed(2).replace('.', ',') :
                '0,00');
              $ln.find('.valor_1').val(item.valor ?
                parseFloat(item.valor).toFixed(2).replace('.', ',') :
                '0,00');
              calcularValores($ln.get(0));
            });
            $('#desc-avista').val(r.desc_avista);
          } else {
            console.debug('sem produtos: addNewLine1');
            addNewLine1();
          }

          // ----- Comissões -----
          $('#linha-container_2').empty();
          if (res.comissoes?.length) {
            res.comissoes.forEach((item, idx) => {
              console.debug('addNewLine2 para comissão', idx);
              addNewLine2();
              const $ln = $('#linha-container_2 .linha_2').eq(idx);
              $ln.find('.desc_2').val(item.descricao || '');
              $ln.find('.quant_caixa_2').val(item.quant_caixa || '');
              $ln.find('.preco_kg_2').val(item.preco_kg ?
                parseFloat(item.preco_kg).toFixed(2).replace('.', ',') :
                '0,00');
              $ln.find('.tipo_cx_2').val(item.tipo_caixa || '');
              $ln.find('.preco_unit_2').val(item.preco_unit ?
                parseFloat(item.preco_unit).toFixed(2).replace('.', ',') :
                '0,00');
              $ln.find('.valor_2').val(item.valor ?
                parseFloat(item.valor).toFixed(2).replace('.', ',') :
                '0,00');
              calcularValores2($ln.get(0));
            });
          } else {
            console.debug('sem comissões: addNewLine2');
            addNewLine2();
          }

          // ----- Materiais -----
          $('#linha-container_3').empty();
          if (res.materiais?.length) {
            res.materiais.forEach((item, idx) => {
              console.debug('addNewLine3 para material', idx);
              addNewLine3();
              const $ln = $('#linha-container_3 .linha_3').eq(idx);
              $ln.find('.obs_3').val(item.observacoes || '');
              $ln.find('.material').val(item.descricao || '');
              $ln.find('.quant_3').val(item.quant || '');
              $ln.find('.preco_unit_3').val(item.preco_unit ?
                parseFloat(item.preco_unit).toFixed(2).replace('.', ',') :
                '0,00');
              $ln.find('.valor_3').val(item.valor ?
                parseFloat(item.valor).toFixed(2).replace('.', ',') :
                '0,00');
              calcularValores3($ln.get(0));
            });
          } else {
            console.debug('sem materiais: addNewLine3');
            addNewLine3();
          }


          $('#valor_adicional').val(r.adicional.toFixed(2).replace('.', ','));
          $('#descricao_adicional').val(r.descricao_a);

          $('#valor_desconto').val(r.desconto.toFixed(2).replace('.', ','));
          $('#descricao_desconto').val(r.descricao_d);

          // 5) Recalcula TUDO manualmente
          calculaTotais();
          calculaTotais2();
          calculaTotais3();
        }, 100);
      },
      error(err) {
        console.error('Erro ao buscar dados (venda):', err);
        alert('Não foi possível carregar detalhes. Veja o console.');
      }
    });
  }





  /**
   * Função ADICIONAR LINHA DE PRODUTO: CORRIGIDA para usar os campos corretos
   */
  function adicionarLinhaProduto(item = {}) {
    // CORREÇÃO 1: Usando item.tipo_caixa para o VALUE e item.tipo_caixa_completo para o TEXTO.
    const optionTipoCaixa = item.tipo_caixa ?
      `<option value="${item.tipo_caixa}" selected>${item.tipo_caixa_completo}</option>` :
      '<option value="">Selecione...</option>';

    const optionVariedade = item.variedade ?
      `<option value="${item.variedade}" selected>${item.nome_produto}</option>` :
      '<option value="">Selecione Variedade...</option>';

    const linha = `
            <tr>
                <td><input type="number" step="1" name="produto_quant[]" class="form-control" value="${item.quant || ''}" required></td>
                <td>
                    <select name="produto_id[]" class="form-control" required>
                        ${optionVariedade}
                    </select>
                </td>
                <td><input type="text" name="produto_preco_kg[]" class="form-control" value="${formatarNumeroBR(item.preco_kg)}"></td>
                <td>
                    <select name="produto_tipo_caixa[]" class="form-control">
                        ${optionTipoCaixa}
                    </select>
                </td>
                <td><input type="text" name="produto_preco_unit[]" class="form-control" value="${formatarNumeroBR(item.preco_unit)}"></td>
                <td><input type="text" name="produto_valor[]" class="form-control" value="${formatarNumeroBR(item.valor)}" readonly></td>
                <td><button type="button" class="btn btn-danger btn-sm" onclick="removerLinha(this)">-</button></td>
            </tr>
        `;
    $('#tbodyProdutos').append(linha);
  }

  // Função para adicionar linha de comissão (também corrigida)
  function adicionarLinhaComissao(item = {}) {
    const optionTipoCaixa = item.tipo_caixa ?
      `<option value="${item.tipo_caixa}" selected>${item.tipo_caixa_completo}</option>` :
      '<option value="">Selecione...</option>';

    const linha = `
             <tr>
                <td><input type="text" name="comissao_desc[]" class="form-control" value="${item.descricao || ''}"></td>
                <td><input type="number" step="1" name="comissao_quant[]" class="form-control" value="${item.quant_caixa || ''}"></td>
                <td><input type="text" name="comissao_valor[]" class="form-control" value="${formatarNumeroBR(item.valor)}"></td>
                <td><button type="button" class="btn btn-danger btn-sm" onclick="removerLinha(this)">-</button></td>
            </tr>
        `;
    $('#tbodyComissoes').append(linha);
  }

  // Função para adicionar linha de material (sem alterações necessárias aqui)
  function adicionarLinhaMaterial(item = {}) {
    /* ... sua função aqui ... */
  }

  function removerLinha(botao) {
    /* ... sua função aqui ... */
  }

  // Suas outras funções (mostrar, imprimir, selecionar, etc.)
  // ...
</script>