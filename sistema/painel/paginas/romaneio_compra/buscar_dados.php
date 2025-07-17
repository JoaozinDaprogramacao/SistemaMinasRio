<?php
require_once("../../../conexao.php");

$id = $_POST['id'];

// --- MODIFICAÇÃO APLICADA AQUI ---
// Buscar dados do romaneio com a lógica inteligente para o nome do fornecedor.
$query_romaneio = $pdo->prepare("
  SELECT 
    rc.*,
    COALESCE(NULLIF(TRIM(f.razao_social), ''), f.nome_atacadista) AS nome_fornecedor,
    p.nome            AS nome_plano,
    c.nome            AS nome_cliente,
    rc.desc_avista    AS desc_avista
  FROM romaneio_compra rc
  LEFT JOIN fornecedores   f ON rc.fornecedor = f.id
  LEFT JOIN planos_pgto    p ON rc.plano_pgto  = p.id
  LEFT JOIN clientes       c ON rc.cliente     = c.id
  WHERE rc.id = :id
");
$query_romaneio->bindValue(":id", $id);
$query_romaneio->execute();
$romaneio = $query_romaneio->fetch(PDO::FETCH_ASSOC);

// Buscar produtos - Esta parte permanece igual, pois não busca o nome do fornecedor.
$query_produtos = $pdo->prepare("
  SELECT 
    lpc.*,
    CONCAT(p.nome, COALESCE(CONCAT(' - ', cat.nome), '')) as nome_produto,
    COALESCE(
      CONCAT(
        FORMAT(tc.tipo, 2),
        ' ',
        CASE tc.unidade_medida 
          WHEN 1 THEN 'KG'
          WHEN 2 THEN 'G'
          WHEN 3 THEN 'UN'
          ELSE ''
        END
      ),
      '-'
    ) as tipo_caixa
  FROM linha_produto_compra lpc
  LEFT JOIN produtos   p   ON lpc.variedade   = p.id
  LEFT JOIN tipo_caixa tc  ON lpc.tipo_caixa  = tc.id
  LEFT JOIN categorias cat ON p.categoria     = cat.id
  WHERE lpc.id_romaneio = :id
");
$query_produtos->bindValue(":id", $id);
$query_produtos->execute();
$produtos = $query_produtos->fetchAll(PDO::FETCH_ASSOC);

echo json_encode([
  'romaneio' => $romaneio,
  'produtos' => $produtos
], JSON_UNESCAPED_UNICODE);
?>