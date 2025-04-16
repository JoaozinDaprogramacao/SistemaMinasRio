<?php
require_once("../../../conexao.php");

$id = $_POST['id'];

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
$query = $pdo->prepare("SELECT 
    lp.*,
    p.nome as nome_produto,
    CONCAT(tc.tipo, ' ', CASE tc.unidade_medida 
        WHEN 1 THEN 'KG'
        WHEN 2 THEN 'G'
        WHEN 3 THEN 'UN'
        ELSE ''
    END) as tipo_caixa
    FROM linha_produto lp 
    LEFT JOIN produtos p ON lp.variedade = p.id 
    LEFT JOIN tipo_caixa tc ON lp.tipo_caixa = tc.id 
    WHERE lp.id_romaneio = :id");
$query->bindValue(":id", $id);
$query->execute();
$produtos = $query->fetchAll(PDO::FETCH_ASSOC);

// Buscar comissões
$query = $pdo->prepare("SELECT 
    lc.*,
    CONCAT(tc.tipo, ' ', CASE tc.unidade_medida 
        WHEN 1 THEN 'KG'
        WHEN 2 THEN 'G'
        WHEN 3 THEN 'UN'
        ELSE ''
    END) as tipo_caixa
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

$dados = array(
    'id' => $romaneio['id'],
    'data' => date('d/m/Y', strtotime($romaneio['data'])),
    'cliente' => $romaneio['nome_cliente'],
    'nota_fiscal' => $romaneio['nota_fiscal'],
    'plano_pgto' => $romaneio['nome_plano'],
    'vencimento' => date('d/m/Y', strtotime($romaneio['vencimento'])),
    'adicional' => number_format($romaneio['adicional'], 2, ',', '.'),
    'descricao_a' => $romaneio['descricao_a'],
    'desconto' => number_format($romaneio['desconto'], 2, ',', '.'),
    'descricao_d' => $romaneio['descricao_d'],
    'total_liquido' => number_format($romaneio['total_liquido'], 2, ',', '.'),
    'produtos' => $produtos,
    'comissoes' => $comissoes,
    'materiais' => $materiais
);

echo json_encode($dados);
?>
