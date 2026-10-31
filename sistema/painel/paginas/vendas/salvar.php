<?php
require_once("../../../conexao.php");
require_once("funcoes_venda.php");
@session_start();
$id_usuario = $_SESSION['id'];
$data_atual = date('Y-m-d');

// --- 1. DADOS DO FORMULÁRIO ---
$id_venda_edicao = intval($_POST['id_venda_edicao'] ?? 0);
$cliente = intval($_POST['cliente'] ?? 0);
$saida = intval($_POST['saida'] ?? 0); // forma_pagamento_id
$data = $_POST['data2'] ?? '';
if (empty($data)) {
    $data = $data_atual;
}
$desconto = venda_valor_br($_POST['desconto'] ?? '');
$frete = venda_valor_br($_POST['frete'] ?? '');
$tipo_desconto = ($_POST['tipo_desconto'] ?? '') == '%' ? '%' : 'valor';
$valor_pago = venda_valor_br($_POST['valor_pago'] ?? '');
$troco = venda_valor_br($_POST['troco'] ?? '');
$forma_pgto2 = intval($_POST['forma_pgto2'] ?? 0);
$data_restante = $_POST['data_restante'] ?? '';
if (empty($data_restante)) {
    $data_restante = $data;
}

// --- 2. VALIDAÇÕES ---
if ($cliente <= 0) {
    echo 'Você precisa selecionar um cliente para a venda!';
    exit();
}
if ($saida <= 0) {
    echo 'Selecione a forma de pagamento!';
    exit();
}
if ($desconto < 0 || $frete < 0 || $valor_pago < 0) {
    echo 'Desconto, frete e valor pago não podem ser negativos!';
    exit();
}

