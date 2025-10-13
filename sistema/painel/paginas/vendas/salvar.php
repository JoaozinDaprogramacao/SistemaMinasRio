<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require_once("../../../conexao.php");
@session_start();
$id_usuario = $_SESSION['id'];
$data_atual = date('Y-m-d');

// --- 1. CAPTURA E LIMPEZA DOS DADOS DO FORMULÁRIO ---
$id_venda_edicao = intval($_POST['id_venda_edicao'] ?? 0);
$cliente = $_POST['cliente'] ?? '';
$desconto = floatval(str_replace(',', '.', $_POST['desconto'] ?? '0'));
$saida = $_POST['saida'] ?? '';
$data = $_POST['data2'] ?? $data_atual;
if (empty($data)) {
    $data = $data_atual;
}
$frete = floatval(str_replace(',', '.', $_POST['frete'] ?? '0'));
$tipo_desconto = $_POST['tipo_desconto'] ?? 'reais';
$subtotal_venda = floatval(str_replace(',', '.', $_POST['subtotal_venda'] ?? '0'));
$valor_pago = floatval(str_replace(',', '.', $_POST['valor_pago'] ?? '0'));
$ids_itens_str = $_POST['ids_itens'] ?? '';
$valor_restante = floatval(str_replace(',', '.', $_POST['valor_restante'] ?? '0'));
$data_restante = $_POST['data_restante'] ?? $data_atual;
$forma_pgto2 = $_POST['forma_pgto2'] ?? "";

// --- 2. VALIDAÇÕES CRÍTICAS ---
if (empty($cliente) && $id_venda_edicao == 0) {
    echo 'Você precisa selecionar um cliente para criar uma nova venda!';
    exit();
}
if ($subtotal_venda <= 0 || empty($ids_itens_str)) {
    echo 'O valor da Venda tem que ser maior que zero. Adicione itens à venda.';
    exit();
}
$ids_itens = array_filter(explode(',', ltrim($ids_itens_str, ',')));
if (empty($ids_itens)) {
    echo 'Nenhum item válido na venda.';
    exit();
}

// --- 3. CÁLCULOS GERAIS ---
$valor_desconto_calc = ($tipo_desconto == '%') ? ($subtotal_venda * $desconto / 100) : $desconto;
$total_final = $subtotal_venda - $valor_desconto_calc + $frete;
if ($total_final <= 0) {
    echo 'O valor final da Venda tem que ser maior que zero.';
    exit();
}

