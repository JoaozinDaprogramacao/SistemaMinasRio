<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require_once("../../../conexao.php");

@session_start();
$id_usuario = $_SESSION['id'];

$data_atual = date('Y-m-d');

// Função para converter datas do formato DD/MM/YYYY para YYYY-MM-DD
function convertDateToMySQL($date) {
    if (!empty($date)) {
        $dateParts = explode('/', $date);
        if (count($dateParts) == 3) {
            return $dateParts[2] . '-' . $dateParts[1] . '-' . $dateParts[0];
        }
    }
    return null; // Retorna NULL se a data for inválida
}

// Captura os dados do POST
$desconto = floatval(str_replace(',', '.', $_POST['desconto'] ?? '0'));
$cliente = $_POST['cliente'] ?? '';
$saida = $_POST['saida'] ?? ''; // forma_pagamento_id
$data = $_POST['data2'] ?? $data_atual;
$frete = floatval(str_replace(',', '.', $_POST['frete'] ?? '0'));
$tipo_desconto = $_POST['tipo_desconto'] ?? '';
$subtotal_venda = floatval(str_replace(',', '.', $_POST['subtotal_venda'] ?? '0'));
$valor_restante = floatval(str_replace(',', '.', $_POST['valor_restante'] ?? '0'));
$valor_pago = floatval(str_replace(',', '.', $_POST['valor_pago'] ?? '0'));
$troco = floatval(str_replace(',', '.', $_POST['troco'] ?? '0'));
$data_restante = $_POST['data_restante'] ?? $data_atual;
$forma_pgto2 = $_POST['forma_pgto2'] ?? "";
$ids_itens = $_POST['ids_itens'] ?? '';

// Lógica de verificação de estoque
$ids_itens = ltrim($ids_itens, ',');
$ids_itens = explode(',', $ids_itens);

foreach ($ids_itens as $id) {
    $stmt = $pdo->prepare('SELECT material, quantidade FROM itens_venda WHERE id = :id');
    $stmt->execute(['id' => $id]);
    $row = $stmt->fetch();
    if ($row) {
        $material = $row['material'];
        $quantidade = $row['quantidade'];

        $stmt_material = $pdo->prepare('SELECT estoque, nome FROM materiais WHERE id = :id');
        $stmt_material->execute(['id' => $material]);
        $row_material = $stmt_material->fetch();

        if ($row_material) {
            $estoque = $row_material['estoque'];
            if ($estoque < $quantidade) {
                $nome  = $row_material['nome'];
                echo "Erro: Estoque insuficiente para o item $nome. Estoque disponível: $estoque";
                exit();
            } else {
                $estoque_novo = $estoque - $quantidade;
                $stmt_update = $pdo->prepare('UPDATE materiais SET estoque = :estoque WHERE id = :id');
                $stmt_update->execute(['estoque' => $estoque_novo, 'id' => $material]);
            }
        }
    }
}

// Formatação e validação de dados
$data = convertDateToMySQL($data);
$data_restante = convertDateToMySQL($data_restante);
if (empty($data) || $data == '1970-01-01') { $data = $data_atual; }
if (empty($data_restante) || $data_restante == '1970-01-01') { $data_restante = $data_atual; }
if ($valor_restante > 0 && $forma_pgto2 == "" && $data_restante == $data_atual) { echo 'Você precisa selecionar uma forma de pagamento para o valor restante!'; exit(); }
if ($tipo_desconto == '%') { $desconto_valor = ($subtotal_venda * ($desconto / 100)); } else { $desconto_valor = $desconto; }
$total_final = $subtotal_venda - $desconto_valor + $frete;
if ($total_final <= 0) { echo 'O valor da Venda tem que ser maior que zero'; exit(); }