// O carrinho do usuário no banco é a fonte da verdade: a tela só exibe.
$stmt = $pdo->prepare("SELECT iv.*, m.nome, m.tem_estoque, m.estoque FROM itens_venda iv
    JOIN materiais m ON m.id = iv.material
    WHERE iv.funcionario = :f AND iv.id_venda = 0 ORDER BY iv.id");
$stmt->execute([':f' => $id_usuario]);
$itens = $stmt->fetchAll(PDO::FETCH_ASSOC);

if (count($itens) == 0) {
    echo 'Adicione pelo menos um item à venda!';
    exit();
}

$subtotal = 0;
$qtd_por_material = [];
foreach ($itens as $item) {
    if ($item['valor'] <= 0) {
        echo 'Informe o preço unitário do item ' . $item['nome'] . '!';
        exit();
    }
    $subtotal += $item['total'];
    $qtd_por_material[$item['material']] = ($qtd_por_material[$item['material']] ?? 0) + $item['quantidade'];
}
$subtotal = round($subtotal, 2);

$valor_desconto = ($tipo_desconto == '%') ? round($subtotal * $desconto / 100, 2) : $desconto;
if ($valor_desconto > $subtotal) {
    echo 'O desconto não pode ser maior que o valor dos itens!';
    exit();
}
$total_final = round($subtotal - $valor_desconto + $frete, 2);
if ($total_final <= 0) {
    echo 'O valor final da Venda tem que ser maior que zero.';
    exit();
}

// Valor pago acima do total é troco: só o total entra no caixa.
if ($valor_pago > $total_final) {
    $valor_pago = $total_final;
}
$valor_restante = round($total_final - $valor_pago, 2);
if ($valor_restante > 0 && $forma_pgto2 <= 0) {
    $forma_pgto2 = $saida;
}

// Venda com data futura é a prazo: a entrada fica a receber até a baixa.
$entrada_recebida = ($data <= $data_atual);

// --- 3. GRAVAÇÃO ---
$pdo->beginTransaction();
try {
    $vendedor = $id_usuario;

    if ($id_venda_edicao > 0) {
        $stmt = $pdo->prepare("SELECT * FROM vendas WHERE id = :id FOR UPDATE");
        $stmt->execute([':id' => $id_venda_edicao]);
        $venda_antiga = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$venda_antiga) {
            throw new Exception('Venda #' . $id_venda_edicao . ' não encontrada.');
        }
        if ($venda_antiga['status_pagamento'] == 'Cancelada') {
            throw new Exception('Venda #' . $id_venda_edicao . ' está cancelada e não pode ser editada.');
        }
        $vendedor = $venda_antiga['vendedor_id'];

        // Desfaz tudo o que a versão anterior da venda gerou
        venda_devolver_estoque($pdo, $id_venda_edicao);
        $pdo->prepare("DELETE FROM detalhes_materiais WHERE id_venda_real = :id")->execute([':id' => $id_venda_edicao]);
        $pdo->prepare("DELETE FROM receber WHERE id_venda_real = :id")->execute([':id' => $id_venda_edicao]);
        $pdo->prepare("DELETE FROM pagar WHERE id_venda_real = :id AND referencia = 'Comissão' AND pago <> 'Sim'")->execute([':id' => $id_venda_edicao]);
        $pdo->prepare("DELETE FROM itens_venda WHERE id_venda_real = :id")->execute([':id' => $id_venda_edicao]);
    }

    // Estoque: confere e abate uma única vez, aqui
    foreach ($qtd_por_material as $id_material => $quantidade) {
        $stmt = $pdo->prepare("SELECT nome, tem_estoque, estoque FROM materiais WHERE id = :id FOR UPDATE");
        $stmt->execute([':id' => $id_material]);
        $mat = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($mat['tem_estoque'] == 'Sim' && $mat['estoque'] < $quantidade) {
            throw new Exception('Estoque insuficiente para o item ' . $mat['nome'] . '. Disponível: ' . $mat['estoque']);
        }
        venda_mover_estoque($pdo, $id_material, $quantidade, -1);
    }

    $dados_venda = [
        ':cliente_id' => $cliente,
        ':vendedor_id' => $vendedor,
        ':data_venda' => $data . ' ' . date('H:i:s'),
        ':subtotal' => $subtotal,
        ':tipo_desconto' => $tipo_desconto,
        ':desconto' => $desconto,
        ':frete' => $frete,
        ':valor_total' => $total_final,
        ':forma_pagamento_id' => $saida,
    ];
    $campos_venda = "cliente_id = :cliente_id, vendedor_id = :vendedor_id, data_venda = :data_venda, subtotal = :subtotal,
        tipo_desconto = :tipo_desconto, desconto = :desconto, frete = :frete, valor_total = :valor_total,
        forma_pagamento_id = :forma_pagamento_id";

    if ($id_venda_edicao > 0) {
        $pdo->prepare("UPDATE vendas SET $campos_venda WHERE id = :id")->execute($dados_venda + [':id' => $id_venda_edicao]);
        $id_venda_real = $id_venda_edicao;
    } else {
        $pdo->prepare("INSERT INTO vendas SET $campos_venda")->execute($dados_venda);
        $id_venda_real = $pdo->lastInsertId();
    }

    // A. Movimentação de material
    $stmt_detalhes = $pdo->prepare("INSERT INTO detalhes_materiais (material_id, data, venda, valor_venda, preco_unidade, saida_estoque, descricao, id_venda_real)
        VALUES (:material_id, :data, :venda, :valor_venda, :preco_unidade, :saida_estoque, :descricao, :id_venda_real)");
    foreach ($itens as $item) {
        $stmt_detalhes->execute([
            ':material_id' => $item['material'],
            ':data' => $data,
            ':venda' => $item['quantidade'],
            ':valor_venda' => $item['total'],
            ':preco_unidade' => $item['valor'],
            ':saida_estoque' => $item['quantidade'],
            ':descricao' => $cliente,
            ':id_venda_real' => $id_venda_real,
        ]);
    }

    // B. Contas a receber: entrada (o que foi pago agora) e restante
    $sql_receber = "INSERT INTO receber SET descricao = :descricao, cliente = :cliente, valor = :valor, subtotal = :subtotal,
        vencimento = :vencimento, data_lanc = curDate(), data_pgto = :data_pgto, usuario_lanc = :usuario_lanc,
        usuario_pgto = :usuario_pgto, pago = :pago, forma_pgto = :forma_pgto, frequencia = 0, referencia = 'Venda',
        id_ref = :id_ref, frete = :frete, desconto = :desconto, tipo_desconto = :tipo_desconto, troco = :troco,
        valor_restante = :valor_restante, forma_pgto_restante = :forma_pgto_restante, data_restante = :data_restante,
        hora = curTime(), id_venda_real = :id_venda_real";
    $comuns = [
        ':cliente' => $cliente,
        ':usuario_lanc' => $id_usuario,
        ':frete' => $frete,
        ':desconto' => $desconto,
        ':tipo_desconto' => $tipo_desconto,
        ':troco' => $troco,
        ':valor_restante' => $valor_restante,
        ':forma_pgto_restante' => $valor_restante > 0 ? $forma_pgto2 : null,
        ':data_restante' => $valor_restante > 0 ? $data_restante : null,
        ':id_venda_real' => $id_venda_real,
    ];
    $id_receber_principal = 0;

    if ($valor_pago > 0) {
        $pdo->prepare($sql_receber)->execute($comuns + [
            ':descricao' => 'Venda #' . $id_venda_real . ($valor_restante > 0 ? ' (Entrada)' : ''),
            ':valor' => $valor_pago,
            ':subtotal' => $valor_pago,
            ':vencimento' => $data,
            ':data_pgto' => $entrada_recebida ? $data : null,
            ':usuario_pgto' => $entrada_recebida ? $id_usuario : 0,
            ':pago' => $entrada_recebida ? 'Sim' : 'Não',
            ':forma_pgto' => $saida,
            ':id_ref' => null,
        ]);
        $id_receber_principal = $pdo->lastInsertId();
    }

    if ($valor_restante > 0) {
        $pdo->prepare($sql_receber)->execute($comuns + [
            ':descricao' => 'Venda #' . $id_venda_real . ($valor_pago > 0 ? ' (Restante)' : ''),
            ':valor' => $valor_restante,
            ':subtotal' => $valor_restante,
            ':vencimento' => $data_restante,
            ':data_pgto' => null,
            ':usuario_pgto' => 0,
            ':pago' => 'Não',
            ':forma_pgto' => $forma_pgto2,
            ':id_ref' => $id_receber_principal ?: null,
        ]);
        if (!$id_receber_principal) {
            $id_receber_principal = $pdo->lastInsertId();
        }
    }

    // C. Vincula o carrinho à venda
    $pdo->prepare("UPDATE itens_venda SET id_venda = :rec, id_venda_real = :venda, codigo = NULL WHERE funcionario = :f AND id_venda = 0")
        ->execute([':rec' => $id_receber_principal, ':venda' => $id_venda_real, ':f' => $id_usuario]);

    // D. Comissão do vendedor (sobre os itens com desconto, sem o frete)
    $stmt = $pdo->prepare("SELECT comissao FROM usuarios WHERE id = :id");
    $stmt->execute([':id' => $vendedor]);
    $comissao = (float) $stmt->fetchColumn();
    $valor_da_comissao = round(($subtotal - $valor_desconto) * $comissao / 100, 2);
    // Comissão já paga numa versão anterior da venda não é lançada de novo
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM pagar WHERE id_venda_real = :id AND referencia = 'Comissão'");
    $stmt->execute([':id' => $id_venda_real]);
    if ($valor_da_comissao > 0 && $stmt->fetchColumn() == 0) {
        $pdo->prepare("INSERT INTO pagar SET descricao = :desc, fornecedor = 0, funcionario = :func, valor = :val,
                vencimento = :venc, data_lanc = curDate(), usuario_lanc = :user, forma_pgto = 0, frequencia = 0,
                pago = 'Não', referencia = 'Comissão', id_ref = :id_ref, id_venda_real = :venda")
            ->execute([
                ':desc' => 'Comissão Venda #' . $id_venda_real,
                ':func' => $vendedor,
                ':val' => $valor_da_comissao,
                ':venc' => date('Y-m-t', strtotime($data)),
                ':user' => $id_usuario,
                ':id_ref' => $id_receber_principal,
                ':venda' => $id_venda_real,
            ]);
    }

    venda_recalcular_status($pdo, $id_venda_real);

    $pdo->commit();
    unset($_SESSION['carrinho_em_modo_edicao'], $_SESSION['id_venda_em_edicao']);
    echo 'Salvo com Sucesso-' . $id_venda_real;
} catch (Exception $e) {
    $pdo->rollBack();
    echo 'Erro ao salvar: ' . $e->getMessage();
}
