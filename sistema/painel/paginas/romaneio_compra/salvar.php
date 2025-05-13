<?php
// sistema/painel/paginas/romaneio_compra/salvar.php
$tabela = 'romaneio_compra';
require_once("../../../conexao.php");
session_start();

header('Content-Type: application/json; charset=utf-8');

$id_usuario     = $_SESSION['id'] ?? null;

// campos do POST
$id             = $_POST['id']              ?? '';
$fornecedor     = $_POST['fornecedor']      ?? '';
$cliente        = $_POST['cliente']         ?? '';
$data           = $_POST['data']            ?? '';
$plano_pgto     = $_POST['plano_pgto']      ?? '';
$quant_dias     = $_POST['quant_dias']      ?? '';
$nota_fiscal    = $_POST['nota_fiscal']     ?? '';
$vencimento     = $_POST['vencimento']      ?? '';
$fazenda        = $_POST['fazenda']         ?? '';

// **novo**: desconto à vista
$desc_avista    = floatval(str_replace(',', '.', $_POST['desc-avista'] ?? '0'));

// descontos fixos
$desc_funrural  = floatval(str_replace(',', '.', $_POST['desc_funrural']   ?? '0'));
$desc_ima       = floatval(str_replace(',', '.', $_POST['desc_ima']        ?? '0'));
$desc_abanorte  = floatval(str_replace(',', '.', $_POST['desc_abanorte']   ?? '0'));
$desc_taxaadm   = floatval(str_replace(',', '.', $_POST['desc_taxaadm']    ?? '0'));

// descontos diversos (dinâmicos)
$tipos          = $_POST['desconto_tipo']  ?? [];
$valores        = $_POST['desconto_valor'] ?? [];
$obs            = $_POST['desconto_obs']   ?? [];
$descontos_diversos = [];
foreach ($tipos as $i => $tipo) {
    $v = str_replace(',', '.', $valores[$i] ?? '0');
    if ($tipo !== '' && is_numeric($v) && floatval($v) > 0) {
        $descontos_diversos[] = [
            'tipo'  => $tipo,
            'valor' => floatval($v),
            'obs'   => trim($obs[$i] ?? '')
        ];
    }
}
$descontos_json = json_encode($descontos_diversos, JSON_UNESCAPED_UNICODE);

// arrays de produtos
$quant_caixa_1  = $_POST['quant_caixa_1']  ?? [];
$produto_1      = $_POST['produto_1']      ?? [];
$preco_kg_1     = $_POST['preco_kg_1']     ?? [];
$tipo_cx_1      = $_POST['tipo_cx_1']      ?? [];
$preco_unit_1   = $_POST['preco_unit_1']   ?? [];
$valor_1        = $_POST['valor_1']        ?? [];

// validações
$erros = [];
if (empty($fornecedor) || $fornecedor == '0')   $erros[] = "Selecione um fornecedor";
if (empty($cliente)    || $cliente    == '0')   $erros[] = "Selecione um cliente";
if (empty($data))                              $erros[] = "Data é obrigatória";
if (empty($plano_pgto) || $plano_pgto == '0')   $erros[] = "Selecione um plano de pagamento";

// valida desconto à vista
if (strtoupper(trim($_POST['plano_pgto'] ?? '')) === 'À VISTA' && $desc_avista <= 0) {
    $erros[] = "Para pagamento à vista, o desconto é obrigatório";
}

if (!empty($nota_fiscal)) {
    $q = $pdo->prepare("SELECT id FROM {$tabela} WHERE nota_fiscal = ? AND id != ?");
    $q->execute([$nota_fiscal, $id]);
    if ($q->rowCount()) $erros[] = "Esta nota fiscal já está cadastrada";
}

// cálculo de totais
$total_bruto   = array_reduce($valor_1, fn($c,$v)=> $c + floatval(str_replace(',', '.', $v)), 0);
$total_liquido = $total_bruto 
               - $desc_avista 
               - $desc_funrural 
               - $desc_ima 
               - $desc_abanorte 
               - $desc_taxaadm;

// validação produtos
$tem_produtos = false;
foreach ($valor_1 as $k => $v) {
    if ($v !== '' && floatval(str_replace(',', '.', $v)) > 0) {
        $tem_produtos = true;
        if (empty($produto_1[$k]))   { $erros[] = "Selecione variedade em todos os produtos";   break; }
        if (empty($tipo_cx_1[$k]))   { $erros[] = "Selecione tipo de caixa em todos os produtos"; break; }
        if (empty($quant_caixa_1[$k]) || $quant_caixa_1[$k] <= 0) {
            $erros[] = "Quantidade de caixas deve ser maior que zero"; break;
        }
    }
}
if (!$tem_produtos) $erros[] = "Adicione pelo menos um produto";