try {
    // 1. Define o status do pagamento
    $status_pagamento = 'Aguardando Pagamento';
    if ($valor_pago >= $total_final) {
        $status_pagamento = 'Pago';
    } elseif ($valor_pago > 0 && $valor_pago < $total_final) {
        $status_pagamento = 'Parcialmente Pago';
    }

    // 2. Prepara o statement para a nova tabela 'vendas'
    $stmt_vendas = $pdo->prepare("INSERT INTO vendas SET 
        cliente_id = :cliente_id, 
        vendedor_id = :vendedor_id, 
        data_venda = :data_venda, 
        subtotal = :subtotal, 
        tipo_desconto = :tipo_desconto, 
        desconto = :desconto, 
        frete = :frete, 
        valor_total = :valor_total, 
        valor_pago = :valor_pago, 
        troco = :troco, 
        forma_pagamento_id = :forma_pagamento_id, 
        data_vencimento_restante = :data_vencimento_restante, 
        forma_pagamento_restante_id = :forma_pagamento_restante_id, 
        status_pagamento = :status_pagamento
    ");

    // 3. Executa a inserção
    $stmt_vendas->execute([
        ':cliente_id' => $cliente,
        ':vendedor_id' => $id_usuario,
        ':data_venda' => $data . ' ' . date('H:i:s'),
        ':subtotal' => $subtotal_venda,
        ':tipo_desconto' => ($tipo_desconto == '%' ? '%' : 'valor'),
        ':desconto' => $desconto,
        ':frete' => $frete,
        ':valor_total' => $total_final,
        ':valor_pago' => $valor_pago,
        ':troco' => $troco,
        ':forma_pagamento_id' => $saida,
        ':data_vencimento_restante' => ($valor_restante > 0) ? $data_restante : null,
        ':forma_pagamento_restante_id' => ($valor_restante > 0 && !empty($forma_pgto2)) ? $forma_pgto2 : null,
        ':status_pagamento' => $status_pagamento
    ]);
    
    $id_venda_real = $pdo->lastInsertId();

} catch (PDOException $e) {
    echo 'Erro ao salvar os dados na tabela de vendas: ' . $e->getMessage();
    exit();
}

// Inserção em 'detalhes_materiais'
try {
    foreach ($ids_itens as $id) {
        $stmt_itens = $pdo->prepare('SELECT material, quantidade, valor FROM itens_venda WHERE id = :id');
        $stmt_itens->execute(['id' => $id]);
        $row_item = $stmt_itens->fetch();
        if ($row_item) {
            $stmt_detalhes = $pdo->prepare("INSERT INTO detalhes_materiais (material_id, data, compra, venda, valor_compra, valor_venda, preco_unidade, saida_estoque, entrada_estoque, descricao) VALUES (:material_id, :data, 0, :venda, 0, :valor_venda, :preco_unidade, :saida_estoque, 0, :descricao)");
            $stmt_detalhes->execute([
                ':material_id' => $row_item['material'],
                ':data' => $data,
                ':venda' => $row_item['quantidade'],
                ':valor_venda' => $row_item['quantidade'] * $row_item['valor'],
                ':preco_unidade' => $row_item['valor'],
                ':saida_estoque' => $row_item['quantidade'],
                ':descricao' => $cliente
            ]);
        }
    }
} catch (PDOException $e) {
    echo 'Erro ao inserir os dados na tabela de detalhes: ' . $e->getMessage();
    exit();
}

// Lógica de inserção em 'receber' e 'pagar'
$pago = 'Não';
$data_pgto = null;
$usuario_pgto = 0;

try {
    if ($valor_restante > 0) {
        $stmt1 = $pdo->prepare("INSERT INTO receber SET descricao = :descricao, valor = :valor, vencimento = :vencimento, data_lanc = CURDATE(), data_pgto = :data_pgto, usuario_lanc = :usuario_lanc, arquivo = 'sem-foto.png', pago = :pago, usuario_pgto = :usuario_pgto, cliente = :cliente, referencia = 'Venda', hora = CURTIME(), forma_pgto = :forma_pgto, desconto = :desconto, frete = :frete, tipo_desconto = :tipo_desconto, subtotal = :subtotal, valor_restante = :valor_restante, forma_pgto_restante = :forma_pgto_restante, data_restante = :data_restante");
        $stmt1->execute([':descricao' => 'Nova Venda', ':valor' => $valor_pago, ':vencimento' => $data, ':data_pgto' => $data_pgto, ':usuario_lanc' => $id_usuario, ':pago' => $pago, ':usuario_pgto' => $usuario_pgto, ':cliente' => $cliente, ':forma_pgto' => $saida, ':desconto' => $desconto, ':frete' => $frete, ':tipo_desconto' => $tipo_desconto, ':subtotal' => $subtotal_venda, ':valor_restante' => $valor_restante, ':forma_pgto_restante' => $forma_pgto2, ':data_restante' => $data_restante]);
        $id_venda = $pdo->lastInsertId();

        $pago2 = (strtotime($data_restante) > strtotime($data_atual)) ? 'Não' : 'Sim';
        $data_pgto2 = ($pago2 == 'Sim') ? $data_restante : null;

        $stmt2 = $pdo->prepare("INSERT INTO receber SET descricao = :descricao, valor = :valor, vencimento = :vencimento, data_lanc = CURDATE(), data_pgto = :data_pgto, usuario_lanc = :usuario_lanc, arquivo = 'sem-foto.png', pago = :pago, usuario_pgto = :usuario_pgto, cliente = :cliente, referencia = 'Venda', hora = CURTIME(), forma_pgto = :forma_pgto, desconto = :desconto, frete = :frete, tipo_desconto = :tipo_desconto, subtotal = :subtotal, valor_restante = :valor_restante, forma_pgto_restante = :forma_pgto_restante, data_restante = :data_restante, id_ref = :id_ref");
        $stmt2->execute([':descricao' => 'Nova Venda (Restante)', ':valor' => $valor_restante, ':vencimento' => $data_restante, ':data_pgto' => $data_pgto2, ':usuario_lanc' => $id_usuario, ':pago' => $pago2, ':usuario_pgto' => $usuario_pgto, ':cliente' => $cliente, ':forma_pgto' => $forma_pgto2, ':desconto' => $desconto, ':frete' => $frete, ':tipo_desconto' => $tipo_desconto, ':subtotal' => $subtotal_venda, ':valor_restante' => $valor_pago, ':forma_pgto_restante' => $saida, ':data_restante' => $data, ':id_ref' => $id_venda]);
    } else {
        $stmt3 = $pdo->prepare("INSERT INTO receber SET descricao = :descricao, valor = :valor, vencimento = :vencimento, data_lanc = CURDATE(), data_pgto = :data_pgto, usuario_lanc = :usuario_lanc, arquivo = 'sem-foto.png', pago = :pago, usuario_pgto = :usuario_pgto, cliente = :cliente, referencia = 'Venda', hora = CURTIME(), forma_pgto = :forma_pgto, desconto = :desconto, frete = :frete, tipo_desconto = :tipo_desconto, subtotal = :subtotal");
        $stmt3->execute([':descricao' => 'Nova Venda', ':valor' => $total_final, ':vencimento' => $data, ':data_pgto' => $data_pgto, ':usuario_lanc' => $id_usuario, ':pago' => $pago, ':usuario_pgto' => $usuario_pgto, ':cliente' => $cliente, ':forma_pgto' => $saida, ':desconto' => $desconto, ':frete' => $frete, ':tipo_desconto' => $tipo_desconto, ':subtotal' => $subtotal_venda]);
        $id_venda = $pdo->lastInsertId();
    }

    // Atualiza os itens da venda com os IDs corretos
    $pdo->query("UPDATE itens_venda SET id_venda = '$id_venda', id_venda_real = '$id_venda_real' WHERE id_venda = 0 AND funcionario = '$id_usuario'");

    // Lança a comissão do vendedor
    $query_comissao = $pdo->query("SELECT comissao FROM usuarios WHERE id = '$id_usuario'");
    $res_comissao = $query_comissao->fetch(PDO::FETCH_ASSOC);
    $comissao = $res_comissao['comissao'] ?? 0;
    
    if ($comissao > 0) {
        $valor_da_venda = $subtotal_venda - $frete;
        $valor_da_comissao = $valor_da_venda * $comissao / 100;
        $data_final_mes = date('Y-m-t', strtotime($data));

        $stmt_pagar = $pdo->prepare("INSERT INTO pagar SET descricao = :descricao, funcionario = :funcionario, valor = :valor, vencimento = :vencimento, data_lanc = CURDATE(), frequencia = '0', arquivo = 'sem-foto.png', subtotal = :subtotal, usuario_lanc = :usuario_lanc, pago = 'Não', referencia = 'Comissão', hora = CURTIME(), id_ref = :id_ref");
        $stmt_pagar->execute([':descricao' => 'Comissão Venda', ':funcionario' => $id_usuario, ':valor' => $valor_da_comissao, ':vencimento' => $data_final_mes, ':subtotal' => $valor_da_comissao, ':usuario_lanc' => $id_usuario, ':id_ref' => $id_venda]);
    }

    echo 'Salvo com Sucesso-' . $id_venda;

} catch (PDOException $e) {
    echo 'Erro ao salvar: ' . $e->getMessage();
}