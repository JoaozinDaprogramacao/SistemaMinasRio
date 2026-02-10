<?php
require_once("../../../conexao.php");

$id = @$_POST['id'];

if ($id == "") {
    echo json_encode(['error' => 'ID não fornecido']);
    exit();
}

// 1. BUSCAR DADOS DO ROMANEIO (CABEÇALHO)
// Removi o JOIN com atacadistas para evitar o erro. 
// Ajuste o nome 'clientes' abaixo se a sua tabela tiver outro nome.
$query = $pdo->prepare("SELECT rv.*, 
    (SELECT nome FROM clientes WHERE id = rv.atacadista) as nome_cliente,
    (SELECT nome FROM formas_pgto WHERE id = rv.plano_pgto) as nome_plano
    FROM romaneio_venda rv 
    WHERE rv.id = :id");
$query->bindValue(":id", $id);
$query->execute();
$romaneio = $query->fetch(PDO::FETCH_ASSOC);

if (!$romaneio) {
    echo json_encode(['error' => 'Romaneio não encontrado']);
    exit();
}

$query_prod = $pdo->prepare("SELECT lp.*, p.nome as nome_produto, c.nome as nome_variedade 
                             FROM linha_produto lp 
                             LEFT JOIN produtos p ON lp.variedade = p.id 
                             LEFT JOIN categorias c ON p.categoria = c.id 
                             WHERE lp.id_romaneio = :id");
$query_prod->bindValue(":id", $id);
$query_prod->execute();
$produtos = $query_prod->fetchAll(PDO::FETCH_ASSOC);

// 3. BUSCAR COMISSÕES (LINHA_COMISSAO)
$query_comis = $pdo->prepare("SELECT lc.*, dr.descricao as nome_comissao, tc.tipo as peso_caixa 
                             FROM linha_comissao lc 
                             LEFT JOIN descricao_romaneio dr ON lc.descricao = dr.id 
                             LEFT JOIN tipo_caixa tc ON lc.tipo_caixa = tc.id 
                             WHERE lc.id_romaneio = :id");
$query_comis->bindValue(":id", $id);
$query_comis->execute();
$comissoes = $query_comis->fetchAll(PDO::FETCH_ASSOC);

// 4. BUSCAR MATERIAIS (LINHA_OBSERVACAO)
$query_mat = $pdo->prepare("SELECT lo.*, m.nome as nome_material 
                            FROM linha_observacao lo 
                            LEFT JOIN materiais m ON lo.descricao = m.id 
                            WHERE lo.id_romaneio = :id");
$query_mat->bindValue(":id", $id);
$query_mat->execute();
$materiais = $query_mat->fetchAll(PDO::FETCH_ASSOC);

// ORGANIZAR RESPOSTA
$dados = [
    'romaneio'  => $romaneio,
    'produtos'  => $produtos,
    'comissoes' => $comissoes,
    'materiais' => $materiais
];

header('Content-Type: application/json');
echo json_encode($dados);
