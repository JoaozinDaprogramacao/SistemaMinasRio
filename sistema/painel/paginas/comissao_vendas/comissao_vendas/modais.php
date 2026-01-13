<?php
// 0. GARANTIR UTF-8 NA CONEXÃO
$pdo->exec("SET NAMES utf8");

function padronizarQualidade($texto)
{
    $num = preg_replace('/[^0-9]/', '', $texto);
    return $num . '°';
}

// 1. BUSCA DINÂMICA DE QUALIDADES (MATERIAIS)
$qualidades = [];
$query_cat = $pdo->query("SELECT nome FROM categorias");
while ($c = $query_cat->fetch(PDO::FETCH_ASSOC)) {
    if (preg_match('/(\d+)(\xAA|\xB0|ª|°)$/u', $c['nome'], $matches)) {
        $qualidades[] = padronizarQualidade($matches[0]);
    }
}
$qualidades = array_unique($qualidades);
sort($qualidades);

// 2. BUSCA DE DADOS
$query = $pdo->query("SELECT p.nome as nome_prod, cat.nome as nome_cat, cat.vendas 
                      FROM produtos p 
                      INNER JOIN categorias cat ON p.categoria = cat.id 
                      ORDER BY p.nome ASC, cat.nome ASC");
$res = $query->fetchAll(PDO::FETCH_ASSOC);

// 3. PROCESSAMENTO
$dados_tabela = [];

foreach ($res as $item) {
    $cat_limpa = preg_replace('/\s+(DE\s+)?\d+.*$/ui', '', $item['nome_cat']);
    $prod_limpo = preg_replace('/\s+\d+.*$/ui', '', $item['nome_prod']);
    $chave = mb_strtoupper($prod_limpo . ' - ' . $cat_limpa);

    $qualidade_item = '';
    if (preg_match('/(\d+)(\xAA|\xB0|ª|°)$/u', $item['nome_cat'], $matches)) {
        $qualidade_item = padronizarQualidade($matches[0]);
    }

    if (!isset($dados_tabela[$chave])) {
        foreach ($qualidades as $q) {
            $dados_tabela[$chave][$q] = 0;
        }
        $dados_tabela[$chave]['GERAL'] = 0;
    }

    if ($qualidade_item != '' && in_array($qualidade_item, $qualidades)) {
        $dados_tabela[$chave][$qualidade_item] += $item['vendas'];
    } else {
        $dados_tabela[$chave]['GERAL'] += $item['vendas'];
    }
}
?>

<div class="card shadow mb-4" style="border-top: 4px solid #2b7a00;">
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-bordered table-hover" style="font-size: 0.85rem;">
                <thead>
                    <tr class="text-center" style="background-color: #2b7a00; color: #ffffff;">
                        <th class="text-left" style="background-color: #1e5600; min-width: 250px;">PRODUTO - CATEGORIA</th>
                        
                        <th width="8%">GERAL</th>

                        <?php foreach ($qualidades as $q): ?>
                            <th width="8%"><?php echo $q; ?></th>
                        <?php endforeach; ?>

                        <th width="10%" style="background-color: #1e5600;">TOTAL MATERIAIS</th>
                        <th width="10%" style="background-color: #28a745;">VALOR R$</th>
                        <th width="10%" style="background-color: #d39e00;">COMIS. BRUTA</th>
                        <th width="10%" style="background-color: #c69500;">COMIS. LÍQUIDA</th>
                        <th width="10%" style="background-color: #1e5600;">TOTAL A RECEBER</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($dados_tabela as $nome_exibicao => $valores): 
                        // --- LÓGICA DE MOCKS (SUBSTITUA PELOS SEUS CÁLCULOS REAIS DEPOIS) ---
                        
                        // 1. Soma de todos os materiais da linha
                        $soma_materiais = $valores['GERAL'];
                        foreach ($qualidades as $q) { $soma_materiais += $valores[$q]; }

                        // 2. Mocks Financeiros
                        $mock_valor_rs = $soma_materiais * 12.50; // Exemplo: R$ 12,50 por unidade
                        $mock_comis_bruta = $mock_valor_rs * 0.10; // Exemplo: 10%
                        $mock_comis_liquida = $mock_comis_bruta * 0.85; // Exemplo: Bruta menos 15% de encargos
                        $mock_total_receber = $mock_valor_rs + $mock_comis_liquida;
                        // -----------------------------------------------------------------
                    ?>
                        <tr class="text-center" style="background-color: #f3f9f1; color: #2b7a00; font-weight: bold;">
                            <td class="text-left" style="color: #1e5600;">
                                <?php echo $nome_exibicao; ?>
                            </td>

                            <td><?php echo number_format($valores['GERAL'], 0, ',', '.'); ?></td>

                            <?php foreach ($qualidades as $q): ?>
                                <td><?php echo number_format($valores[$q], 0, ',', '.'); ?></td>
                            <?php endforeach; ?>

                            <td style="background-color: #e2efda;"><?php echo number_format($soma_materiais, 0, ',', '.'); ?></td>
                            
                            <td style="color: #155724;">R$ <?php echo number_format($mock_valor_rs, 2, ',', '.'); ?></td>
                            
                            <td style="color: #856404;">R$ <?php echo number_format($mock_comis_bruta, 2, ',', '.'); ?></td>
                            
                            <td style="color: #856404;">R$ <?php echo number_format($mock_comis_liquida, 2, ',', '.'); ?></td>
                            
                            <td style="background-color: #c6e0b4; color: #1e5600;">
                                R$ <?php echo number_format($mock_total_receber, 2, ',', '.'); ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>