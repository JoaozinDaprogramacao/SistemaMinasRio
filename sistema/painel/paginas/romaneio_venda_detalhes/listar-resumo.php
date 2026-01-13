<?php
require_once("../../../conexao.php");

// 1. RECEBIMENTO DOS FILTROS
$dataInicial = @$_POST['dataInicial'];
$dataFinal = @$_POST['dataFinal'];
$cliente = @$_POST['cliente'];

$temFiltro = !empty($dataInicial) || !empty($dataFinal) || (!empty($cliente) && $cliente !== "0");

// 2. BUSCA DINÂMICA DE QUALIDADES (Padronizando para o feminino "ª")
$qualidades = [];
$query_cat = $pdo->query("SELECT nome FROM categorias");
while ($c = $query_cat->fetch(PDO::FETCH_ASSOC)) {
    // Regex captura números seguidos de °, ª, º ou símbolos similares
    if (preg_match('/(\d+)(\xAA|\xB0|ª|°)$/u', $c['nome'], $matches)) {
        $num = preg_replace('/[^0-9]/', '', $matches[0]);
        $qualidades[] = $num . 'ª';
    }
}
$qualidades = array_unique($qualidades);
sort($qualidades);

// 3. DEFINIÇÃO DA QUERY COM CÁLCULO DE KG (JOIN COM TIPO_CAIXA)
// O cálculo é: Quantidade (lp.quant) * Valor do KG daquela caixa (tc.tipo)
if (!$temFiltro) {
    $query = $pdo->query("SELECT p.nome as nome_prod, cat.nome as nome_cat, 
                                 SUM(lp.quant) as total_vendas, 
                                 SUM(lp.quant * tc.tipo) as total_kg 
                          FROM linha_produto lp
                          INNER JOIN produtos p ON lp.variedade = p.id 
                          INNER JOIN categorias cat ON p.categoria = cat.id 
                          INNER JOIN tipo_caixa tc ON lp.tipo_caixa = tc.id
                          GROUP BY p.nome, cat.nome
                          ORDER BY p.nome ASC, cat.nome ASC");
} else {
    $where = " WHERE 1=1 ";
    if (!empty($dataInicial)) $where .= " AND rv.data >= '$dataInicial' ";
    if (!empty($dataFinal))   $where .= " AND rv.data <= '$dataFinal' ";
    if (!empty($cliente) && $cliente !== "0") $where .= " AND rv.atacadista = '$cliente' ";

    $query = $pdo->query("SELECT p.nome as nome_prod, cat.nome as nome_cat, 
                                 SUM(lp.quant) as total_vendas, 
                                 SUM(lp.quant * tc.tipo) as total_kg 
                          FROM linha_produto lp
                          INNER JOIN romaneio_venda rv ON lp.id_romaneio = rv.id
                          INNER JOIN produtos p ON lp.variedade = p.id 
                          INNER JOIN categorias cat ON p.categoria = cat.id 
                          INNER JOIN tipo_caixa tc ON lp.tipo_caixa = tc.id
                          $where
                          GROUP BY p.nome, cat.nome
                          ORDER BY p.nome ASC, cat.nome ASC");
}

$res = $query->fetchAll(PDO::FETCH_ASSOC);

// 4. PROCESSAMENTO DOS DADOS PARA O ARRAY DA TABELA
$dados_tabela = [];
foreach ($res as $item) {
    // Limpeza de nomes para agrupar (ex: Tomate 1ª e Tomate 2ª viram apenas "TOMATE")
    $cat_limpa = preg_replace('/\s+(DE\s+)?\d+.*$/ui', '', $item['nome_cat']);
    $prod_limpo = preg_replace('/\s+\d+.*$/ui', '', $item['nome_prod']);
    $chave = mb_strtoupper($prod_limpo . ' - ' . $cat_limpa);

    $qualidade_item = '';
    if (preg_match('/(\d+)(\xAA|\xB0|ª|°)$/u', $item['nome_cat'], $matches)) {
        $num = preg_replace('/[^0-9]/', '', $matches[0]);
        $qualidade_item = $num . 'ª';
    }

    if (!isset($dados_tabela[$chave])) {
        foreach ($qualidades as $q) {
            $dados_tabela[$chave][$q] = ['qtd' => 0, 'kg' => 0];
        }
        $dados_tabela[$chave]['GERAL_CAT'] = ['qtd' => 0, 'kg' => 0]; // Itens sem classificação numérica
    }

    if ($qualidade_item != '' && in_array($qualidade_item, $qualidades)) {
        $dados_tabela[$chave][$qualidade_item]['qtd'] += $item['total_vendas'];
        $dados_tabela[$chave][$qualidade_item]['kg'] += $item['total_kg'];
    } else {
        $dados_tabela[$chave]['GERAL_CAT']['qtd'] += $item['total_vendas'];
        $dados_tabela[$chave]['GERAL_CAT']['kg'] += $item['total_kg'];
    }
}
?>

<style>
    .cell-container {
        display: flex;
        flex-direction: column;
        align-items: center;
        justify-content: center;
        min-height: 55px;
    }

    .val-cx {
        font-size: 1.15em;
        font-weight: 800;
        color: #2b7a00;
        /* Verde */
        display: block;
        width: 100%;
        border-bottom: 1px solid #e0e0e0;
        padding-bottom: 2px;
    }

    .val-kg {
        font-size: 1.15em;
        font-weight: 800;
        color: #b35900;
        /* Laranja Escuro / Bronze */
        display: block;
        padding-top: 2px;
    }

    .unit-label {
        font-size: 0.65em;
        font-weight: normal;
        text-transform: uppercase;
        margin-left: 3px;
        color: #666;
    }

    .table-header-main {
        background-color: #2b7a00;
        color: white;
        vertical-align: middle !important;
    }

    .sub-header {
        font-size: 0.75em;
        display: block;
        color: #c6e0b4;
    }
</style>

<div class="table-responsive">
    <table class="table table-bordered table-hover">
        <thead>
            <tr class="text-center table-header-main">
                <th class="text-left" style="background-color: #1e5600;">PRODUTO - CATEGORIA</th>
                <th width="12%">SEM CLASSIF.<br><span class="sub-header">CX | KG</span></th>
                <?php foreach ($qualidades as $q): ?>
                    <th width="12%"><?php echo $q; ?><br><span class="sub-header">CX | KG</span></th>
                <?php endforeach; ?>
                <th width="15%" style="background-color: #1e5600;">TOTAL GERAL<br><span class="sub-header">CX | KG</span></th>
            </tr>
        </thead>
        <tbody>
            <?php if (count($dados_tabela) > 0):
                foreach ($dados_tabela as $nome_exibicao => $valores): ?>
                    <tr class="text-center" style="background-color: #f3f9f1;">
                        <td class="text-left" style="color: #1e5600; font-weight: bold; vertical-align: middle;">
                            <?php echo $nome_exibicao; ?>
                        </td>

                        <td class="p-0">
                            <div class="cell-container">
                                <span class="val-cx"><?php echo number_format($valores['GERAL_CAT']['qtd'], 0, ',', '.'); ?><span class="unit-label">cx</span></span>
                                <span class="val-kg"><?php echo number_format($valores['GERAL_CAT']['kg'], 1, ',', '.'); ?><span class="unit-label">kg</span></span>
                            </div>
                        </td>

                        <?php
                        $soma_qtd_linha = $valores['GERAL_CAT']['qtd'];
                        $soma_kg_linha = $valores['GERAL_CAT']['kg'];

                        foreach ($qualidades as $q):
                            $v_qtd = $valores[$q]['qtd'];
                            $v_kg = $valores[$q]['kg'];
                            $soma_qtd_linha += $v_qtd;
                            $soma_kg_linha += $v_kg;
                        ?>
                            <td class="p-0">
                                <div class="cell-container">
                                    <span class="val-cx"><?php echo number_format($v_qtd, 0, ',', '.'); ?><span class="unit-label">cx</span></span>
                                    <span class="val-kg"><?php echo number_format($v_kg, 1, ',', '.'); ?><span class="unit-label">kg</span></span>
                                </div>
                            </td>
                        <?php endforeach; ?>

                        <td class="p-0" style="background-color: #c6e0b4;">
                            <div class="cell-container">
                                <span class="val-cx" style="color: #1e5600; border-bottom-color: #1e5600;">
                                    <?php echo number_format($soma_qtd_linha, 0, ',', '.'); ?><span class="unit-label">cx</span>
                                </span>
                                <span class="val-kg" style="color: #1e5600;">
                                    <?php echo number_format($soma_kg_linha, 1, ',', '.'); ?><span class="unit-label">kg</span>
                                </span>
                            </div>
                        </td>
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