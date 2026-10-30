<?php
// Regras da venda de diversos compartilhadas pelo PDV (vendas), Vendas Detalhes
// e Lista de Vendas. Quem inclui já tem $pdo aberto.

// Converte "1.234,56" (digitado) ou "1234.56" (vindo do banco/JS) em float.
function venda_valor_br($v)
{
    $v = trim(str_replace(['R$', ' '], '', (string) $v));
    if ($v === '') return 0.0;
    // Vírgula decimal ou só pontos de milhar ("1.000") seguem o formato BR
    if (strpos($v, ',') !== false || preg_match('/^-?\d{1,3}(\.\d{3})+$/', $v)) {
        $v = str_replace(',', '.', str_replace('.', '', $v));
    }
    return (float) $v;
}

// Estoque só é controlado para materiais com tem_estoque = 'Sim'; o contador
// de vendas do material anda para todos. $sinal = -1 abate, +1 devolve.
function venda_mover_estoque($pdo, $id_material, $quantidade, $sinal)
{
    $pdo->prepare("UPDATE materiais SET
            estoque = IF(tem_estoque = 'Sim', estoque + :q1, estoque),
            vendas = COALESCE(vendas, 0) - :q2
        WHERE id = :id")
        ->execute([':q1' => $sinal * $quantidade, ':q2' => $sinal * $quantidade, ':id' => $id_material]);
}

function venda_devolver_estoque($pdo, $id_venda_real)
{
    $stmt = $pdo->prepare("SELECT material, quantidade FROM itens_venda WHERE id_venda_real = :id");
    $stmt->execute([':id' => $id_venda_real]);
    foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $item) {
        venda_mover_estoque($pdo, $item['material'], $item['quantidade'], +1);
    }
}

// Quantidade de um material já reservada pela venda em edição: ela volta ao
// estoque quando a edição é salva, então conta como disponível.
function venda_qtd_em_edicao($pdo, $id_venda_real, $id_material)
{
    if (!$id_venda_real) return 0;
    $stmt = $pdo->prepare("SELECT COALESCE(SUM(quantidade), 0) FROM itens_venda WHERE id_venda_real = :v AND material = :m");
    $stmt->execute([':v' => $id_venda_real, ':m' => $id_material]);
    return (float) $stmt->fetchColumn();
}

// Valor pago, restante e status da venda são derivados das contas a receber dela.
function venda_recalcular_status($pdo, $id_venda_real)
{
    $stmt = $pdo->prepare("SELECT valor_total, status_pagamento FROM vendas WHERE id = :id");
    $stmt->execute([':id' => $id_venda_real]);
    $venda = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$venda || $venda['status_pagamento'] == 'Cancelada') return;

    $stmt = $pdo->prepare("SELECT COALESCE(SUM(valor), 0) FROM receber
        WHERE id_venda_real = :id AND pago = 'Sim' AND COALESCE(cancelada, '') <> 'Sim'");
    $stmt->execute([':id' => $id_venda_real]);
    $pago = round((float) $stmt->fetchColumn(), 2);

    $restante = max(0, round($venda['valor_total'] - $pago, 2));
    if ($restante <= 0) {
        $status = 'Pago';
    } elseif ($pago > 0) {
        $status = 'Parcialmente Pago';
    } else {
        $status = 'Aguardando Pagamento';
    }

    $pdo->prepare("UPDATE vendas SET valor_pago = :p, valor_restante = :r, status_pagamento = :s WHERE id = :id")
        ->execute([':p' => $pago, ':r' => $restante, ':s' => $status, ':id' => $id_venda_real]);
}

// Cancela a venda inteira: devolve o estoque, apaga o que está em aberto e
// registra devolução do que já foi recebido. Retorna '' ou a mensagem de erro.
function venda_cancelar($pdo, $id_venda_real, $id_usuario, $nivel)
{
    $stmt = $pdo->prepare("SELECT * FROM vendas WHERE id = :id");
    $stmt->execute([':id' => $id_venda_real]);
    $venda = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$venda) return 'Venda não encontrada!';
    if ($venda['status_pagamento'] == 'Cancelada') return 'Esta venda já está cancelada!';

    $stmt = $pdo->prepare("SELECT * FROM receber WHERE id_venda_real = :id AND COALESCE(cancelada, '') <> 'Sim'");
    $stmt->execute([':id' => $id_venda_real]);
    $contas = $stmt->fetchAll(PDO::FETCH_ASSOC);
    $pagas = array_filter($contas, fn($c) => $c['pago'] == 'Sim');

    if (count($pagas) > 0 && $nivel != 'Administrador' && $nivel != 'Gerente') {
        return 'Você não pode cancelar uma venda já Paga se não for um Administrador ou um Gerente!';
    }

    $pdo->beginTransaction();
    try {
        venda_devolver_estoque($pdo, $id_venda_real);
        $pdo->prepare("DELETE FROM detalhes_materiais WHERE id_venda_real = :id")->execute([':id' => $id_venda_real]);

        foreach ($contas as $conta) {
            if ($conta['pago'] == 'Sim') {
                $pdo->prepare("UPDATE receber SET cancelada = 'Sim' WHERE id = :id")->execute([':id' => $conta['id']]);
                $pdo->prepare("INSERT INTO pagar SET descricao = :desc, fornecedor = 0, funcionario = 0, cliente = :cli,
                        valor = :val, subtotal = :val2, vencimento = curDate(), data_pgto = curDate(), data_lanc = curDate(),
                        frequencia = 0, forma_pgto = :forma, usuario_lanc = :u1, usuario_pgto = :u2, arquivo = 'sem-foto.png',
                        pago = 'Sim', referencia = 'Cancelamento', id_ref = :ref, id_venda_real = :venda")
                    ->execute([
                        ':desc' => 'Devolução Venda #' . $id_venda_real, ':cli' => $conta['cliente'],
                        ':val' => $conta['valor'], ':val2' => $conta['valor'], ':forma' => (int) $conta['forma_pgto'],
                        ':u1' => $id_usuario, ':u2' => $id_usuario, ':ref' => $conta['id'], ':venda' => $id_venda_real
                    ]);
            } else {
                $pdo->prepare("DELETE FROM receber WHERE id = :id")->execute([':id' => $conta['id']]);
            }
        }

        $pdo->prepare("DELETE FROM pagar WHERE id_venda_real = :id AND referencia = 'Comissão' AND pago <> 'Sim'")
            ->execute([':id' => $id_venda_real]);
        $pdo->prepare("UPDATE vendas SET status_pagamento = 'Cancelada', valor_restante = 0 WHERE id = :id")
            ->execute([':id' => $id_venda_real]);

        $pdo->commit();
    } catch (Exception $e) {
        $pdo->rollBack();
        return 'Erro ao cancelar: ' . $e->getMessage();
    }
    return '';
}
