<?php
require_once("../../../conexao.php");

// Recebe os filtros
$dataInicial = @$_POST['dataInicial'];
$dataFinal = @$_POST['dataFinal'];
$cliente = @$_POST['cliente'];

// Verifica se existe algum filtro ativo
// Consideramos "sem filtro" se as datas estiverem vazias e o cliente for vazio ou "0"
$temFiltro = !empty($dataInicial) || !empty($dataFinal) || (!empty($cliente) && $cliente !== "0");

// 1. BUSCA DINÂMICA DE QUALIDADES (Cabeçalho permanece igual)
$qualidades = [];
$query_cat = $pdo->query("SELECT nome FROM categorias");
while ($c = $query_cat->fetch(PDO::FETCH_ASSOC)) {
    if (preg_match('/(\d+)(\xAA|\xB0|ª|°)$/u', $c['nome'], $matches)) {
        $num = preg_replace('/[^0-9]/', '', $matches[0]);
        $qualidades[] = $num . 'ª';
    }
}
$qualidades = array_unique($qualidades);
sort($qualidades);

// 2. DEFINIÇÃO DA QUERY BASEADA NO FILTRO
if (!$temFiltro) {
    // PERFORMANCE MÁXIMA: Pega direto da tabela categorias (Total Geral)
    $query = $pdo->query("SELECT p.nome as nome_prod, cat.nome as nome_cat, cat.vendas as total_vendas 
                          FROM produtos p 
                          INNER JOIN categorias cat ON p.categoria = cat.id 
                          ORDER BY p.nome ASC, cat.nome ASC");
} else {
    // CÁLCULO DINÂMICO: Quando o usuário quer ver um período ou cliente específico
    $where = " WHERE 1=1 ";
    if (!empty($dataInicial)) {
        $where .= " AND rv.data >= '$dataInicial' ";
    }
    if (!empty($dataFinal)) {
        $where .= " AND rv.data <= '$dataFinal' ";
    }
    if (!empty($cliente) && $cliente !== "0") {
        $where .= " AND rv.atacadista = '$cliente' ";
    }

    $query = $pdo->query("SELECT p.nome as nome_prod, cat.nome as nome_cat, SUM(lp.quant) as total_vendas 
                          FROM linha_produto lp
                          INNER JOIN romaneio_venda rv ON lp.id_romaneio = rv.id
                          INNER JOIN produtos p ON lp.variedade = p.id 
                          INNER JOIN categorias cat ON p.categoria = cat.id 
                          $where
                          GROUP BY p.nome, cat.nome
                          ORDER BY p.nome ASC, cat.nome ASC");
}

$res = $query->fetchAll(PDO::FETCH_ASSOC);

// 3. PROCESSAMENTO DOS DADOS PARA O FORMATO DA TABELA
$dados_tabela = [];
foreach ($res as $item) {
    $cat_limpa = preg_replace('/\s+(DE\s+)?\d+.*$/ui', '', $item['nome_cat']);
    $prod_limpo = preg_replace('/\s+\d+.*$/ui', '', $item['nome_prod']);
    $chave = mb_strtoupper($prod_limpo . ' - ' . $cat_limpa);

    $qualidade_item = '';
    if (preg_match('/(\d+)(\xAA|\xB0|ª|°)$/u', $item['nome_cat'], $matches)) {
        $num = preg_replace('/[^0-9]/', '', $matches[0]);
        $qualidade_item = $num . '°';
    }

    if (!isset($dados_tabela[$chave])) {
        foreach ($qualidades as $q) {
            $dados_tabela[$chave][$q] = 0;
        }
        $dados_tabela[$chave]['GERAL'] = 0;
    }

    if ($qualidade_item != '' && in_array($qualidade_item, $qualidades)) {
        $dados_tabela[$chave][$qualidade_item] += $item['total_vendas'];
    } else {
        $dados_tabela[$chave]['GERAL'] += $item['total_vendas'];
    }
}
?>

<div class="table-responsive">
    <table class="table table-bordered table-hover">
        <thead>
            <tr class="text-center" style="background-color: #2b7a00; color: #ffffff;">
                <th class="text-left" style="background-color: #1e5600;">PRODUTO - CATEGORIA
                    <?php echo !$temFiltro ? '<small>(TOTAL ACUMULADO)</small>' : '<small>(FILTRADO)</small>'; ?>
                </th>
                <th width="10%">GERAL</th>
                <?php foreach ($qualidades as $q): ?>
                    <th width="10%"><?php echo $q; ?></th>
                <?php endforeach; ?>
                <th width="12%" style="background-color: #1e5600;">TOTAL</th>
            </tr>
        </thead>
        <tbody>
            <?php if (count($dados_tabela) > 0):
                foreach ($dados_tabela as $nome_exibicao => $valores): ?>
                    <tr class="text-center" style="background-color: #f3f9f1; color: #2b7a00; font-weight: bold;">
                        <td class="text-left" style="color: #1e5600;"><?php echo $nome_exibicao; ?></td>
                        <td><?php echo number_format($valores['GERAL'], 0, ',', '.'); ?></td>
                        <?php
                        $soma_linha = $valores['GERAL'];
                        foreach ($qualidades as $q):
                            $v = $valores[$q];
                            $soma_linha += $v;
                        ?>
                            <td><?php echo number_format($v, 0, ',', '.'); ?></td>
                        <?php endforeach; ?>
                        <td style="background-color: #c6e0b4; color: #1e5600;"><?php echo number_format($soma_linha, 0, ',', '.'); ?></td>
                    </tr>
                <?php endforeach;
            else: ?>
                <tr>
                    <td colspan="<?php echo count($qualidades) + 3; ?>" class="text-center">Nenhum dado encontrado para os filtros aplicados.</td>
                </tr>
            <?php endif; ?>
        </tbody>
    </table>
</div>