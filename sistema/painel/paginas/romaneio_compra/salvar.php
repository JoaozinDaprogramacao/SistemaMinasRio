<?php
$tabela = 'romaneio_compra';
require_once("../../../conexao.php");

@session_start();
$id_usuario = @$_SESSION['id'];

// Configuração inicial
header('Content-Type: application/json; charset=utf-8');

// Validações básicas dos campos obrigatórios
$erros = [];

// Validação do Fornecedor
if (empty($_POST['fornecedor']) || $_POST['fornecedor'] == '0') {
    $erros[] = "Selecione um fornecedor";
}

// Validação da Data
if (empty($_POST['data'])) {
    $erros[] = "Data é obrigatória";
}

// Validação do Plano de Pagamento
if (empty($_POST['plano_pgto']) || $_POST['plano_pgto'] == '0') {
    $erros[] = "Selecione um plano de pagamento";
}

// Validação do desconto à vista
if (!empty($_POST['plano_pgto'])) {
    $query = $pdo->prepare("SELECT nome FROM planos_pgto WHERE id = ?");
    $query->execute([$_POST['plano_pgto']]);
    $plano = $query->fetch(PDO::FETCH_ASSOC);
    
    $plano_nome = strtoupper($plano['nome']);
    if ($plano && ($plano_nome === 'À VISTA' || $plano_nome === 'Á VISTA')) {
        $desconto = $_POST['desc-avista'] ?? '';
        
        if (empty($desconto) || floatval(str_replace(',', '.', $desconto)) <= 0) {
            $erros[] = "Para pagamento à vista, o desconto é obrigatório";
        } else {
            // Calcular o desconto à vista
            $valor_desconto = ($total_bruto * floatval(str_replace(',', '.', $desconto))) / 100;
            $total_liquido = $total_bruto - $valor_desconto;
        }
    }
}

// Validação da Nota Fiscal
if (!empty($_POST['nota_fiscal'])) {
    $nota = $_POST['nota_fiscal'];
    $id = $_POST['id'] ?? '';
    $query = $pdo->prepare("SELECT id FROM romaneio_compra WHERE nota_fiscal = ? AND id != ?");
    $query->execute([$nota, $id]);
    if ($query->rowCount() > 0) {
        $erros[] = "Esta nota fiscal já está cadastrada";
    }
}

// Validação dos Produtos
$tem_produtos = false;
if (isset($_POST['valor_1'])) {
    foreach ($_POST['valor_1'] as $key => $valor) {
        if (!empty($valor)) {
            $tem_produtos = true;
            
            if (empty($_POST['produto_1'][$key])) {
                $erros[] = "Selecione a variedade para todos os produtos";
                break;
            }
            if (empty($_POST['tipo_cx_1'][$key])) {
                $erros[] = "Selecione o tipo de caixa para todos os produtos";
                break;
            }
            if (empty($_POST['quant_caixa_1'][$key]) || $_POST['quant_caixa_1'][$key] <= 0) {
                $erros[] = "Quantidade de caixas deve ser maior que zero";
                break;
            }
        }
    }
}

if (!$tem_produtos) {
    $erros[] = "Adicione pelo menos um produto";
}

