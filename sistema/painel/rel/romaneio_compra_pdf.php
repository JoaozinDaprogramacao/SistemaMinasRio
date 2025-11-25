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

// --- CÁLCULOS COM A SOLUÇÃO DEFINITIVA ---
$total_bruto = array_sum(array_column($produtos, 'valor'));

// 1. Converte o texto "6.00" do banco para o número 6.0 (float)
$percentual_desconto = floatval($romaneio['desc_avista'] ?? 0);

// 2. O resto dos cálculos funciona normalmente
$valor_desconto_calculado = ($total_bruto * $percentual_desconto) / 100;
$total_liquido_parcial = $total_bruto - $valor_desconto_calculado;
?>
<!DOCTYPE html>
<html lang="pt-BR">

<head>
  <meta charset="utf-8">
  <title>Romaneio de Compra</title>
  <style>
    * {
      margin: 0;
      padding: 0;
      box-sizing: border-box;
      font-family: Arial, sans-serif;
      font-size: 10px;
    }

    table {
      border-collapse: collapse;
      width: 100%;
    }

    th,
    td {
      border: 1px solid #000;
      padding: 4px;
      vertical-align: middle;
    }

    .logo-cell {
      border: none;
    }

    .logo-cell img {
      width: 200px;
    }

    .title-cell {
      text-align: center;
      font-weight: bold;
    }

    .rom-label,
    .rom-number {
      text-align: center;
      color: #fff;
      background: #D32F2F;
      font-weight: bold;
    }

    .rom-label {
      border-right: none;
    }

    .rom-number {
      border-left: none;
    }

    .info-label {
      font-weight: bold;
      width: 15%;
    }

    .info-value {
      text-align: center;
      width: 20%;
    }

    .tabela-dados th {
      background: #eee;
      text-align: left;
    }

    .col-quant {
      width: 10%;
    }

    .col-variedade {
      width: 30%;
    }

    .col-preco {
      width: 15%;
    }

    .col-tipo {
      width: 15%;
    }

    .col-unit {
      width: 15%;
    }

    .col-valor {
      width: 15%;
    }

    .total-bruto td,
    .total-liquido td {
      background: #C5E0B3;
      font-weight: bold;
    }

    .desconto-vista td {
      background: none;
    }

    .comissao th {
      background: #eee;
      text-align: left;
    }

    .comissao .total-comissao td {
      background: #C5E0B3;
      font-weight: bold;
    }

    .descontos th {
      background: #eee;
      text-align: left;
    }

    .descontos .total-diversos td {
      background: #C5E0B3;
      font-weight: bold;
    }

    .assinatura {
      margin: 20px auto 0;
      border-top: 1px solid #000;
      width: 200px;
      text-align: center;
      padding-top: 5px;
    }
  </style>
</head>

