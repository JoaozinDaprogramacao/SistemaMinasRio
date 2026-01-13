<?php
require_once("../../../conexao.php");

// Recebe os filtros
$dataInicial = @$_POST['dataInicial'];
$dataFinal = @$_POST['dataFinal'];
$cliente = @$_POST['cliente'];

$temFiltro = !empty($dataInicial) || !empty($dataFinal) || (!empty($cliente) && $cliente !== "0");

// 1. DEFINIÇÃO DA QUERY
if (!$temFiltro) {
    $query = $pdo->query("SELECT SUM(cat.vendas) as total_vendas 
                          FROM produtos p 
                          INNER JOIN categorias cat ON p.categoria = cat.id");
} else {
    $where = " WHERE 1=1 ";
    if (!empty($dataInicial)) $where .= " AND rv.data >= '$dataInicial' ";
    if (!empty($dataFinal)) $where .= " AND rv.data <= '$dataFinal' ";
    if (!empty($cliente) && $cliente !== "0") $where .= " AND rv.atacadista = '$cliente' ";

    $query = $pdo->query("SELECT SUM(lp.quant) as total_vendas 
                          FROM linha_produto lp
                          INNER JOIN romaneio_venda rv ON lp.id_romaneio = rv.id
                          $where");
}

$res = $query->fetch(PDO::FETCH_ASSOC);
$soma_materiais = $res['total_vendas'] ?? 0;

// --- LÓGICA DE CÁLCULO (MOCKS FINANCEIROS) ---
$mock_valor_rs = $soma_materiais * 15.00;
$mock_comis_bruta = $mock_valor_rs * 0.10;
$mock_comis_liquida = $mock_comis_bruta * 0.90;
$mock_comis_materiais = $mock_comis_bruta + $soma_materiais;
$mock_liq_banana = $mock_comis_liquida + 50.00;
$mock_total_receber = $mock_valor_rs + $mock_comis_liquida;
?>

<div class="table-responsive">
    <table class="table table-bordered table-hover" style="font-size: 0.85rem;">
        <thead>
            <tr class="text-center" style="background-color: #2b7a00; color: #ffffff;">
                <th width="14%" style="background-color: #1e5600;">Valor R$</th>
                <th width="14%" style="background-color: #d39e00;">COMIS. BRUTA</th>
                <th width="14%" style="background-color: #c69500;">COMIS. LÍQUIDA</th>
                <th width="14%" style="background-color: #5b9bd5;">COMIS. + MAT.</th>
                <th width="14%" style="background-color: #70ad47;">LÍQ. E BANANA</th>
                <th width="16%" style="background-color: #1e5600;">TOTAL A RECEBER</th>
            </tr>
        </thead>
        <tbody>
            <?php if ($soma_materiais > 0): ?>
                <tr class="text-center" style="background-color: #f3f9f1; color: #2b7a00; font-weight: bold; font-size: 1rem;">
                    <td style="background-color: #e2efda;"><?php echo number_format($soma_materiais, 0, ',', '.'); ?></td>
                    <td class="text-dark">R$ <?php echo number_format($mock_valor_rs, 2, ',', '.'); ?></td>
                    <td class="text-dark">R$ <?php echo number_format($mock_comis_bruta, 2, ',', '.'); ?></td>
                    <td class="text-dark">R$ <?php echo number_format($mock_comis_liquida, 2, ',', '.'); ?></td>
                    <td class="text-dark">R$ <?php echo number_format($mock_comis_materiais, 2, ',', '.'); ?></td>
                    <td style="background-color: #c6e0b4; color: #1e5600;">
                        R$ <?php echo number_format($mock_total_receber, 2, ',', '.'); ?>
                    </td>
                </tr>
            <?php else: ?>
                <tr>
                    <td colspan="7" class="text-center">Nenhum dado financeiro para o período.</td>
                </tr>
            <?php endif; ?>
        </tbody>
    </table>
</div>