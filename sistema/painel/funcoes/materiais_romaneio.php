<?php
// Materiais usados no romaneio de venda (linha_observacao.descricao = id do material).
// Cada linha baixa o estoque e fica registrada em detalhes_materiais (id_romaneio),
// para aparecer na tela "Detalhes Materiais". Editar ou excluir o romaneio devolve
// antes o que a versão anterior tinha baixado.
//
// Usar sempre dentro da transação do romaneio.

// Devolve ao estoque o que o romaneio baixou e apaga o histórico dele.
// Chamar ANTES de apagar as linhas de linha_observacao.
function romaneio_materiais_devolver($pdo, $id_romaneio)
{
    $stmt = $pdo->prepare("SELECT descricao, quant FROM linha_observacao WHERE id_romaneio = ?");
    $stmt->execute([$id_romaneio]);
    $devolve = $pdo->prepare("UPDATE materiais SET
            estoque = IF(UPPER(tem_estoque) = 'SIM', estoque + ?, estoque),
            vendas = COALESCE(vendas, 0) - ?
        WHERE id = ?");
    foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $linha) {
        $id_material = (int) $linha['descricao'];
        $qtd = (float) $linha['quant'];
        if ($id_material > 0 && $qtd > 0) {
            $devolve->execute([$qtd, $qtd, $id_material]);
        }
    }
    $pdo->prepare("DELETE FROM detalhes_materiais WHERE id_romaneio = ?")->execute([$id_romaneio]);
}

// Baixa o estoque de um material usado no romaneio e registra a movimentação.
// Com $bloquear_sem_estoque, recusa quando o material controla estoque e não há saldo.
function romaneio_materiais_lancar($pdo, $id_romaneio, $id_material, $qtd, $preco_unit, $valor, $cliente, $data, $bloquear_sem_estoque)
{
    $id_material = (int) $id_material;
    $qtd = (float) $qtd;
    if ($id_material <= 0 || $qtd <= 0) return;

    $stmt = $pdo->prepare("SELECT nome, tem_estoque, estoque FROM materiais WHERE id = ? FOR UPDATE");
    $stmt->execute([$id_material]);
    $mat = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$mat) return;

    $controla = strtoupper((string) $mat['tem_estoque']) === 'SIM';
    if ($controla && $bloquear_sem_estoque && (float) $mat['estoque'] < $qtd) {
        throw new Exception("Estoque insuficiente para o material: '{$mat['nome']}'. Disponível: {$mat['estoque']}");
    }

    $pdo->prepare("UPDATE materiais SET
            estoque = IF(UPPER(tem_estoque) = 'SIM', estoque - ?, estoque),
            vendas = COALESCE(vendas, 0) + ?
        WHERE id = ?")
        ->execute([$qtd, $qtd, $id_material]);

    $pdo->prepare("INSERT INTO detalhes_materiais (material_id, data, venda, valor_venda, preco_unidade, saida_estoque, descricao, id_romaneio)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?)")
        ->execute([
            $id_material,
            $data,
            $qtd,
            (float) str_replace(',', '.', (string) $valor),
            (float) str_replace(',', '.', (string) $preco_unit),
            $controla ? $qtd : 0,
            $cliente,
            $id_romaneio,
        ]);
}