<body>

  <table>
    <tr>
      <td class="logo-cell" rowspan="3">
        <img style="margin-top: 7px; margin-left: 7px;" id="imag" src="<?php echo $url_sistema ?>img/logo.jpg" width="110px">
      </td>
      <td class="title-cell" colspan="2">ROMANEIO DE COMPRA</td>
      <td class="rom-label">Rom nº</td>
      <td class="rom-number"><?= str_pad($romaneio['id'], 6, '0', STR_PAD_LEFT) ?></td>
    </tr>
    <tr>
      <td class="info-label">DATA:</td>
      <td class="info-value"><?= date('d/m/Y', strtotime($romaneio['data'])) ?></td>
      <td class="info-label">CLIENTE - ATAC.</td>
      <td class="info-value"><?= $romaneio['nome_cliente'] ?></td>
    </tr>
    <tr>
      <td class="info-label">PLANO PGTº</td>
      <td class="info-value"><?= $romaneio['nome_plano'] ?> <?= $romaneio['quant_dias'] ?></td>
      <td class="info-label">VENCIMENTO</td>
      <td class="info-value"><?= date('d/m/Y', strtotime($romaneio['vencimento'])) ?></td>
    </tr>
  </table>

  <table>
    <tr>
      <td colspan="5" style="font-weight:bold;">
        FORNECEDOR - PROD. RURAL: <?= $romaneio['nome_fornecedor'] ?> /
        FAZENDA: <?= $romaneio['fazenda'] ?>
      </td>
    </tr>
  </table>

  <table class="tabela-dados">
    <thead>
      <tr>
        <th class="col-quant">QUANT. CX</th>
        <th class="col-variedade">VARIEDADE</th>
        <th class="col-preco">PREÇO KG</th>
        <th class="col-tipo">TIPO CX</th>
        <th class="col-unit">PREÇO UNIT.</th>
        <th class="col-valor">VALOR R$</th>
      </tr>
    </thead>
    <tbody>
      <?php foreach ($produtos as $p): ?>
        <tr>
          <td class="col-quant"><?= $p['quant'] ?></td>
          <td class="col-variedade"><?= $p['nome_produto'] ?></td>
          <td style="text-align: right;">R$ <?= number_format($p['preco_kg'], 2, ',', '.') ?></td>
          <td class="col-tipo"><?= $p['tipo_caixa'] ?> KG</td>
          <td style="text-align: right;">R$ <?= number_format($p['preco_unit'], 2, ',', '.') ?></td>
          <td style="text-align: right;">R$ <?= number_format($p['valor'], 2, ',', '.') ?></td>
        </tr>
      <?php endforeach; ?>
      <tr class="total-bruto">
        <td colspan="5">TOTAL BRUTO</td>
        <td style="text-align: right;">R$ <?= number_format($total_bruto, 2, ',', '.') ?></td>
      </tr>

      <?php if ($percentual_desconto > 0): ?>
        <tr>
          <td colspan="5">DESCONTO PAGAMENTO À VISTA (<?= str_replace('.', ',', $percentual_desconto) ?>%)</td>
          <td style="text-align: right;">R$ <?= number_format($valor_desconto_calculado, 2, ',', '.') ?></td>
        </tr>
        <tr class="total-liquido">
          <td colspan="5">TOTAL LÍQUIDO (APÓS DESCONTO)</td>
          <td style="text-align: right;">R$ <?= number_format($total_liquido_parcial, 2, ',', '.') ?></td>
        </tr>
      <?php endif; ?>
    </tbody>
  </table>

  <table class="tabela-dados comissao">
    <tbody>
      <tr>
        <td>FUNRURAL</td>
        <td colspan="4"></td>
        <td style="text-align: right;">R$ <?= number_format($romaneio['desc_funrural'], 2, ',', '.') ?></td>
      </tr>
      <tr>
        <td>IMA</td>
        <td colspan="4"></td>
        <td style="text-align: right;">R$ <?= number_format($romaneio['desc_ima'], 2, ',', '.') ?></td>
      </tr>
      <tr>
        <td>ABANORTE</td>
        <td colspan="4"></td>
        <td style="text-align: right;">R$ <?= number_format($romaneio['desc_abanorte'], 2, ',', '.') ?></td>
      </tr>
      <tr>
        <td>TAXA ADM</td>
        <td colspan="4"></td>
        <td style="text-align: right;">R$ <?= number_format($romaneio['desc_taxaadm'], 2, ',', '.') ?></td>
      </tr>
      <tr class="total-comissao">
        <td colspan="5">TOTAL IMPOSTOS E TAXAS</td>
        <td style="text-align: right;">
          R$ <?= number_format(
                ($romaneio['desc_funrural'] ?? 0)
                  + ($romaneio['desc_ima'] ?? 0)
                  + ($romaneio['desc_abanorte'] ?? 0)
                  + ($romaneio['desc_taxaadm'] ?? 0),
                2,
                ',',
                '.'
              ) ?>
        </td>
      </tr>
    </tbody>
  </table>

  <table class="tabela-dados descontos">
    <thead>
      <tr>
        <th>TIPO</th>
        <th>OBS</th>
        <th>VALOR R$</th>
      </tr>
    </thead>
    <tbody>
      <?php foreach ($descontos as $d): ?>
        <tr>
          <td><?= htmlspecialchars($d['tipo']) === '+' ? 'Adicionar' : 'Subtrair' ?></td>
          <td><?= htmlspecialchars($d['obs']) ?></td>
          <td style="text-align: right;">R$ <?= number_format($d['valor'], 2, ',', '.') ?></td>
        </tr>
      <?php endforeach; ?>
      <tr class="total-diversos">
        <td colspan="2">VALOR LÍQUIDO A PAGAR</td>
        <td style="text-align: right;">R$ <?= number_format($romaneio['total_liquido'], 2, ',', '.') ?></td>
      </tr>
    </tbody>
  </table>

  <div class="assinatura">ASS. Emitente Resp.</div>

</body>

</html>