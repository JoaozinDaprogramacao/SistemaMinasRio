<?php
require_once("../../../conexao.php");

$id = $_POST['id'];

// Buscar dados do romaneio (agora incluindo desc_avista)
$query = $pdo->prepare("
  SELECT 
    rc.*,
    f.razao_social    AS nome_fornecedor,
    p.nome            AS nome_plano,
    c.nome            AS nome_cliente,
    rc.desc_avista    AS desc_avista
  FROM romaneio_compra rc
  LEFT JOIN fornecedores   f ON rc.fornecedor = f.id
  LEFT JOIN planos_pgto    p ON rc.plano_pgto  = p.id
  LEFT JOIN clientes       c ON rc.cliente     = c.id
  WHERE rc.id = :id
");
$query->bindValue(":id", $id);
$query->execute();
$romaneio = $query->fetch(PDO::FETCH_ASSOC);

// Buscar produtos
$query = $pdo->prepare("
  SELECT 
    lpc.*,
    p.nome as nome_produto,
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
  LEFT JOIN produtos   p  ON lpc.variedade   = p.id
  LEFT JOIN tipo_caixa tc ON lpc.tipo_caixa = tc.id
  WHERE lpc.id_romaneio = :id
");
$query->bindValue(":id", $id);
$query->execute();
$produtos = $query->fetchAll(PDO::FETCH_ASSOC);

echo json_encode([
  'romaneio' => $romaneio,
  'produtos' => $produtos
], JSON_UNESCAPED_UNICODE);
