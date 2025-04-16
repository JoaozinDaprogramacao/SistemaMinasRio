<?php
require_once("../../conexao.php");

$id = $_GET['id'];

// Buscar dados do romaneio
$query = $pdo->prepare("SELECT rv.*, c.nome as nome_cliente, p.nome as nome_plano 
    FROM romaneio_venda rv 
    LEFT JOIN clientes c ON rv.atacadista = c.id 
    LEFT JOIN planos_pgto p ON rv.plano_pgto = p.id 
    WHERE rv.id = :id");
$query->bindValue(":id", $id);
$query->execute();
$romaneio = $query->fetch(PDO::FETCH_ASSOC);

// Buscar produtos
$query = $pdo->prepare("SELECT lp.*, p.nome as nome_produto, tc.tipo as tipo_caixa 
    FROM linha_produto lp 
    LEFT JOIN produtos p ON lp.variedade = p.id 
    LEFT JOIN tipo_caixa tc ON lp.tipo_caixa = tc.id 
    WHERE lp.id_romaneio = :id");
$query->bindValue(":id", $id);
$query->execute();
$produtos = $query->fetchAll(PDO::FETCH_ASSOC);

// Buscar comissões
$query = $pdo->prepare("SELECT lc.*, tc.tipo as tipo_caixa 
    FROM linha_comissao lc 
    LEFT JOIN tipo_caixa tc ON lc.tipo_caixa = tc.id 
    WHERE lc.id_romaneio = :id");
$query->bindValue(":id", $id);
$query->execute();
$comissoes = $query->fetchAll(PDO::FETCH_ASSOC);

// Buscar materiais/observações
$query = $pdo->prepare("SELECT lo.*, m.nome as nome_material 
    FROM linha_observacao lo 
    LEFT JOIN materiais m ON lo.descricao = m.id 
    WHERE lo.id_romaneio = :id");
$query->bindValue(":id", $id);
$query->execute();
$materiais = $query->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Romaneio de Vendas</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            font-family: Arial, sans-serif;
            font-size: 10px; /* Fonte menor para caber mais conteúdo */
        }

        .cabecalho {
            width: 100%;
            border: 1px solid #000;
            margin-bottom: 5px;
        }

        .logo {
            width: 120px; /* Logo um pouco menor */
            height: auto;
            float: left;
            margin: 5px;
        }

        .titulo {
            color: #dc3545;
            text-align: right;
            padding: 5px;
            font-size: 14px;
            font-weight: bold;
        }

        .info-romaneio {
            width: 100%;
            margin-bottom: 5px;
        }

        .info-romaneio td {
            border: 1px solid #000;
            padding: 2px 4px;
            height: 20px;
        }

        .tabela-dados {
            width: 100%;
            margin-bottom: 3px;
        }

        .tabela-dados th, 
        .tabela-dados td {
            border: 1px solid #000;
            padding: 2px 4px;
            height: 18px; /* Altura fixa para as células */
        }

        .tabela-dados th {
            font-weight: normal;
            text-align: left;
            background-color: #fff;
        }

        .total-bruto,
        .total-comissao,
        .total-materiais {
            background-color: #c5e0b3;
        }

        .desconto-vista {
            background-color: #ffeb9c;
        }

        .valor-total {
            background-color: #c5e0b3;
            font-weight: bold;
            text-align: right;
            padding: 4px;
        }

        /* Ajuste das larguras das colunas */
        .col-quant { width: 10%; }
        .col-variedade { width: 30%; }
        .col-preco { width: 15%; }
        .col-tipo { width: 15%; }
        .col-unit { width: 15%; }
        .col-valor { width: 15%; }

        .assinatura {
            margin-top: 30px;
            border-top: 1px solid #000;
            width: 200px;
            text-align: center;
            padding-top: 5px;
            font-size: 10px;
        }

        /* Espaçamento entre as seções */
        .espaco-secao {
            height: 3px;
        }
    </style>
</head>
<body>
    <div class="cabecalho">
        <img src="<?= $url_sistema ?>img/logo.jpg" class="logo">
        <div class="titulo">ROMANEIO DE VENDAS</div>
    </div>

    <table class="info-romaneio">
        <tr>
            <td>Rom. Nº <?= str_pad($romaneio['id'], 6, '0', STR_PAD_LEFT) ?></td>
            <td>DATA: <?= date('d/m/Y', strtotime($romaneio['data'])) ?></td>
            <td>NOTA FISCAL: <?= $romaneio['nota_fiscal'] ?></td>
        </tr>
        <tr>
            <td>CLIENTE ATACADISTA: <?= $romaneio['nome_cliente'] ?></td>
            <td>PLANO PGTº: <?= $romaneio['nome_plano'] ?></td>
            <td>VENCIMENTO: <?= date('d/m/Y', strtotime($romaneio['vencimento'])) ?></td>
        </tr>
    </table>

    <table class="tabela-dados">
        <tr>
            <th class="col-quant">QUANT. CX</th>
            <th class="col-variedade">VARIEDADE</th>
            <th class="col-preco">PREÇO KG</th>
            <th class="col-tipo">TIPO CX</th>
            <th class="col-unit">PREÇO UNIT.</th>
            <th class="col-valor">VALOR R$</th>
        </tr>
        <?php foreach($produtos as $prod): ?>
        <tr>
            <td class="col-quant"><?= $prod['quant'] ?></td>
            <td class="col-variedade"><?= $prod['nome_produto'] ?></td>
            <td class="col-preco">R$ <?= number_format($prod['preco_kg'], 2, ',', '.') ?></td>
            <td class="col-tipo"><?= $prod['tipo_caixa'] ?></td>
            <td class="col-unit">R$ <?= number_format($prod['preco_unit'], 2, ',', '.') ?></td>
            <td class="col-valor">R$ <?= number_format($prod['valor'], 2, ',', '.') ?></td>
        </tr>
        <?php endforeach; ?>
        <tr class="total-bruto">
            <td class="col-quant" colspan="5">TOTAL BRUTO - BANANA</td>
            <td class="col-valor">R$ <?= number_format(array_sum(array_column($produtos, 'valor')), 2, ',', '.') ?></td>
        </tr>
        <tr class="desconto-vista">
            <td class="col-quant" colspan="5">DESCONTO RECEBIMENTO À VISTA 5%</td>
            <td class="col-valor">R$ <?= number_format($romaneio['desconto'], 2, ',', '.') ?></td>
        </tr>
        <tr class="total-liquido">
            <td class="col-quant" colspan="5">TOTAL LÍQUIDO - BANANA</td>
            <td class="col-valor">R$ <?= number_format($romaneio['total_liquido'], 2, ',', '.') ?></td>
        </tr>
    </table>

    <table class="tabela-dados">
        <tr>
            <th>DESCRIÇÃO</th>
            <th>QUANT. CX</th>
            <th>PREÇO KG</th>
            <th>TIPO CX</th>
            <th>PREÇO UNIT.</th>
            <th>VALOR R$</th>
        </tr>
        <?php foreach($comissoes as $com): ?>
        <tr>
            <td>COMISSÃO</td>
            <td><?= $com['quant_caixa'] ?></td>
            <td>R$ <?= number_format($com['preco_kg'], 2, ',', '.') ?></td>
            <td><?= $com['tipo_caixa'] ?></td>
            <td>R$ <?= number_format($com['preco_unit'], 2, ',', '.') ?></td>
            <td>R$ <?= number_format($com['valor'], 2, ',', '.') ?></td>
        </tr>
        <?php endforeach; ?>
        <tr class="total-comissao">
            <td colspan="5">TOTAL COMISSÃO</td>
            <td>R$ <?= number_format(array_sum(array_column($comissoes, 'valor')), 2, ',', '.') ?></td>
        </tr>
    </table>

    <table class="tabela-dados">
        <tr>
            <th>OBSERVAÇÕES</th>
            <th>DESCRIÇÃO</th>
            <th>QUANT.</th>
            <th>UNIT.</th>
            <th>VALOR R$</th>
        </tr>
        <?php foreach($materiais as $mat): ?>
        <tr>
            <td><?= $mat['observacoes'] ?></td>
            <td><?= $mat['nome_material'] ?></td>
            <td><?= $mat['quant'] ?></td>
            <td><?= $mat['preco_unit'] ?></td>
            <td>R$ <?= number_format($mat['valor'], 2, ',', '.') ?></td>
        </tr>
        <?php endforeach; ?>
        <tr class="total-materiais">
            <td colspan="4">TOTAL MATERIAIS</td>
            <td>R$ <?= number_format(array_sum(array_column($materiais, 'valor')), 2, ',', '.') ?></td>
        </tr>
    </table>

    <div class="assinatura">
        ASS. Emitente Resp.
    </div>
</body>
</html> 