// Se houver erros, retorna
if (!empty($erros)) {
    echo json_encode([
        'status' => 'erro',
        'mensagem' => implode("<br>", $erros)
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

$id = $_POST['id'];
$fornecedor = $_POST['fornecedor'];
$data = $_POST['data'];
$plano_pgto = $_POST['plano_pgto'];
$quant_dias = $_POST['quant_dias']; // Valor de quant_dias
$nota_fiscal = $_POST['nota_fiscal'];
$vencimento = $_POST['vencimento'];
$fazenda = $_POST['fazenda'];
$desc_funrural = str_replace(',', '.', $_POST['desc_funrural'] ?? '0');
$desc_ima_aban = str_replace(',', '.', $_POST['desc_ima_aban'] ?? '0');

// Se quant_dias estiver vazio, atribui o valor padrão 0
if ($quant_dias === '' || !is_numeric($quant_dias)) {
    $quant_dias = 0; // Pode ser também NULL, dependendo da sua necessidade no banco de dados
}

// Campos que podem ser arrays
$quant_caixa_1 = $_POST['quant_caixa_1'] ?? [];
$produto_1 = $_POST['produto_1'] ?? [];
$preco_kg_1 = $_POST['preco_kg_1'] ?? [];
$tipo_cx_1 = $_POST['tipo_cx_1'] ?? [];
$preco_unit_1 = $_POST['preco_unit_1'] ?? [];
$valor_1 = $_POST['valor_1'] ?? [];

// Converter para float
$desc_funrural = floatval(str_replace(',', '.', $_POST['desc_funrural'] ?? '0'));
$desc_ima_aban = floatval(str_replace(',', '.', $_POST['desc_ima_aban'] ?? '0'));

// Calcular total bruto
$total_bruto = array_reduce($valor_1, function ($carry, $item) {
    return $carry + (float) str_replace(',', '.', $item);
}, 0);

// Aplicar os descontos
$total_liquido = $total_bruto;

// Aplicar desconto FUNRURAL se houver
if ($desc_funrural > 0) {
    $total_liquido -= $desc_funrural;
}

// Aplicar desconto IMA/ABAN se houver
if ($desc_ima_aban > 0) {
    $total_liquido -= $desc_ima_aban;
}

// Inicializando os arrays validados
$quant_caixa_1_val = [];
$produto_1_val = [];
$preco_kg_1_val = [];
$tipo_cx_1_val = [];
$preco_unit_1_val = [];
$valor_1_val = [];

function validarCampo($campo)
{
    $campo_validado = [];
    if (is_array($campo)) {
        foreach ($campo as $valor) {
            if ($valor != "") {
                $campo_validado[] = $valor;
            }
        }
    }
    return $campo_validado;
}

// Aplicando a validação para cada campo
$quant_caixa_1_val = validarCampo($quant_caixa_1);
$produto_1_val = validarCampo($produto_1);
$preco_kg_1_val = validarCampo($preco_kg_1);
$tipo_cx_1_val = validarCampo($tipo_cx_1);
$preco_unit_1_val = validarCampo($preco_unit_1);
$valor_1_val = validarCampo($valor_1);

// Validação dos arrays antes de acessar índices
if (!empty($quant_caixa_1_val)) {
    foreach ($quant_caixa_1_val as $key => $valor) {
        if (!empty($valor)) {
            $tem_produtos = true;
            
            // Validação dos campos do produto
            if (empty($produto_1_val[$key])) {
                $erros[] = "Selecione a variedade para todos os produtos";
                break;
            }
            if (empty($tipo_cx_1_val[$key])) {
                $erros[] = "Selecione o tipo de caixa para todos os produtos";
                break;
            }
            if (empty($quant_caixa_1_val[$key]) || $quant_caixa_1_val[$key] <= 0) {
                $erros[] = "Quantidade de caixas deve ser maior que zero";
                break;
            }
        }
    }
}

if (!$tem_produtos) {
    $erros[] = "Adicione pelo menos um produto";
}

// Se houver erros, retorna
if (!empty($erros)) {
    echo json_encode([
        'status' => 'erro',
        'mensagem' => implode("<br>", $erros)
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

// Preparar a query
if ($id == "") {
    $query = $pdo->prepare("INSERT INTO $tabela SET 
        fornecedor = :fornecedor, 
        quant_dias = :quant_dias, 
        data = :data, 
        nota_fiscal = :nota_fiscal, 
        plano_pgto = :plano_pgto, 
        vencimento = :vencimento, 
        total_liquido = :total_liquido, 
        fazenda = :fazenda, 
        desc_funrural = :desc_funrural, 
        desc_ima_aban = :desc_ima_aban");
} else {
    $query = $pdo->prepare("UPDATE $tabela SET 
        fornecedor = :fornecedor, 
        quant_dias = :quant_dias, 
        data = :data, 
        nota_fiscal = :nota_fiscal, 
        plano_pgto = :plano_pgto, 
        vencimento = :vencimento, 
        total_liquido = :total_liquido, 
        fazenda = :fazenda, 
        desc_funrural = :desc_funrural, 
        desc_ima_aban = :desc_ima_aban 
        WHERE id = :id");
}

$query->bindValue(":fornecedor", $fornecedor);
$query->bindValue(":data", $data);
$query->bindValue(":nota_fiscal", $nota_fiscal);
$query->bindValue(":plano_pgto", $plano_pgto);
$query->bindValue(":vencimento", DateTime::createFromFormat('Y-m-d', $vencimento)->format('Y-m-d'));
$query->bindValue(":total_liquido", $total_liquido);
$query->bindValue(":quant_dias", $quant_dias);
$query->bindValue(":fazenda", $fazenda);
$query->bindValue(":desc_funrural", $desc_funrural);
$query->bindValue(":desc_ima_aban", $desc_ima_aban);

if ($id != "") {
    $query->bindValue(":id", $id);
}

if ($query->execute()) {
    // Após salvar o romaneio_compra, inserir os produtos
    if ($id != "") {
        $pdo->prepare("DELETE FROM linha_produto_compra WHERE id_romaneio = ?")->execute([$id]);
    }

    foreach ($_POST['valor_1'] as $key => $valor) {
        if (!empty($valor)) {
            $query = $pdo->prepare("INSERT INTO linha_produto_compra (
                id_romaneio, 
                quant, 
                variedade, 
                preco_kg, 
                tipo_caixa, 
                preco_unit, 
                valor
            ) VALUES (?, ?, ?, ?, ?, ?, ?)");
            
            // Buscar o ID do tipo_caixa baseado no valor decimal
            $tipo_caixa_valor = str_replace(',', '.', $_POST['tipo_cx_1'][$key]);
            $stmt = $pdo->prepare("SELECT id FROM tipo_caixa WHERE tipo = ?");
            $stmt->execute([$tipo_caixa_valor]);
            $tipo_caixa = $stmt->fetchColumn();
            
            if (!$tipo_caixa) {
                // Se não encontrar o tipo_caixa, insere um novo
                $stmt = $pdo->prepare("INSERT INTO tipo_caixa (tipo, unidade_medida) VALUES (?, 1)");
                $stmt->execute([$tipo_caixa_valor]);
                $tipo_caixa = $pdo->lastInsertId();
            }
            
            $query->execute([
                $id ?: $pdo->lastInsertId(),
                $_POST['quant_caixa_1'][$key],
                $_POST['produto_1'][$key],
                str_replace(',', '.', $_POST['preco_kg_1'][$key]),
                $tipo_caixa, // Agora usando o ID do tipo_caixa
                str_replace(',', '.', $_POST['preco_unit_1'][$key]),
                str_replace(',', '.', $valor)
            ]);
        }
    }

    echo json_encode([
        'status' => 'sucesso',
        'mensagem' => 'Salvo com Sucesso'
    ], JSON_UNESCAPED_UNICODE);
} else {
    echo json_encode([
        'status' => 'erro',
        'mensagem' => 'Erro ao salvar no banco de dados'
    ], JSON_UNESCAPED_UNICODE);
}