<?php
$tabela = 'romaneio_compra';
require_once("../../../conexao.php");

@session_start();
$id_usuario = @$_SESSION['id'];

// Configuração inicial
header('Content-Type: application/json; charset=utf-8');

// Validações básicas dos campos obrigatórios
$erros = [];


$id = $_POST['id'];
$fornecedor = $_POST['fornecedor'];
$cliente = $_POST['cliente'];
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

// Validação do Fornecedor
if (empty($_POST['fornecedor']) || $_POST['fornecedor'] == '0') {
    $erros[] = "Selecione um fornecedor";
}


if (empty($_POST['cliente']) || $_POST['cliente'] == '0') {
    $erros[] = "Selecione um Cliente";
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
        cliente = :cliente, 
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
        cliente = :cliente,
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
$query->bindValue(":cliente", $cliente);
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
        
    // 1) determina o ID real do romaneio salvo
    if ($id === "" || $id === null) {
        // acabou de inserir um novo romaneio
        $romaneioId = $pdo->lastInsertId();
    } else {
        // estava editando um existente
        $romaneioId = $id;
    }

    // 2) apaga as linhas antigas (se for edição)
    $delete = $pdo->prepare("DELETE FROM linha_produto_compra WHERE id_romaneio = ?");
    $delete->execute([$romaneioId]);

    // 3) prepara UMA SÓ vez o statement de inserção de linha
    $insertLinha = $pdo->prepare("
        INSERT INTO linha_produto_compra
        (id_romaneio, quant, variedade, preco_kg, tipo_caixa, preco_unit, valor)
        VALUES
        (?, ?, ?, ?, ?, ?, ?)
    ");

    foreach ($quant_caixa_1_val as $key => $q) {
        $v       = $valor_1_val[$key];
        $var     = $produto_1_val[$key];
        $pkg     = str_replace(',', '.', $preco_kg_1_val[$key]);
        $pun     = str_replace(',', '.', $preco_unit_1_val[$key]);
        $tipoVal = str_replace(',', '.', $tipo_cx_1_val[$key]);

        // pega ou cria o tipo_caixa
        $stmtTipo = $pdo->prepare("SELECT id FROM tipo_caixa WHERE tipo = ?");
        $stmtTipo->execute([$tipoVal]);
        $tipoCx = $stmtTipo->fetchColumn();
        if (!$tipoCx) {
            $stmtNew = $pdo->prepare("INSERT INTO tipo_caixa (tipo, unidade_medida) VALUES (?, 1)");
            $stmtNew->execute([$tipoVal]);
            $tipoCx = $pdo->lastInsertId();
        }

        // 4) insere usando sempre $romaneioId
        $insertLinha->execute([
            $romaneioId,
            $q,
            $var,
            $pkg,
            $tipoCx,
            $pun,
            str_replace(',', '.', $v)
        ]);
    }

    // 5) devolve sucesso
    echo json_encode([
    'status'  => 'sucesso',
    'mensagem'=> 'Salvo com sucesso',
    'id'      => $romaneioId
    ], JSON_UNESCAPED_UNICODE);
    exit;
} else {
    echo json_encode([
        'status' => 'erro',
        'mensagem' => 'Erro ao salvar no banco de dados'
    ], JSON_UNESCAPED_UNICODE);
}