if ($erros) {
    echo json_encode(['status'=>'erro','mensagem'=>implode("<br>", $erros)], JSON_UNESCAPED_UNICODE);
    exit;
}

// filtra valores não vazios
function filtrar(array $a){
    return array_values(array_filter($a, fn($v)=> $v !== ''));
}
$quant_caixa_1_val = filtrar($quant_caixa_1);
$produto_1_val     = filtrar($produto_1);
$preco_kg_1_val    = filtrar($preco_kg_1);
$tipo_cx_1_val     = filtrar($tipo_cx_1);
$preco_unit_1_val  = filtrar($preco_unit_1);
$valor_1_val       = filtrar($valor_1);

// INSERT ou UPDATE do cabeçalho
if ($id === '') {
    $sql = "INSERT INTO {$tabela}
      (fornecedor, cliente, quant_dias, data, nota_fiscal, plano_pgto, vencimento,
       total_liquido, fazenda, desc_avista,
       desc_funrural, desc_ima, desc_abanorte, desc_taxaadm, descontos_diversos)
     VALUES
      (:forn, :cli, :qd, :dt, :nf, :pp, :ven, :tl, :faz, :da,
       :df,   :di,   :dab,   :dtadm,  :dd)";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([
        ':forn'=>$fornecedor,
        ':cli'=>$cliente,
        ':qd'=>$quant_dias,
        ':dt'=>$data,
        ':nf'=>$nota_fiscal,
        ':pp'=>$plano_pgto,
        ':ven'=>$vencimento,
        ':tl'=>$total_liquido,
        ':faz'=>$fazenda,
        ':da'=>$desc_avista,
        ':df'=>$desc_funrural,
        ':di'=>$desc_ima,
        ':dab'=>$desc_abanorte,
        ':dtadm'=>$desc_taxaadm,
        ':dd'=>$descontos_json
    ]);
    $romaneioId = $pdo->lastInsertId();
} else {
    $sql = "UPDATE {$tabela} SET
        fornecedor       = :forn,
        cliente          = :cli,
        quant_dias       = :qd,
        data             = :dt,
        nota_fiscal      = :nf,
        plano_pgto       = :pp,
        vencimento       = :ven,
        total_liquido    = :tl,
        fazenda          = :faz,
        desc_avista      = :da,
        desc_funrural    = :df,
        desc_ima         = :di,
        desc_abanorte    = :dab,
        desc_taxaadm     = :dtadm,
        descontos_diversos = :dd
     WHERE id = :id";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([
        ':forn'=>$fornecedor,
        ':cli'=>$cliente,
        ':qd'=>$quant_dias,
        ':dt'=>$data,
        ':nf'=>$nota_fiscal,
        ':pp'=>$plano_pgto,
        ':ven'=>$vencimento,
        ':tl'=>$total_liquido,
        ':faz'=>$fazenda,
        ':da'=>$desc_avista,
        ':df'=>$desc_funrural,
        ':di'=>$desc_ima,
        ':dab'=>$desc_abanorte,
        ':dtadm'=>$desc_taxaadm,
        ':dd'=>$descontos_json,
        ':id'=>$id
    ]);
    $romaneioId = $id;
}

// apaga itens antigos
$pdo->prepare("DELETE FROM linha_produto_compra WHERE id_romaneio = ?")
    ->execute([$romaneioId]);

// insere produtos
$insertLinha = $pdo->prepare(
    "INSERT INTO linha_produto_compra
      (id_romaneio, quant, variedade, preco_kg, tipo_caixa, preco_unit, valor)
    VALUES (?, ?, ?, ?, ?, ?, ?)"
);

foreach ($quant_caixa_1_val as $key => $q) {
    $v       = str_replace(',', '.', $valor_1_val[$key]);
    $var     = $produto_1_val[$key];
    $pkg     = str_replace(',', '.', $preco_kg_1_val[$key]);
    $pun     = str_replace(',', '.', $preco_unit_1_val[$key]);
    $tipoVal = str_replace(',', '.', $tipo_cx_1_val[$key]);

    // obtém ou insere tipo_caixa
    $t = $pdo->prepare("SELECT id FROM tipo_caixa WHERE tipo = ?");
    $t->execute([$tipoVal]);
    $tipoCx = $t->fetchColumn();
    if (!$tipoCx) {
        $t2 = $pdo->prepare("INSERT INTO tipo_caixa (tipo, unidade_medida) VALUES (?,1)");
        $t2->execute([$tipoVal]);
        $tipoCx = $pdo->lastInsertId();
    }

    $insertLinha->execute([
        $romaneioId,
        $q,
        $var,
        $pkg,
        $tipoCx,
        $pun,
        $v
    ]);
}

echo json_encode([
    'status'   => 'sucesso',
    'mensagem' => 'Salvo com sucesso',
    'id'       => $romaneioId
], JSON_UNESCAPED_UNICODE);
