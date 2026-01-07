<?php
// 1. BUSCA DINÂMICA DE QUALIDADES (Extraindo do nome da CATEGORIA)
$qualidades = [];
$query_cat = $pdo->query("SELECT nome FROM categorias");
while ($c = $query_cat->fetch(PDO::FETCH_ASSOC)) {
    if (preg_match('/(\d+ª|\d+°)$/', $c['nome'], $matches)) {
        $qualidades[] = $matches[1];
    }
}
$qualidades = array_unique($qualidades);
sort($qualidades);

// 2. BUSCA DE DADOS (Corrigido para buscar cat.vendas)
$query = $pdo->query("SELECT p.nome as nome_prod, cat.nome as nome_cat, cat.vendas 
                      FROM produtos p 
                      INNER JOIN categorias cat ON p.categoria = cat.id 
                      ORDER BY p.nome ASC, cat.nome ASC");
$res = $query->fetchAll(PDO::FETCH_ASSOC);

// 3. PROCESSAMENTO PARA O FORMATO DE TABELA PIVÔ
$dados_tabela = [];

foreach ($res as $item) {
    $cat_limpa = preg_replace('/\s+(DE\s+)?(\d+ª|\d+°)$/i', '', $item['nome_cat']);
    $chave = mb_strtoupper($item['nome_prod'] . ' - ' . $cat_limpa);

    $qualidade_item = '';
    if (preg_match('/(\d+ª|\d+°)$/', $item['nome_cat'], $matches)) {
        $qualidade_item = $matches[1];
    }

    if (!isset($dados_tabela[$chave])) {
        foreach ($qualidades as $q) {
            $dados_tabela[$chave][$q] = 0;
        }
    }

    if ($qualidade_item != '') {
        $dados_tabela[$chave][$qualidade_item] += $item['vendas'];
    }
}
?>

<div class="card shadow mb-4" style="border-top: 4px solid #2b7a00;">
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-bordered">
                <thead>
                    <tr class="text-center" style="background-color: #2b7a00; color: #ffffff;">
                        <th class="text-left" style="background-color: #1e5600;">PRODUTO - CATEGORIA</th>
                        <?php foreach ($qualidades as $q): ?>
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

                            <?php
                            $soma_linha = 0;
                            foreach ($qualidades as $q):
                                $v = $valores[$q];
                                $soma_linha += $v;
                                // Formata para 1.000, 10.000 etc
                                $v_formatado = number_format($v, 0, ',', '.');
                            ?>
                                <td><?php echo $v_formatado; ?></td>
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