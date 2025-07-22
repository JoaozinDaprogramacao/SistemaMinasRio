<?php
require_once("../../conexao.php");

$id = $_GET['id'];

// BUSCAR DADOS (sem alterações aqui)
$query = $pdo->prepare("SELECT rv.*, c.nome as nome_cliente, p.nome as nome_plano 
    FROM romaneio_venda rv 
    LEFT JOIN clientes c ON rv.atacadista = c.id 
    LEFT JOIN planos_pgto p ON rv.plano_pgto = p.id 
    WHERE rv.id = :id");
$query->bindValue(":id", $id);
$query->execute();
$romaneio = $query->fetch(PDO::FETCH_ASSOC);

$query = $pdo->prepare("SELECT lp.*, p.nome as nome_produto, tc.tipo as tipo_caixa 
    FROM linha_produto lp 
    LEFT JOIN produtos p ON lp.variedade = p.id 
    LEFT JOIN tipo_caixa tc ON lp.tipo_caixa = tc.id 
    WHERE lp.id_romaneio = :id");
$query->bindValue(":id", $id);
$query->execute();
$produtos = $query->fetchAll(PDO::FETCH_ASSOC);

$query = $pdo->prepare("SELECT lc.*, tc.tipo as tipo_caixa 
    FROM linha_comissao lc 
    LEFT JOIN tipo_caixa tc ON lc.tipo_caixa = tc.id 
    WHERE lc.id_romaneio = :id");
$query->bindValue(":id", $id);
$query->execute();
$comissoes = $query->fetchAll(PDO::FETCH_ASSOC);

$query = $pdo->prepare("SELECT lo.*, m.nome as nome_material 
    FROM linha_observacao lo 
    LEFT JOIN materiais m ON lo.descricao = m.id 
    WHERE lo.id_romaneio = :id");
$query->bindValue(":id", $id);
$query->execute();
$materiais = $query->fetchAll(PDO::FETCH_ASSOC);

// Pré-cálculos (sem alterações aqui)
$total_bruto_banana = array_sum(array_column($produtos, 'valor'));
$total_comissao = array_sum(array_column($comissoes, 'valor'));
$total_materiais = array_sum(array_column($materiais, 'valor'));
$valor_total_final = ($romaneio['total_liquido'] ?? 0) + $total_comissao - $total_materiais;
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="utf-8">
    <title>Romaneio de Venda</title>
    <style>
        /* --- CSS IDÊNTICO AO ROMANEIO_COMPRA --- */
        * { margin:0; padding:0; box-sizing:border-box; font-family:Arial,sans-serif; font-size:10px; }
        table { border-collapse:collapse; width:100%; }
        th, td { border:1px solid #000; padding:4px; vertical-align:middle; }

        /* cabeçalho */
        .logo-cell { border:none; }
        .logo-cell img { width:200px; } /* CORREÇÃO: Tamanho do logo ajustado para 200px */
        .title-cell { text-align:center; font-weight:bold; } /* CORREÇÃO: Removido font-size explícito */
        .rom-label, .rom-number { text-align:center; color:#fff; background:#D32F2F; font-weight:bold; } /* CORREÇÃO: Cor do vermelho ajustada */
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

        /* CORREÇÃO: Usando as classes de total específicas do romaneio_compra */
        .total-bruto td,
        .total-liquido td,
        .total-comissao td,
        .total-materiais td,
        .total-final td { 
            background:#C5E0B3; 
            font-weight:bold; 
        }

        /* assinatura */
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

<table>
    <tr>
        <td class="logo-cell" rowspan="3">
            <img src="<?= $url_sistema ?>img/logo.jpg" alt="Logo">
        </td>
        <td class="title-cell" colspan="2">ROMANEIO DE VENDAS</td>
        <td class="rom-label">Rom nº</td>
        <td class="rom-number"><?= str_pad($romaneio['id'], 6, '0', STR_PAD_LEFT) ?></td>
    </tr>
    <tr>
        <td class="info-label">DATA:</td>
        <td class="info-value"><?= date('d/m/Y', strtotime($romaneio['data'])) ?></td>
        <td class="info-label">PLANO PGTº:</td>
        <td class="info-value"><?= $romaneio['nome_plano'] ?></td>
    </tr>
    <tr>
        <td class="info-label">VENCIMENTO:</td>
        <td class="info-value"><?= date('d/m/Y', strtotime($romaneio['vencimento'])) ?></td>
        <td class="info-label">NOTA FISCAL:</td>
        <td class="info-value"><?= $romaneio['nota_fiscal'] ?></td>
    </tr>
</table>

<table>
    <tr>
        <td colspan="5" style="font-weight:bold;">
            CLIENTE ATACADISTA: <?= $romaneio['nome_cliente'] ?>
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
        <?php foreach($produtos as $prod): ?>
        <tr>
            <td><?= $prod['quant'] ?></td>
            <td><?= $prod['nome_produto'] ?></td>
            <td style="text-align: right;">R$ <?= number_format($prod['preco_kg'], 2, ',', '.') ?></td>
            <td><?= $prod['tipo_caixa'] ?> KG</td>
            <td style="text-align: right;">R$ <?= number_format($prod['preco_unit'], 2, ',', '.') ?></td>
            <td style="text-align: right;">R$ <?= number_format($prod['valor'], 2, ',', '.') ?></td>
        </tr>
        <?php endforeach; ?>
        <tr class="total-bruto">
            <td colspan="5">TOTAL BRUTO - BANANA</td>
            <td style="text-align: right;">R$ <?= number_format($total_bruto_banana, 2, ',', '.') ?></td>
        </tr>
        <tr>
            <td colspan="5">DESCONTO RECEBIMENTO À VISTA 5%</td>
            <td style="text-align: right;">R$ <?= number_format($romaneio['desconto'], 2, ',', '.') ?></td>
        </tr>
        <tr class="total-liquido">
            <td colspan="5">TOTAL LÍQUIDO - BANANA</td>
            <td style="text-align: right;">R$ <?= number_format($romaneio['total_liquido'], 2, ',', '.') ?></td>
        </tr>
    </tbody>
</table>

<?php if (!empty($comissoes)): ?>
<table class="tabela-dados">
    <thead>
        <tr>
            <th>DESCRIÇÃO</th>
            <th>QUANT. CX</th>
            <th>PREÇO KG</th>
            <th>TIPO CX</th>
            <th>PREÇO UNIT.</th>
            <th>VALOR R$</th>
        </tr>
    </thead>
    <tbody>
        <?php foreach($comissoes as $com): ?>
        <tr>
            <td>COMISSÃO</td>
            <td><?= $com['quant_caixa'] ?></td>
            <td style="text-align: right;">R$ <?= number_format($com['preco_kg'], 2, ',', '.') ?></td>
            <td><?= $com['tipo_caixa'] ?> KG</td>
            <td style="text-align: right;">R$ <?= number_format($com['preco_unit'], 2, ',', '.') ?></td>
            <td style="text-align: right;">R$ <?= number_format($com['valor'], 2, ',', '.') ?></td>
        </tr>
        <?php endforeach; ?>
        <tr class="total-comissao">
            <td colspan="5">TOTAL COMISSÃO</td>
            <td style="text-align: right;">R$ <?= number_format($total_comissao, 2, ',', '.') ?></td>
        </tr>
    </tbody>
</table>
<?php endif; ?>

<?php if (!empty($materiais)): ?>
<table class="tabela-dados">
    <thead>
        <tr>
            <th style="width: 40%;">OBSERVAÇÕES</th>
            <th style="width: 25%;">DESCRIÇÃO</th>
            <th style="width: 10%;">QUANT.</th>
            <th style="width: 10%;">UNIT. R$</th>
            <th style="width: 15%;">VALOR R$</th>
        </tr>
    </thead>
    <tbody>
        <?php foreach($materiais as $mat): ?>
        <tr>
            <td><?= $mat['observacoes'] ?></td>
            <td><?= $mat['nome_material'] ?></td>
            <td><?= $mat['quant'] ?></td>
            <td style="text-align: right;"><?= number_format($mat['preco_unit'], 2, ',', '.') ?></td>
            <td style="text-align: right;">R$ <?= number_format($mat['valor'], 2, ',', '.') ?></td>
        </tr>
        <?php endforeach; ?>
        <tr class="total-materiais">
            <td colspan="4">TOTAL MATERIAIS (a deduzir)</td>
            <td style="text-align: right;">R$ <?= number_format($total_materiais, 2, ',', '.') ?></td>
        </tr>
    </tbody>
</table>
<?php endif; ?>

<table>
    <tbody>
        <tr class="total-final">
            <td style="width: 85%;" colspan="4">VALOR TOTAL A RECEBER</td>
            <td style="width: 15%; text-align: right;">R$ <?= number_format($valor_total_final, 2, ',', '.') ?></td>
        </tr>
    </tbody>
</table>

<div class="assinatura">
    ASS. Emitente Resp.
</div>

</body>
</html>