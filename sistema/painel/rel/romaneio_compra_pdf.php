<?php
// romaneio_compra_pdf.php
require_once("../../conexao.php");

$id = $_GET['id'];

// Buscar dados do romaneio de compra
$query = $pdo->prepare("
  SELECT 
    rc.*,
    f.nome_atacadista AS nome_fornecedor,
    p.nome            AS nome_plano,
    c.nome            AS nome_cliente
  FROM romaneio_compra rc
  LEFT JOIN fornecedores f ON rc.fornecedor = f.id
  LEFT JOIN planos_pgto    p ON rc.plano_pgto  = p.id
  LEFT JOIN clientes       c ON rc.cliente     = c.id
  WHERE rc.id = :id
");
$query->bindValue(":id", $id);
$query->execute();
$romaneio = $query->fetch(PDO::FETCH_ASSOC);

// Buscar produtos
$query = $pdo->prepare("
  SELECT 
    lpc.*,
    p.nome   AS nome_produto,
    tc.tipo  AS tipo_caixa
  FROM linha_produto_compra lpc
  LEFT JOIN produtos   p  ON lpc.variedade   = p.id
  LEFT JOIN tipo_caixa tc ON lpc.tipo_caixa = tc.id
  WHERE lpc.id_romaneio = :id
");
$query->bindValue(":id", $id);
$query->execute();
$produtos = $query->fetchAll(PDO::FETCH_ASSOC);

// Decodificar descontos diversos (JSON)
$descontos = json_decode($romaneio['descontos_diversos'] ?? '[]', true) ?: [];
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
  <meta charset="utf-8">
  <title>Romaneio de Compra</title>
  <style>
    * { margin:0; padding:0; box-sizing:border-box; font-family:Arial,sans-serif; font-size:10px; }
    table { border-collapse:collapse; width:100%; }
    th, td { border:1px solid #000; padding:4px; vertical-align:middle; }

    /* cabeçalho */
    .logo-cell { border:none; }
    .logo-cell img { width:200px; }
    .title-cell { text-align:center; font-weight:bold; }
    .rom-label, .rom-number { text-align:center; color:#fff; background:#D32F2F; font-weight:bold; }
    .rom-label { border-right:none; }
    .rom-number { border-left:none; }

    /* info */
    .info-label { font-weight:bold; width:15%; }
    .info-value { text-align:center; width:20%; }

    /* tabelas */
    .tabela-dados th { background:#eee; text-align:left; }
    .col-quant { width:10%; }
    .col-variedade { width:30%; }
    .col-preco { width:15%; }
    .col-tipo { width:15%; }
    .col-unit { width:15%; }
    .col-valor { width:15%; }

    /* cores de total */
    .total-bruto td { background:#C5E0B3; font-weight:bold; }
    /* remove amarelo */
    .desconto-vista td { background:none; }
    .total-liquido td { background:#C5E0B3; font-weight:bold; }

    /* comissão/abatimentos */
    .comissao th { background:#eee; text-align:left; }
    .comissao .total-comissao td { background:#C5E0B3; font-weight:bold; }

    /* descontos diversos */
    .descontos th { background:#eee; text-align:left; }
    .descontos .total-diversos td { background:#C5E0B3; font-weight:bold; }

    /* assinatura centralizada */
    .assinatura { 
      margin:20px auto 0; 
      border-top:1px solid #000; 
      width:200px; 
      text-align:center; 
      padding-top:5px; 
    }
  </style>
</head>
<body>

  <!-- cabeçalho -->
  <table>
    <tr>
      <td class="logo-cell" rowspan="3">
        <img src="<?= $url_sistema ?>/img/foto-painel.png" alt="Logo errada">
      </td>
      <td class="title-cell" colspan="2">ROMANEIO DE COMPRA</td>
      <td class="rom-label">Rom nº</td>
      <td class="rom-number"><?= str_pad($romaneio['id'],6,'0',STR_PAD_LEFT) ?></td>
    </tr>
    <tr>
      <td class="info-label">DATA:</td>
      <td class="info-value"><?= date('d/m/Y',strtotime($romaneio['data'])) ?></td>
      <td class="info-label">CLIENTE - ATAC.</td>
      <td class="info-value"><?= $romaneio['nome_cliente'] ?></td>
    </tr>
    <tr>
      <td class="info-label">PLANO PGTº</td>
      <td class="info-value"><?= $romaneio['nome_plano'] ?> <?= $romaneio['quant_dias'] ?></td>
      <td class="info-label">VENCIMENTO</td>
      <td class="info-value"><?= date('d/m/Y',strtotime($romaneio['vencimento'])) ?></td>
    </tr>
  </table>

  <!-- fornecedor / fazenda -->
  <table>
    <tr>
      <td colspan="5" style="font-weight:bold;">
        FORNECEDOR - PROD. RURAL: <?= $romaneio['nome_fornecedor'] ?> /
        FAZENDA: <?= $romaneio['fazenda'] ?>
      </td>
    </tr>
  </table>

  <!-- produtos -->
  <table class="tabela-dados">
    <tr>
      <th class="col-quant">QUANT. CX</th>
      <th class="col-variedade">VARIEDADE</th>
      <th class="col-preco">PREÇO KG</th>
      <th class="col-tipo">TIPO CX</th>
      <th class="col-unit">PREÇO UNIT.</th>
      <th class="col-valor">VALOR R$</th>
    </tr>
    <?php foreach($produtos as $p): ?>
    <tr>
      <td class="col-quant"><?= $p['quant'] ?></td>
      <td class="col-variedade"><?= $p['nome_produto'] ?></td>
      <td class="col-preco">R$ <?= number_format($p['preco_kg'],2,',','.') ?></td>
      <td class="col-tipo"><?= $p['tipo_caixa'] ?> KG</td>
      <td class="col-unit">R$ <?= number_format($p['preco_unit'],2,',','.') ?></td>
      <td class="col-valor">R$ <?= number_format($p['valor'],2,',','.') ?></td>
    </tr>
    <?php endforeach; ?>
    <tr class="total-bruto">
      <td colspan="5" style="text-align:left;">TOTAL BRUTO</td>
      <td>R$ <?= number_format(array_sum(array_column($produtos,'valor')),2,',','.') ?></td>
    </tr>
  </table>

  <!-- comissões fixas -->
  <table class="tabela-dados comissao">
    <tr class="desconto-vista">
      <td>FUNRURAL</td><td colspan="4"></td>
      <td>R$ <?= number_format($romaneio['desc_funrural'],2,',','.') ?></td>
    </tr>
    <tr>
      <td>IMA</td><td colspan="4"></td>
      <td>R$ <?= number_format($romaneio['desc_ima'],2,',','.') ?></td>
    </tr>
    <tr>
      <td>ABANORTE</td><td colspan="4"></td>
      <td>R$ <?= number_format($romaneio['desc_abanorte'],2,',','.') ?></td>
    </tr>
    <tr>
      <td>TAXA ADM</td><td colspan="4"></td>
      <td>R$ <?= number_format($romaneio['desc_taxaadm'],2,',','.') ?></td>
    </tr>
    <tr class="total-comissao">
      <td colspan="5" style="text-align:left;">TOTAL IMPOSTOS E TAXAS</td>
      <td>
        R$ <?= number_format(
          $romaneio['desc_funrural']
        + $romaneio['desc_ima']
        + $romaneio['desc_abanorte']
        + $romaneio['desc_taxaadm'],2,',','.') ?>
      </td>
    </tr>
  </table>

  <!-- descontos diversos -->
  <table class="tabela-dados descontos">
    <tr>
      <th>TIPO</th><th>OBS</th><th>VALOR R$</th>
    </tr>
    <?php foreach($descontos as $d): ?>
    <tr>
      <td><?= $d['tipo']==='+'?'Adicionar':'Subtrair' ?></td>
      <td><?= $d['obs'] ?></td>
      <td>R$ <?= number_format($d['valor'],2,',','.') ?></td>
    </tr>
    <?php endforeach; ?>
    <tr class="total-diversos">
      <td colspan="2" style="text-align:left;">VALOR LÍQUIDO A PAGAR</td>
      <td>R$ <?= number_format($romaneio['total_liquido'],2,',','.') ?></td>
    </tr>
  </table>

  <div class="assinatura">ASS. Emitente Resp.</div>

</body>
</html>
