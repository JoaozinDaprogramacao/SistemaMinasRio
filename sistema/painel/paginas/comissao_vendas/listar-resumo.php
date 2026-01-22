<?php
require_once("../../../conexao.php");

// 1. RECEBE OS FILTROS
$dataInicial = @$_POST['dataInicial'];
$dataFinal = @$_POST['dataFinal'];
$cliente = @$_POST['cliente'];

// Montagem do filtro WHERE para as tabelas relacionadas ao romaneio
$where = " WHERE 1=1 ";
if (!empty($dataInicial)) $where .= " AND rv.data >= '$dataInicial' ";
if (!empty($dataFinal)) $where .= " AND rv.data <= '$dataFinal' ";
if (!empty($cliente) && $cliente !== "0") $where .= " AND rv.atacadista = '$cliente' ";

// 2. BUSCAR MATERIAIS (DA LINHA_OBSERVACAO)
// Agrupamos por material para mostrar "cada material cadastrado" que teve movimento
$sql_materiais = "SELECT 
                    m.nome as material_nome,
                    SUM(lo.quant) as qtd_total,
                    SUM(lo.valor) as valor_total_material
                  FROM linha_observacao lo
                  INNER JOIN materiais m ON lo.descricao = m.id
                  INNER JOIN romaneio_venda rv ON lo.id_romaneio = rv.id
                  $where
                  GROUP BY m.id, m.nome";
$query_mat = $pdo->query($sql_materiais);
$res_mat = $query_mat->fetchAll(PDO::FETCH_ASSOC);

// 3. BUSCAR FINANCEIRO DO ROMANEIO (COMISSÕES, DESCONTOS, ACRÉSCIMOS)
// Buscamos a comissão bruta na linha_comissao e os descontos/adicionais no romaneio_venda
$sql_financeiro = "SELECT 
    (SELECT SUM(lc.valor) FROM linha_comissao lc INNER JOIN romaneio_venda rv2 ON lc.id_romaneio = rv2.id " . str_replace('rv.', 'rv2.', $where) . ") as total_comis_bruta,
    SUM(rv.desconto) as total_descontos,
    SUM(rv.adicional) as total_acrescimos,
    (SELECT SUM(lp.valor) FROM linha_produto lp INNER JOIN romaneio_venda rv3 ON lp.id_romaneio = rv3.id " . str_replace('rv.', 'rv3.', $where) . ") as total_banana
FROM romaneio_venda rv
$where";

$query_fin = $pdo->query($sql_financeiro);
$res_fin = $query_fin->fetch(PDO::FETCH_ASSOC);

$comis_bruta = $res_fin['total_comis_bruta'] ?? 0;
$descontos   = $res_fin['total_descontos'] ?? 0;
$acrescimos  = $res_fin['total_acrescimos'] ?? 0;
$banana_liq  = $res_fin['total_banana'] ?? 0;

// Cálculo da Comissão Líquida (Bruta + Adicionais - Descontos)
$comis_liquida = ($comis_bruta + $acrescimos) - $descontos;

$total_valor_materiais = 0;
?>

<div class="table-responsive">
    <table class="table table-bordered table-hover mb-4" style="font-size: 0.85rem;">
        <thead>
            <tr style="background-color: #2b7a00; color: #ffffff;" class="text-center">
                <th>Material Cadastrado</th>
                <th>Quantidade</th>
                <th>Valor R$ (Venda Material)</th>
            </tr>
        </thead>
        <tbody>
            <?php
            if (count($res_mat) > 0):
                foreach ($res_mat as $row):
                    $total_valor_materiais += $row['valor_total_material'];
            ?>
                    <tr class="text-center">
                        <td class="text-left"><?php echo $row['material_nome']; ?></td>
                        <td><?php echo $row['qtd_total']; ?></td>
                        <td>R$ <?php echo number_format($row['valor_total_material'], 2, ',', '.'); ?></td>
                    </tr>
                <?php endforeach;
            else: ?>
                <tr>
                    <td colspan="3" class="text-center">Nenhum material movimentado no período.</td>
                </tr>
            <?php endif; ?>
        </tbody>
    </table>

    <table class="table table-bordered" style="font-size: 0.9rem;">
        <thead>
            <tr class="text-center" style="background-color: #f8f9fa;">
                <th width="20%">Comissão Bruta</th>
                <th width="20%">Descontos (-)</th>
                <th width="20%">Acréscimos (+)</th>
                <th width="20%" style="background-color: #d39e00">Comissão Líquida</th>
            </tr>
        </thead>
        <tbody>
            <tr class="text-center" style="font-weight: bold;">
                <td>R$ <?php echo number_format($comis_bruta, 2, ',', '.'); ?></td>
                <td class="text-danger">R$ <?php echo number_format($descontos, 2, ',', '.'); ?></td>
                <td class="text-primary">R$ <?php echo number_format($acrescimos, 2, ',', '.'); ?></td>
                <td style="background-color: #fffdf0; font-size: 1.1rem; color: #1e5600;">
                    R$ <?php echo number_format($comis_liquida, 2, ',', '.'); ?>
                </td>
            </tr>
        </tbody>
    </table>

    <table class="table table-bordered mt-3">
        <thead>
            <tr class="text-center" style="background-color: #1e5600; color: white;">
                <th>Total Materiais</th>
                <th>Comissão + Materiais R$</th>
                <th>Banana Líquido</th>
                <th style="background-color: #d39e00;">TOTAL LÍQUIDO A RECEBER</th>
            </tr>
        </thead>
        <tbody>
            <tr class="text-center" style="font-weight: bold; font-size: 1.1rem;">
                <td>R$ <?php echo number_format($total_valor_materiais, 2, ',', '.'); ?></td>
                <td>R$ <?php echo number_format($comis_liquida + $total_valor_materiais, 2, ',', '.'); ?></td>
                <td>R$ <?php echo number_format($banana_liq, 2, ',', '.'); ?></td>
                <td style="background-color: #dff0d8; color: #1e5600; font-size: 1.3rem;">
                    <?php
                    // Total Final = Comissão Líquida + Valor dos Materiais + Valor da Banana
                    $total_final = $comis_liquida + $total_valor_materiais + $banana_liq;
                    echo "R$ " . number_format($total_final, 2, ',', '.');
                    ?>
                </td>
            </tr>
        </tbody>
    </table>
</div>