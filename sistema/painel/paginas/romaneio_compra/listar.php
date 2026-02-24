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
  $where[] = "rc.data >= :dataInicial AND rc.data <= :dataFinal"; // Adicionei o alias rc. para evitar ambiguidade
  $params[':dataInicial'] = $dataInicial;
  $params[':dataFinal'] = $dataFinal;
}

// Adiciona filtro de fornecedor
if (!empty($fornecedor)) {
  $where[] = "rc.fornecedor = :fornecedor";
  $params[':fornecedor'] = $fornecedor;
}

// Combina as condições do WHERE
$filtrar = '';
if (count($where) > 0) {
  $filtrar = ' WHERE ' . implode(' AND ', $where);
}

// --- ALTERAÇÃO 1: Adicionei o JOIN com a tabela de clientes ---
// Assumi que a tabela se chama 'clientes' e o campo 'nome'. Ajuste se necessário.
$query = $pdo->prepare("SELECT rc.*, 
        f.nome_atacadista as nome_fornecedor,
        c.nome as nome_cliente 
    FROM $tabela rc
    LEFT JOIN fornecedores f ON rc.fornecedor = f.id
    LEFT JOIN clientes c ON rc.cliente = c.id " .
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
    <th>Cliente</th> <th>Data</th>    
    <th>Ações</th>
    </tr> 
    </thead> 
    <tbody> 
HTML;

  for ($i = 0; $i < $linhas; $i++) {
    $id = $res[$i]['id'];
    $data = $res[$i]['data'];

    // Formatação da data
    $dataF = implode('/', array_reverse(@explode('-', explode(' ', $data)[0])));

    // Pegando nomes vindos direto do SQL (Muito mais rápido que fazer consulta extra)
    $fornecedor_nome = $res[$i]['nome_fornecedor'] ? $res[$i]['nome_fornecedor'] : 'Fornecedor não encontrado';

    // --- ALTERAÇÃO 3: Lógica para pegar nome do cliente ---
    $cliente_nome = $res[$i]['nome_cliente'] ? $res[$i]['nome_cliente'] : 'Sem Cliente';

    // IDs originais para as funções JS
    $id_fornecedor_fk = $res[$i]['fornecedor'];

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
    <td>{$cliente_nome}</td> <td>{$dataF}</td>

    <td>
        <big><a class="btn btn-info btn-sm" href="#" onclick="editar('{$id}')" title="Editar Dados"><i class="fa fa-edit "></i></a></big>

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
    console.log('=== editar() iniciado para ID:', id);

    limparCampos();
    $('#titulo_inserir').text('Editar Registro');
    $('#id').val(id);

    $.ajax({
      url: 'paginas/romaneio_compra/buscar_dados.php',
      type: 'POST',
      dataType: 'json',
      data: {
        id
      },
      success: function(res) {
        console.log('Resposta AJAX buscar_dados:', res);
        if (!res || !res.romaneio) {
          alert('Erro ao carregar os dados do romaneio.');
          return;
        }
        const r = res.romaneio;

        // 1. Cabeçalho
        $('input[name="data"]').val(r.data ? r.data.split(' ')[0] : '');
        $('#vencimento').val(r.vencimento ? r.vencimento.split(' ')[0] : '');
        $('#nota_fiscal').val(r.nota_fiscal || '');
        $('#plano_pgto').val(r.plano_pgto || '');
        $('#quant_dias').val(r.quant_dias || '');
        $('#fornecedor').val(r.fornecedor || '');
        $('#fazenda').val(r.fazenda || '');
        $('#cliente').val(r.cliente || '');
        $('#desc-avista').val(r.desc_avista ? r.desc_avista.replace('.', ',') : '0,00');

        // 2. Produtos
        $('#linha-container_1').empty();
        if (res.produtos && res.produtos.length) {
          res.produtos.forEach((item, idx) => {
            addNewLine1();
            const $linha = $('#linha-container_1 .linha_1').eq(idx);

            $linha.find('.quant_caixa_1').val(item.quant || '');
            $linha.find('.produto_1').val(item.variedade || '');
            $linha.find('.preco_kg_1').val(item.preco_kg ? parseFloat(item.preco_kg).toFixed(2).replace('.', ',') : '0,00');

            const rawTipo = item.tipo_caixa || '';
            const numTipo = rawTipo.split(' ')[0];
            $linha.find('.tipo_cx_1').val(numTipo);

            $linha.find('.preco_unit_1').val(item.preco_unit ? parseFloat(item.preco_unit).toFixed(2).replace('.', ',') : '0,00');
            $linha.find('.valor_1').val(item.valor ? parseFloat(item.valor).toFixed(2).replace('.', ',') : '0,00');
          });
        } else {
          addNewLine1();
        }

        // 3. Deduções e Impostos (FUNRURAL, IMA, ABANORTE, TAXA ADM)

        // FUNRURAL
        $('#info_funrural').val(r.funrural_config_info || '');
        if (r.funrural_config_preco_unit) {
          let unitF = parseFloat(r.funrural_config_preco_unit).toFixed(2).replace('.', ',') + '%';
          $('#preco_unit_funrural').val(unitF);
        }
        $('#valor_funrural').val(r.desc_funrural ? r.desc_funrural.replace('.', ',') : '0,00');

        // IMA
        $('#info_ima').val(r.ima_config_info || '');
        if (r.ima_config_preco_unit) {
          let unitI = parseFloat(r.ima_config_preco_unit).toFixed(2).replace('.', ',');
          $('#preco_unit_ima').val(unitI);
        }
        $('#valor_ima').val(r.desc_ima ? r.desc_ima.replace('.', ',') : '0,00');

        // ABANORTE
        $('#info_abanorte').val(r.abanorte_config_info || '');
        if (r.abanorte_config_preco_unit) {
          let valA = parseFloat(r.abanorte_config_preco_unit);
          // Tenta casar com formato percentual ou decimal dependendo do option
          let unitA = valA < 1 ? valA.toFixed(4).replace('.', ',') + '%' : valA.toFixed(2).replace('.', ',') + '%';
          $('#preco_unit_abanorte').val(unitA);
        }
        $('#valor_abanorte').val(r.desc_abanorte ? r.desc_abanorte.replace('.', ',') : '0,00');

        // TAXA ADM
        $('#taxa_adm_val_5').val(r.taxa_adm_config_taxa_perc ? parseFloat(r.taxa_adm_config_taxa_perc) : '');
        if (r.taxa_adm_config_preco_unit) {
          let unitT = parseFloat(r.taxa_adm_config_preco_unit).toFixed(2).replace('.', ',');
          $('#preco_unit_taxa_adm').val(unitT);
        }
        $('#valor_taxa_adm').val(r.desc_taxaadm ? r.desc_taxaadm.replace('.', ',') : '0,00');

        // 4. Descontos Diversos
        $('#discount-container').empty();
        let descontos = [];
        try {
          if (r.descontos_diversos && r.descontos_diversos.trim() !== "") {
            descontos = JSON.parse(r.descontos_diversos);
          }
        } catch (e) {
          console.warn('Erro parse descontos_diversos', e);
        }

        if (descontos && descontos.length > 0) {
          descontos.forEach((d, i) => {
            addDiscountLine();
            const $dlinha = $('#discount-container .linha_3').eq(i);
            $dlinha.find('.desconto-type').val(d.tipo || '+');
            $dlinha.find('.desconto-valor').val(d.valor ? parseFloat(d.valor).toFixed(2).replace('.', ',') : '0,00');
            $dlinha.find('.desconto-obs').val(d.obs || '');
          });
        } else {
          addDiscountLine();
        }

        // 5. Atualização de Totais da Tela
        if (typeof calcularTotalAbatimentos === 'function') calcularTotalAbatimentos();
        if (typeof calcularDescontosDiversos === 'function') calcularDescontosDiversos();
        if (typeof calculaTotais === 'function') calculaTotais();

        $('#modalForm').modal('show');
      },
      error: function(err) {
        console.error('Erro ao buscar dados:', err);
        alert('Não foi possível carregar os detalhes do registro.');
      }
    });
  }


  function handleInput(input) {
    if (isEditing) return; // IGNORA auto-add no fluxo de edição
    const linha = input.closest(".linha_1");
    const container = document.getElementById("linha-container_1");
    const allFilled = [...linha.querySelectorAll("input, select")].every(f => f.value.trim() !== "");
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
      data: {
        id
      },
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
        let htmlProdutos = ''; // Renomeado para evitar conflito com htmlDesc (boa prática)
        if (res.produtos && res.produtos.length) {
          res.produtos.forEach(item => {
            htmlProdutos += `
            <tr>
              <td>${item.nome_produto || '-'}</td>
              <td>${item.tipo_caixa || '-'}</td>
              <td>${item.quant || '0'}</td>  
              <td>${formatarNumero(item.preco_kg)}</td>
              <td>${formatarNumero(item.preco_unit)}</td>
              <td>${formatarNumero(item.valor)}</td>
            </tr>`;
          });
        } else {
          htmlProdutos = '<tr><td colspan="6" class="text-center">Nenhum produto encontrado</td></tr>';
        }
        $('#produtos_modal').html(htmlProdutos);

        // Deduções e Descontos Fixos (anteriormente "Comissões")
        // 1. Adicionar o preenchimento para desc_avista_perc_modal
        const descAvistaPercentual = r.desc_avista ? parseFloat(r.desc_avista).toFixed(2) : '0.00';
        $('#desc_avista_perc_modal').text(descAvistaPercentual + '%');

        $('#desc_funrural_modal').text('R$ ' + formatarNumero(r.desc_funrural));
        $('#desc_ima_modal').text('R$ ' + formatarNumero(r.desc_ima));
        $('#desc_abanorte_modal').text('R$ ' + formatarNumero(r.desc_abanorte));
        $('#desc_taxaadm_modal').text('R$ ' + formatarNumero(r.desc_taxaadm));

        // Descontos Diversos (JSON string -> array)
        let descontos = [];
        try {
          if (r.descontos_diversos && r.descontos_diversos.trim() !== "") {
            descontos = JSON.parse(r.descontos_diversos);
          }
        } catch (e) {
          console.warn('JSON inválido em descontos_diversos:', r.descontos_diversos, e);
          descontos = []; // Garante que descontos seja um array
        }

        let htmlDesc = '';
        if (descontos && descontos.length > 0) { // Adicionado verificação se descontos é um array e tem itens
          descontos.forEach(d => {
            // 2. Ajustar a ordem das colunas para: Tipo, Obs, Valor
            htmlDesc += `<tr>
            <td>${d.tipo || '-'}</td> 
            <td>${d.obs || ''}</td>
            <td>R$ ${formatarNumero(d.valor)}</td>
          </tr>`;
          });
        } else {
          htmlDesc = '<tr><td colspan="3" class="text-center">Nenhum desconto diverso informado</td></tr>';
        }
        $('#descontos_modal').html(htmlDesc);

        // Abre o modal
        $('#modalMostrarDados').modal('show');
      },
      error: function(err) {
        console.error('Erro ao buscar dados do romaneio:', err);
        // Tenta mostrar mais detalhes do erro se disponíveis
        let errorMsg = 'Não foi possível carregar os detalhes.';
        if (err.responseJSON && err.responseJSON.mensagem) {
          errorMsg = err.responseJSON.mensagem;
        } else if (err.responseText) {
          try {
            const parsedError = JSON.parse(err.responseText);
            if (parsedError && parsedError.mensagem) {
              errorMsg = parsedError.mensagem;
            }
          } catch (e) {
            // Se não for JSON, pode mostrar parte do texto do erro (cuidado com HTML)
            // errorMsg += "\nDetalhes: " + (err.responseText.substring(0, 200) || "Erro desconhecido.");
            console.warn("Resposta de erro não é JSON:", err.responseText);
          }
        }
        alert(errorMsg + ' Veja o console para mais detalhes técnicos.');
      }
    });
  }

  // Supondo que você tenha funções de formatação como estas (ajuste conforme necessário):
  function formatarData(dataStr) {
    if (!dataStr) return '-';
    // Exemplo: dataStr pode ser 'YYYY-MM-DD HH:MM:SS' ou 'YYYY-MM-DD'
    const dataObj = new Date(dataStr.split(' ')[0]); // Pega apenas a parte da data
    if (isNaN(dataObj.getTime())) return dataStr; // Retorna original se inválida
    // Adiciona 1 dia porque o new Date() pode interpretar como UTC e subtrair um dia dependendo do fuso
    dataObj.setDate(dataObj.getDate() + 1);
    return dataObj.toLocaleDateString('pt-BR'); // Formato DD/MM/YYYY
  }

  function formatarNumero(valor) {
    if (valor === null || valor === undefined || valor === '') return '0,00';
    let num = parseFloat(valor);
    if (isNaN(num)) return '0,00';
    return num.toLocaleString('pt-BR', {
      minimumFractionDigits: 2,
      maximumFractionDigits: 2
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
    console.log("=== INICIANDO FUNÇÃO LIMPAR CAMPOS ===");

    // 1) DEBUG: Mostra o valor do ID antes de tentar limpar
    var idAntes = $('#id').val();
    console.log("🔴 ID ATUAL (Antes de limpar):", idAntes);
    console.log("Tipo do dado:", typeof idAntes);

    $('#mensagem-sucesso').hide();
    $('#mensagem-erro').hide();

    // AÇÃO: Limpa o ID
    $('#id').val('');

    // 2) DEBUG: Mostra o valor do ID imediatamente após limpar
    var idDepois = $('#id').val();
    console.log("🟢 ID NOVO (Depois de limpar):", idDepois);

    // Verifica se limpou mesmo
    if (idDepois === "" || idDepois === null) {
      console.log("✅ Sucesso: O ID está vazio.");
    } else {
      console.error("❌ Erro: O ID NÃO ficou vazio!");
    }

    // --- Restante da limpeza ---

    $('.data_atual').val(new Date().toISOString().split('T')[0]);
    $('#fornecedor').val('');
    $('#plano_pgto').val('');
    $('#nota_fiscal').val('');
    $('#quant_dias').val('');
    $('#vencimento').val('');
    $('#fazenda').val('');
    $('#cliente').val('');
    $('#desc-avista').val('');

    // Limpa sessão de produtos
    $('#linha-container_1').empty();
    if (typeof addNewLine1 === 'function') {
      addNewLine1();
    }

    // Limpa totais de produtos
    $('#total_caixa').text('0 CXS');
    $('#total_kg').text('0 KG');
    $('#total_bruto').text('R$ 0,00');
    $('#desc-avista').text(''); // Cuidado: Se for input, use .val(''). Se for div/span, use .text('')
    $('#desc-avista').val(''); // Garante limpeza se for input
    $('#total-desc').text('R$ 0,00');
    $('#total-geral').text('0,00');
    $('#valor_liquido').val('0,00');

    // Limpa impostos e taxas
    $('#info_funrural, #preco_unit_funrural').prop('selectedIndex', 0);
    $('#valor_funrural').val('0,00');
    $('#info_ima, #preco_unit_ima').prop('selectedIndex', 0);
    $('#valor_ima').val('0,00');
    $('#info_abanorte, #preco_unit_abanorte').prop('selectedIndex', 0);
    $('#valor_abanorte').val('0,00');
    $('#taxa_adm_percent').val('');
    $('#preco_unit_taxa_adm').prop('selectedIndex', 0);
    $('#valor_taxa_adm').val('0,00');
    $('#total_comissao').text('0,00');

    // Limpa descontos diversos
    $('#discount-container').empty();
    if (typeof addDiscountLine === 'function') {
      addDiscountLine();
    }
    $('#total_descontos_diversos').text('0,00');

    // Limpa total líquido final
    $('#total_liquido_pagar').text('0,00');

    // Recalcula fórmulas
    if (typeof calculaTotais === 'function') calculaTotais();
    if (typeof calculaTotais2 === 'function') calculaTotais2();
    if (typeof calcularDescontosDiversos === 'function') calcularDescontosDiversos();
    if (typeof updateLiquidPayable === 'function') updateLiquidPayable();

    console.log("=== FIM FUNÇÃO LIMPAR CAMPOS ===");
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