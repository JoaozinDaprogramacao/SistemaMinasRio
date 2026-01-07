<?php
// 0. GARANTIR UTF-8 NA CONEXÃO
$pdo->exec("SET NAMES utf8");

function padronizarQualidade($texto)
{
    $num = preg_replace('/[^0-9]/', '', $texto);
    return $num . '°';
}

// 1. BUSCA DINÂMICA DE QUALIDADES
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
    // Limpeza para agrupar (remove 1°, 2°, etc)
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
        $dados_tabela[$chave]['GERAL'] = 0; // Coluna para produtos tipo único
    }

    if ($qualidade_item != '' && in_array($qualidade_item, $qualidades)) {
        $dados_tabela[$chave][$qualidade_item] += $item['vendas'];
    } else {
        // Aqui cai a Banana Maçã, que não tem 1° ou 2° no nome
        $dados_tabela[$chave]['GERAL'] += $item['vendas'];
    }
}
?>

<div class="card shadow mb-4" style="border-top: 4px solid #2b7a00;">
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-bordered table-hover">
                <thead>
                    <tr class="text-center" style="background-color: #2b7a00; color: #ffffff;">
                        <th class="text-left" style="background-color: #1e5600;">PRODUTO - CATEGORIA</th>
                        <th width="10%">GERAL / ÚNICO</th> <?php foreach ($qualidades as $q): ?>
                            <th width="10%"><?php echo $q; ?></th>
                        <?php endforeach; ?>
                        <th width="12%" style="background-color: #1e5600;">TOTAL</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($dados_tabela as $nome_exibicao => $valores): ?>
                        <tr class="text-center" style="background-color: #f3f9f1; color: #2b7a00; font-weight: bold;">
                            <td class="text-left" style="color: #1e5600;">
                                <?php echo $nome_exibicao; ?>
                            </td>

                            <td style="color: #2b7a00;">
                                <?php echo number_format($valores['GERAL'], 0, ',', '.'); ?>
                            </td>

                            <?php
                            $soma_linha = $valores['GERAL'];
                            foreach ($qualidades as $q):
                                $v = $valores[$q];
                                $soma_linha += $v;
                            ?>
                                <td><?php echo number_format($v, 0, ',', '.'); ?></td>
                            <?php endforeach; ?>

                            <td style="background-color: #c6e0b4; color: #1e5600;">
                                <?php echo number_format($soma_linha, 0, ',', '.'); ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>