// --- 4. LÓGICA PRINCIPAL ---
$pdo->beginTransaction();
try {
    if ($id_venda_edicao > 0) {
        // MODO DE EDIÇÃO
        // ... (lógica de UPDATE e DELETE que já está correta) ...
        $query_itens_antigos = $pdo->prepare("SELECT material, quantidade FROM itens_venda WHERE id_venda_real = :id");
        $query_itens_antigos->execute([':id' => $id_venda_edicao]);
        $itens_antigos = $query_itens_antigos->fetchAll(PDO::FETCH_ASSOC);
        foreach ($itens_antigos as $item) {
            $pdo->prepare("UPDATE materiais SET estoque = estoque + :qtd WHERE id = :id_mat")->execute([':qtd' => $item['quantidade'], ':id_mat' => $item['material']]);
        }
        foreach ($ids_itens as $id_item_temp) {
            $stmt = $pdo->prepare('SELECT iv.material, iv.quantidade, m.estoque, m.nome FROM itens_venda iv JOIN materiais m ON iv.material = m.id WHERE iv.id = :id');
            $stmt->execute([':id' => $id_item_temp]);
            $item_novo = $stmt->fetch();
            if ($item_novo['estoque'] < $item_novo['quantidade']) {
                throw new Exception("Estoque insuficiente para o item " . $item_novo['nome'] . ". Disponível: " . $item_novo['estoque']);
            }
            $pdo->prepare("UPDATE materiais SET estoque = estoque - :qtd WHERE id = :id_mat")->execute([':qtd' => $item_novo['quantidade'], ':id_mat' => $item_novo['material']]);
        }
        $status_pagamento = ($valor_pago >= $total_final) ? 'Pago' : (($valor_pago > 0) ? 'Parcialmente Pago' : 'Aguardando Pagamento');
        $stmt_update = $pdo->prepare("UPDATE vendas SET cliente_id = :cliente_id, vendedor_id = :vendedor_id, data_venda = :data_venda, subtotal = :subtotal, tipo_desconto = :tipo_desconto, desconto = :desconto, frete = :frete, valor_total = :valor_total, valor_pago = :valor_pago, forma_pagamento_id = :forma_pagamento_id, status_pagamento = :status_pagamento WHERE id = :id");
        $stmt_update->execute([':cliente_id' => $cliente, ':vendedor_id' => $id_usuario, ':data_venda' => $data . ' ' . date('H:i:s'), ':subtotal' => $subtotal_venda, ':tipo_desconto' => ($tipo_desconto == '%' ? '%' : 'valor'), ':desconto' => $desconto, ':frete' => $frete, ':valor_total' => $total_final, ':valor_pago' => $valor_pago, ':forma_pagamento_id' => $saida, ':status_pagamento' => $status_pagamento, ':id' => $id_venda_edicao]);
        $stmt_old_id = $pdo->prepare("SELECT id_venda FROM itens_venda WHERE id_venda_real = ? LIMIT 1");
        $stmt_old_id->execute([$id_venda_edicao]);
        $old_receber_id = $stmt_old_id->fetchColumn();
        if ($old_receber_id) {
            $pdo->prepare("DELETE FROM receber WHERE id = ? OR id_ref = ?")->execute([$old_receber_id, $old_receber_id]);
            $pdo->prepare("DELETE FROM pagar WHERE id_ref = ?")->execute([$old_receber_id]);
        }
        $pdo->prepare("DELETE FROM itens_venda WHERE id_venda_real = :id")->execute([':id' => $id_venda_edicao]);
        $id_venda_real = $id_venda_edicao;
    } else {
        // MODO DE CRIAÇÃO
        // ... (lógica de INSERT que já está correta) ...
        foreach ($ids_itens as $id_item_temp) {
            $stmt = $pdo->prepare('SELECT iv.material, iv.quantidade, m.estoque, m.nome FROM itens_venda iv JOIN materiais m ON iv.material = m.id WHERE iv.id = :id');
            $stmt->execute([':id' => $id_item_temp]);
            $item_novo = $stmt->fetch();
            if ($item_novo['estoque'] < $item_novo['quantidade']) {
                throw new Exception("Estoque insuficiente para o item " . $item_novo['nome'] . ". Disponível: " . $item_novo['estoque']);
            }
            $pdo->prepare("UPDATE materiais SET estoque = estoque - :qtd WHERE id = :id_mat")->execute([':qtd' => $item_novo['quantidade'], ':id_mat' => $item_novo['material']]);
        }
        $status_pagamento = ($valor_pago >= $total_final) ? 'Pago' : (($valor_pago > 0) ? 'Parcialmente Pago' : 'Aguardando Pagamento');
        $stmt_vendas = $pdo->prepare("INSERT INTO vendas SET cliente_id = :cliente_id, vendedor_id = :vendedor_id, data_venda = :data_venda, subtotal = :subtotal, tipo_desconto = :tipo_desconto, desconto = :desconto, frete = :frete, valor_total = :valor_total, valor_pago = :valor_pago, forma_pagamento_id = :forma_pagamento_id, status_pagamento = :status_pagamento");
        $stmt_vendas->execute([':cliente_id' => $cliente, ':vendedor_id' => $id_usuario, ':data_venda' => $data . ' ' . date('H:i:s'), ':subtotal' => $subtotal_venda, ':tipo_desconto' => ($tipo_desconto == '%' ? '%' : 'valor'), ':desconto' => $desconto, ':frete' => $frete, ':valor_total' => $total_final, ':valor_pago' => $valor_pago, ':forma_pagamento_id' => $saida, ':status_pagamento' => $status_pagamento]);
        $id_venda_real = $pdo->lastInsertId();
    }

    // --- 5. LÓGICA COMUM (EXECUTADA TANTO NA CRIAÇÃO QUANTO NA EDIÇÃO) ---

    // A. INSERE EM `detalhes_materiais` (COM A CORREÇÃO)
    foreach ($ids_itens as $id_item_temp) {
        $stmt = $pdo->prepare('SELECT material, quantidade, valor FROM itens_venda WHERE id = :id');
        $stmt->execute(['id' => $id_item_temp]);
        $row = $stmt->fetch();
        if ($row) {
            $stmt_detalhes = $pdo->prepare("INSERT INTO detalhes_materiais (material_id, data, venda, valor_venda, preco_unidade, saida_estoque, descricao) VALUES (:material_id, :data, :venda, :valor_venda, :preco_unidade, :saida_estoque, :descricao)");
            $stmt_detalhes->execute([
                ':material_id' => $row['material'],
                ':data' => $data,
                ':venda' => $row['quantidade'],
                ':valor_venda' => $row['quantidade'] * $row['valor'],
                ':preco_unidade' => $row['valor'],
                ':saida_estoque' => $row['quantidade'],
                ':descricao' => empty($cliente) ? null : intval($cliente)
            ]);
        }
    }

    // B. INSERE EM `receber` (com a correção do 'usuario_pgto')
    $usuario_pgto = 0; // Valor padrão para o usuário que pagou

    if ($valor_restante > 0) {
        // CORREÇÃO: Adicionado 'usuario_pgto = :user_pgto'
        $stmt_rec1 = $pdo->prepare("INSERT INTO receber SET descricao = :desc, valor = :val, vencimento = :venc, data_lanc = CURDATE(), usuario_lanc = :user, pago = 'Não', cliente = :cli, referencia = 'Venda', forma_pgto = :pgto, usuario_pgto = :user_pgto");
        $stmt_rec1->execute([':desc' => 'Venda #' . $id_venda_real . ' (Entrada)', ':val' => $valor_pago, ':venc' => $data, ':user' => $id_usuario, ':cli' => $cliente, ':pgto' => $saida, ':user_pgto' => $usuario_pgto]);
        $id_venda_receber = $pdo->lastInsertId();

        // CORREÇÃO: Adicionado 'usuario_pgto = :user_pgto'
        $stmt_rec2 = $pdo->prepare("INSERT INTO receber SET descricao = :desc, valor = :val, vencimento = :venc, data_lanc = CURDATE(), usuario_lanc = :user, pago = 'Não', cliente = :cli, referencia = 'Venda', forma_pgto = :pgto, id_ref = :id_ref, usuario_pgto = :user_pgto");
        $stmt_rec2->execute([':desc' => 'Venda #' . $id_venda_real . ' (Restante)', ':val' => $valor_restante, ':venc' => $data_restante, ':user' => $id_usuario, ':cli' => $cliente, ':pgto' => $forma_pgto2, ':id_ref' => $id_venda_receber, ':user_pgto' => $usuario_pgto]);
    } else {
        // CORREÇÃO: Adicionado 'usuario_pgto = :user_pgto'
        $stmt_rec_total = $pdo->prepare("INSERT INTO receber SET descricao = :desc, valor = :val, vencimento = :venc, data_lanc = CURDATE(), usuario_lanc = :user, pago = 'Não', cliente = :cli, referencia = 'Venda', forma_pgto = :pgto, usuario_pgto = :user_pgto");
        $stmt_rec_total->execute([':desc' => 'Venda #' . $id_venda_real, ':val' => $total_final, ':venc' => $data, ':user' => $id_usuario, ':cli' => $cliente, ':pgto' => $saida, ':user_pgto' => $usuario_pgto]);
        $id_venda_receber = $pdo->lastInsertId();
    }

    // C. VINCULA OS ITENS TEMPORÁRIOS
    $in_clause = implode(',', array_fill(0, count($ids_itens), '?'));
    $stmt_link = $pdo->prepare("UPDATE itens_venda SET id_venda = ?, id_venda_real = ? WHERE id IN ($in_clause) AND funcionario = ?");
    $params = array_merge([$id_venda_receber, $id_venda_real], $ids_itens, [$id_usuario]);
    $stmt_link->execute($params);

    // D. INSERE EM `pagar` (comissão)
    $query_comissao = $pdo->query("SELECT comissao FROM usuarios WHERE id = '$id_usuario'");
    $comissao = $query_comissao->fetchColumn() ?? 0;
    if ($comissao > 0) {
        $valor_da_comissao = ($subtotal_venda - $frete) * $comissao / 100;
        if ($valor_da_comissao > 0) {
            $data_final_mes = date('Y-m-t', strtotime($data));
            $stmt_pagar = $pdo->prepare("INSERT INTO pagar SET descricao = :desc, funcionario = :func, valor = :val, vencimento = :venc, data_lanc = CURDATE(), usuario_lanc = :user, pago = 'Não', referencia = 'Comissão', id_ref = :id_ref");
            $stmt_pagar->execute([':desc' => 'Comissão Venda #' . $id_venda_real, ':func' => $id_usuario, ':val' => $valor_da_comissao, ':venc' => $data_final_mes, ':user' => $id_usuario, ':id_ref' => $id_venda_receber]);
        }
    }

    // Confirma a transação, salvando tudo no banco
    $pdo->commit();
    echo 'Salvo com Sucesso-' . ($id_venda_edicao > 0 ? $id_venda_edicao : $id_venda_receber);
} catch (Exception $e) {
    // Se qualquer passo falhar, desfaz todas as operações
    $pdo->rollBack();
    echo 'Erro ao salvar: ' . $e->getMessage();
}
