<?php
// Monta a consulta do extrato a partir dos filtros (usada pela listagem e pela exportação)
function extrato_consulta($pdo, array $f)
{
    $banco = (int) ($f['banco'] ?? 0);
    $ini = fin_data_valida($f['ini'] ?? '') ? $f['ini'] : date('Y-m-01');
    $fim = fin_data_valida($f['fim'] ?? '') ? $f['fim'] : date('Y-m-t');
    $where = ["DATE(l.data) BETWEEN ? AND ?"];
    $p = [$ini, $fim];
    if ($banco) { $where[] = "l.id_banco = ?"; $p[] = $banco; }
    if (($f['tipo'] ?? '') === 'credito') $where[] = "l.credito > 0";
    if (($f['tipo'] ?? '') === 'debito') $where[] = "l.debito > 0";
    if (($f['conciliado'] ?? '') !== '' && isset($f['conciliado'])) { $where[] = "l.conciliado = ?"; $p[] = (int) $f['conciliado']; }
    $origem = $f['origem'] ?? '';
    if ($origem === 'legado') $where[] = "(l.origem IS NULL OR l.origem IN ('legado_estorno', 'estorno_legado'))";
    elseif ($origem === 'manual') $where[] = "l.origem IN ('manual', 'saldo_inicial')";
    elseif ($origem !== '') { $where[] = "l.origem = ?"; $p[] = $origem; }
    $busca = trim($f['busca'] ?? '');
    if ($busca !== '') { $where[] = "(l.descricao LIKE ? OR l.documento LIKE ? OR l.n_fiscal LIKE ?)"; $p[] = "%$busca%"; $p[] = "%$busca%"; $p[] = "%$busca%"; }

    $linhas = fin_q($pdo, "SELECT l.*, b.banco nome_banco, cr.nome cat_rec, cp.nome cat_pag, cc.nome centro,
            rp.id_receber, pp.id_pagar
        FROM linha_bancos l
        JOIN bancos b ON b.id = l.id_banco
        LEFT JOIN categorias_receber cr ON cr.id = l.categoria_receber
        LEFT JOIN categorias_pagar cp ON cp.id = l.categoria_pagar
        LEFT JOIN centros_custo cc ON cc.id = l.centro_custo
        LEFT JOIN receber_pagamentos rp ON l.origem = 'receber_pgto' AND rp.id = l.id_origem
        LEFT JOIN pagar_pagamentos pp ON l.origem = 'pagar_pgto' AND pp.id = l.id_origem
        WHERE " . implode(' AND ', $where) . " ORDER BY DATE(l.data), l.id", $p)->fetchAll(PDO::FETCH_ASSOC);

    // Saldo anterior só faz sentido sem filtros que escondem movimentos
    $filtrado = ($f['tipo'] ?? '') !== '' || ($f['conciliado'] ?? '') !== '' || $origem !== '' || $busca !== '';
    $anterior = $filtrado ? null : fin_saldo($pdo, $banco ?: null, date('Y-m-d', strtotime($ini . ' -1 day')));
    return ['linhas' => $linhas, 'anterior' => $anterior, 'ini' => $ini, 'fim' => $fim, 'banco' => $banco];
}

function extrato_origem_rotulo(array $l)
{
    switch ($l['origem']) {
        case 'receber_pgto': return ['Recebimento', 'success', $l['id_receber'] ? 'Título a receber #' . $l['id_receber'] : ''];
        case 'pagar_pgto': return ['Pagamento', 'danger', $l['id_pagar'] ? 'Título a pagar #' . $l['id_pagar'] : ''];
        case 'transferencia': return ['Transferência', 'secondary', ''];
        case 'manual': return ['Avulso', 'info', ''];
        case 'saldo_inicial': return ['Saldo inicial', 'dark', ''];
        case 'adiantamento': return ['Adiantamento', 'primary', 'Adiantamento #' . $l['id_origem']];
        case 'estorno_legado':
        case 'legado_estorno': return ['Estorno antigo', 'warning', ''];
        default: return ['Antigo', 'light', 'Lançamento anterior à atualização, sem vínculo'];
    }
}
