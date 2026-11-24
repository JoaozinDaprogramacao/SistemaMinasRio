<?php
// =====================================================================
// SANEAMENTO DO HISTÓRICO FINANCEIRO
// ---------------------------------------------------------------------
// Corrige dados gravados pelas versões antigas do sistema para que fiquem
// no modelo atual (todo pagamento registrado e ligado à sua linha no extrato).
// Só faz automaticamente o que é provado pelos próprios dados; o que depende
// do extrato real do banco é listado para decisão do administrador.
// =====================================================================

require_once __DIR__ . '/financeiro.php';

// Linha antiga do extrato (sem vínculo) com a mesma conta/data/valor, ainda não usada
function san_linha_legado($pdo, $lado, $valor, $data, $banco = null)
{
    $sql = "SELECT * FROM linha_bancos WHERE origem IS NULL AND $lado = ? AND DATE(data) = ?";
    $p = [round($valor, 2), $data];
    if ($banco) { $sql .= " AND id_banco = ?"; $p[] = $banco; }
    return fin_q($pdo, "$sql ORDER BY id LIMIT 1", $p)->fetch(PDO::FETCH_ASSOC) ?: null;
}

function san_vincular_linha($pdo, $id_linha, $origem, $id_origem, $descricao = null)
{
    fin_q($pdo, "UPDATE linha_bancos SET origem = ?, id_origem = ?" . ($descricao !== null ? ", descricao = ?" : '') . " WHERE id = ?",
        $descricao !== null ? [$origem, $id_origem, mb_substr($descricao, 0, 255), $id_linha] : [$origem, $id_origem, $id_linha]);
}

// Acerta desconto/acréscimo para que valor + ajustes = o que foi efetivamente pago
// (as versões antigas gravavam o valor pago no subtotal sem registrar a diferença)
function san_reconciliar_ajustes($pdo, $tabela, array $t, $pago)
{
    $esperado = round($t['valor'] + (float) $t['multa'] + (float) $t['juros'] + (float) $t['taxa'] - (float) $t['desconto'], 2);
    $dif = round($esperado - $pago, 2);
    if ($dif > 0) fin_q($pdo, "UPDATE $tabela SET desconto = COALESCE(desconto, 0) + ? WHERE id = ?", [$dif, $t['id']]);
    if ($dif < 0) fin_q($pdo, "UPDATE $tabela SET taxa = COALESCE(taxa, 0) + ? WHERE id = ?", [-$dif, $t['id']]);
}

// ---------------------------------------------------------------------
// Diagnóstico
// ---------------------------------------------------------------------

function san_diagnostico($pdo)
{
    $d = [];
    $d['sem_registro'] = fin_q($pdo, "SELECT 'receber' tipo, COUNT(*) n, COALESCE(SUM(COALESCE(subtotal, valor)), 0) v FROM receber r
        WHERE pago IN ('Sim', 'Parcial') AND COALESCE(residuo, '') <> 'Sim' AND NOT EXISTS (SELECT 1 FROM receber_pagamentos p WHERE p.id_receber = r.id)
        UNION ALL SELECT 'pagar', COUNT(*), COALESCE(SUM(COALESCE(subtotal, valor)), 0) FROM pagar r
        WHERE pago IN ('Sim', 'Parcial') AND NOT EXISTS (SELECT 1 FROM pagar_pagamentos p WHERE p.id_pagar = r.id)")->fetchAll(PDO::FETCH_ASSOC);
    $d['residuos'] = fin_q($pdo, "SELECT COUNT(*) n, COALESCE(SUM(valor), 0) v FROM receber WHERE residuo = 'Sim'")->fetch(PDO::FETCH_ASSOC);
    $fora = fin_recebimentos_fora_do_extrato($pdo);
    $d['fora_extrato'] = ['n' => count($fora), 'v' => array_sum(array_column($fora, 'valor'))];
    $d['sem_conta'] = san_pagamentos_sem_conta($pdo);
    $d['dobro'] = san_pagamentos_em_dobro($pdo);
    $d['status'] = san_status_divergente($pdo);
    $d['romaneios'] = san_romaneios_divergentes($pdo);
    $d['linhas_soltas'] = san_linhas_soltas($pdo);
    return $d;
}

// Recebimentos/pagamentos registrados sem conta bancária e que não usaram adiantamento
function san_pagamentos_sem_conta($pdo)
{
    $lista = [];
    foreach (['receber' => ['receber_pagamentos', 'id_receber', 'receber_pgto'], 'pagar' => ['pagar_pagamentos', 'id_pagar', 'pagar_pgto']] as $tipo => [$tab, $fk, $origem]) {
        $extra = $tipo === 'pagar' ? ' AND p.orfao = 0' : '';
        foreach (fin_q($pdo, "SELECT p.*, t.descricao, t.id id_titulo, f.nome nome_forma, f.banco_padrao FROM $tab p JOIN $tipo t ON t.id = p.$fk
            LEFT JOIN formas_pgto f ON f.id = p.forma_pgto
            WHERE (p.banco IS NULL OR p.banco = '' OR p.banco = 0) AND p.id_adiantamento IS NULL AND p.valor > 0 $extra
              AND NOT EXISTS (SELECT 1 FROM linha_bancos l WHERE l.origem = '$origem' AND l.id_origem = p.id)
            ORDER BY p.data_pgto")->fetchAll(PDO::FETCH_ASSOC) as $r) {
            $lista[] = $r + ['tipo' => $tipo];
        }
    }
    return $lista;
}

// Pagamentos de contas a pagar cujo título foi apagado pelo erro antigo do romaneio de compra,
// e o título recriado foi pago de novo com o mesmo valor: o extrato tem as duas saídas.
function san_pagamentos_em_dobro($pdo)
{
    $orfaos = fin_q($pdo, "SELECT pp.*, l.id id_linha, l.descricao desc_linha, l.conciliado FROM pagar_pagamentos pp
        LEFT JOIN linha_bancos l ON l.origem = 'pagar_pgto' AND l.id_origem = pp.id
        WHERE pp.orfao = 1 AND COALESCE(pp.numero_operacao, '') NOT LIKE '%[revisado]%' ORDER BY pp.data_pgto")->fetchAll(PDO::FETCH_ASSOC);
    $lista = [];
    foreach ($orfaos as $o) {
        $atual = null;
        if ($o['desc_linha']) {
            $atual = fin_q($pdo, "SELECT p.id, p.descricao, p.pago, x.id id_pgto, x.valor, x.data_pgto FROM pagar p
                JOIN pagar_pagamentos x ON x.id_pagar = p.id AND x.valor = ?
                WHERE p.descricao = ? ORDER BY x.id LIMIT 1", [$o['valor'], $o['desc_linha']])->fetch(PDO::FETCH_ASSOC) ?: null;
        }
        $lista[] = $o + ['atual' => $atual];
    }
    return $lista;
}

// Títulos com pagamentos cujo status gravado não confere com a soma paga
function san_status_divergente($pdo)
{
    $lista = [];
    foreach (['receber' => ['receber_pagamentos', 'id_receber'], 'pagar' => ['pagar_pagamentos', 'id_pagar']] as $t => [$tab, $fk]) {
        foreach (fin_q($pdo, "SELECT t.id, t.descricao, t.pago, t.valor, s.pago_total,
                ROUND(t.valor + COALESCE(t.multa,0) + COALESCE(t.juros,0) + COALESCE(t.taxa,0) - COALESCE(t.desconto,0), 2) devido
            FROM $t t JOIN (SELECT $fk id, SUM(valor) pago_total FROM $tab GROUP BY $fk) s ON s.id = t.id")->fetchAll(PDO::FETCH_ASSOC) as $r) {
            $correto = $r['pago_total'] <= 0 ? 'Não' : ($r['pago_total'] >= $r['devido'] - 0.009 ? 'Sim' : 'Parcial');
            if ($correto !== (string) $r['pago']) $lista[] = $r + ['tipo' => $t, 'correto' => $correto];
        }
    }
    return $lista;
}

// Romaneios cujo total difere do que está no financeiro (só lista; quem decide é o usuário)
function san_romaneios_divergentes($pdo)
{
    $v = fin_q($pdo, "SELECT 'venda' tipo, r.id, r.total_liquido total, COALESCE(SUM(t.valor), 0) financeiro, COUNT(t.id) titulos
        FROM romaneio_venda r LEFT JOIN receber t ON t.id_romaneio = r.id AND t.referencia = 'Romaneio Venda' AND COALESCE(t.residuo, '') <> 'Sim'
        GROUP BY r.id HAVING ABS(total - financeiro) > 0.01")->fetchAll(PDO::FETCH_ASSOC);
    $c = fin_q($pdo, "SELECT 'compra' tipo, r.id, r.total_liquido total, COALESCE(SUM(t.valor), 0) financeiro, COUNT(t.id) titulos
        FROM romaneio_compra r LEFT JOIN pagar t ON t.id_ref = r.id AND t.referencia = 'romaneio_compra'
        GROUP BY r.id HAVING ABS(total - financeiro) > 0.01 AND NOT (total <= 0 AND titulos = 0)")->fetchAll(PDO::FETCH_ASSOC);
    return array_merge($v, $c);
}

// Linhas antigas do extrato que não correspondem a nenhum título nem estorno (só depois das correções automáticas)
function san_linhas_soltas($pdo)
{
    return fin_q($pdo, "SELECT l.*, b.banco nome_banco,
        (SELECT COUNT(*) FROM linha_bancos x WHERE x.id_banco = l.id_banco AND DATE(x.data) = DATE(l.data) AND x.credito = l.credito AND x.debito = l.debito AND x.id <> l.id) iguais
        FROM linha_bancos l JOIN bancos b ON b.id = l.id_banco WHERE l.origem IS NULL ORDER BY l.data, l.id")->fetchAll(PDO::FETCH_ASSOC);
}

// Linha solta conferida e legítima: passa a ser um lançamento avulso e sai da lista
function san_manter_linha($pdo, $id)
{
    fin_q($pdo, "UPDATE linha_bancos SET origem = 'manual' WHERE id = ? AND origem IS NULL", [$id]);
    fin_log($pdo, 'saneamento_linha_mantida', 'linha_bancos', $id, 'Confirmou linha antiga do extrato como lançamento avulso');
}

// ---------------------------------------------------------------------
// Correções automáticas
// ---------------------------------------------------------------------

// 1) Títulos marcados como pagos sem registro de pagamento: cria o registro e liga à linha
//    do extrato que a versão antiga lançou (mesma data e valor). Sem linha: fica "sem conta".
function san_registrar_pagamentos_antigos($pdo)
{
    $r = ['titulos' => 0, 'vinculados' => 0, 'sem_conta' => 0];
    foreach (['receber' => ['receber_pagamentos', 'id_receber', 'receber_pgto', 'credito'], 'pagar' => ['pagar_pagamentos', 'id_pagar', 'pagar_pgto', 'debito']] as $tipo => [$tab, $fk, $origem, $lado]) {
        $extra = $tipo === 'receber' ? " AND COALESCE(residuo, '') <> 'Sim'" : '';
        $titulos = fin_q($pdo, "SELECT * FROM $tipo t WHERE pago IN ('Sim', 'Parcial') $extra AND NOT EXISTS (SELECT 1 FROM $tab p WHERE p.$fk = t.id)")->fetchAll(PDO::FETCH_ASSOC);
        foreach ($titulos as $t) {
            $valor = $t['pago'] === 'Parcial'
                ? round((float) $t['subtotal'] - (float) $t['valor_restante'], 2)
                : round((float) ($t['subtotal'] ?: $t['valor']), 2);
            if ($valor <= 0 || !$t['data_pgto']) continue;
            $linha = san_linha_legado($pdo, $lado, $valor, $t['data_pgto']);
            fin_q($pdo, "INSERT INTO $tab SET $fk = ?, valor = ?, data_pgto = ?, forma_pgto = ?, banco = ?, numero_operacao = ?", [
                $t['id'], $valor, $t['data_pgto'], $t['forma_pgto'] ?: null, $linha ? $linha['id_banco'] : null,
                $linha ? (string) $linha['descricao'] : '',
            ]);
            $id_pg = (int) $pdo->lastInsertId();
            if ($linha) {
                san_vincular_linha($pdo, $linha['id'], $origem, $id_pg);
                $r['vinculados']++;
            } else {
                $r['sem_conta']++;
            }
            if ($t['pago'] === 'Sim') san_reconciliar_ajustes($pdo, $tipo, $t, $valor);
            $r['titulos']++;
        }
    }
    return $r;
}

// 2) Resíduos (modelo antigo de baixa parcial): viram pagamentos do título original,
//    que volta a ter o valor cheio, e as linhas do extrato ficam ligadas a esses pagamentos.
function san_converter_residuos($pdo)
{
    $r = ['residuos' => 0, 'vinculados' => 0, 'sem_conta' => 0];
    $residuos = fin_q($pdo, "SELECT * FROM receber WHERE residuo = 'Sim' ORDER BY id")->fetchAll(PDO::FETCH_ASSOC);
    foreach ($residuos as $res) {
        $pai = fin_q($pdo, "SELECT * FROM receber WHERE id = ?", [$res['id_ref']])->fetch(PDO::FETCH_ASSOC);
        if (!$pai) continue;
        $recebido = round((float) ($res['subtotal'] ?: $res['valor']), 2);
        $principal = round($recebido - (float) $res['multa'] - (float) $res['juros'] - (float) $res['taxa'] + (float) $res['desconto'], 2);

        // Pagamentos já registrados no próprio resíduo passam para o título original
        $pg_res = fin_q($pdo, "SELECT * FROM receber_pagamentos WHERE id_receber = ?", [$res['id']])->fetchAll(PDO::FETCH_ASSOC);
        if ($pg_res) {
            fin_q($pdo, "UPDATE receber_pagamentos SET id_receber = ? WHERE id_receber = ?", [$pai['id'], $res['id']]);
        } elseif ($recebido > 0 && $res['data_pgto']) {
            $linha = san_linha_legado($pdo, 'credito', $recebido, $res['data_pgto']);
            fin_q($pdo, "INSERT INTO receber_pagamentos SET id_receber = ?, valor = ?, data_pgto = ?, forma_pgto = ?, banco = ?, numero_operacao = ?", [
                $pai['id'], $recebido, $res['data_pgto'], $res['forma_pgto'] ?: null, $linha ? $linha['id_banco'] : null, $linha ? (string) $linha['descricao'] : '',
            ]);
            $id_pg = (int) $pdo->lastInsertId();
            if ($linha) { san_vincular_linha($pdo, $linha['id'], 'receber_pgto', $id_pg); $r['vinculados']++; } else { $r['sem_conta']++; }
        }
        fin_q($pdo, "UPDATE receber SET valor = valor + ?, multa = COALESCE(multa,0) + ?, juros = COALESCE(juros,0) + ?, taxa = COALESCE(taxa,0) + ?,
            desconto = COALESCE(desconto,0) + ? WHERE id = ?", [$principal, (float) $res['multa'], (float) $res['juros'], (float) $res['taxa'], (float) $res['desconto'], $pai['id']]);
        fin_q($pdo, "DELETE FROM receber WHERE id = ?", [$res['id']]);
        fin_log($pdo, 'saneamento_residuo', 'receber', $pai['id'], 'Resíduo #' . $res['id'] . ' (' . fin_moeda($recebido) . ') virou pagamento do título #' . $pai['id'], $res);
        $r['residuos']++;
    }
    return $r;
}

// 3) Recebimentos com conta informada que nunca entraram no extrato. Se já houver uma linha
//    antiga idêntica (mesma conta, data e valor) ela é ligada em vez de lançar de novo.
function san_lancar_recebimentos($pdo)
{
    $r = ['lancados' => 0, 'vinculados' => 0, 'total' => 0];
    $bloqueio = fin_data_bloqueio($pdo);
    foreach (fin_recebimentos_fora_do_extrato($pdo) as $rp) {
        if (!fin_data_valida((string) $rp['data_pgto']) || ($bloqueio && $rp['data_pgto'] <= $bloqueio)) continue;
        $linha = san_linha_legado($pdo, 'credito', $rp['valor'], $rp['data_pgto'], $rp['banco']);
        if ($linha) {
            san_vincular_linha($pdo, $linha['id'], 'receber_pgto', $rp['id']);
            $r['vinculados']++;
            continue;
        }
        $nome = fin_q($pdo, "SELECT nome FROM clientes WHERE id = ?", [(int) $rp['cliente']])->fetchColumn();
        fin_lancar($pdo, ['id_banco' => $rp['banco'], 'data' => $rp['data_pgto'], 'descricao' => 'Receb. ' . $rp['descricao'] . ($nome ? " - $nome" : ''),
            'credito' => $rp['valor'], 'origem' => 'receber_pgto', 'id_origem' => $rp['id'], 'documento' => $rp['numero_operacao'],
            'categoria_receber' => $rp['categoria_receber'], 'centro_custo' => $rp['centro_custo']]);
        $r['lancados']++;
        $r['total'] += $rp['valor'];
    }
    return $r;
}

// 4) Status gravado diferente do que os pagamentos dizem
function san_corrigir_status($pdo)
{
    $n = 0;
    foreach (san_status_divergente($pdo) as $s) {
        fin_recalcular_titulo($pdo, $s['tipo'], $s['id']);
        $n++;
    }
    return $n;
}

// Executa as correções automáticas na ordem certa, numa transação só
function san_executar_automatico($pdo)
{
    return fin_transacao($pdo, function () use ($pdo) {
        $r = [];
        $r['antigos'] = san_registrar_pagamentos_antigos($pdo);
        $r['residuos'] = san_converter_residuos($pdo);
        $r['recebimentos'] = san_lancar_recebimentos($pdo);
        $r['status'] = san_corrigir_status($pdo);
        $vendas = fin_q($pdo, "SELECT DISTINCT id_venda_real FROM receber WHERE id_venda_real > 0")->fetchAll(PDO::FETCH_COLUMN);
        if (function_exists('venda_recalcular_status')) foreach ($vendas as $v) venda_recalcular_status($pdo, $v);
        foreach (fin_q($pdo, "SELECT id FROM bancos")->fetchAll(PDO::FETCH_COLUMN) as $b) fin_recalcular_saldo($pdo, $b);
        fin_log($pdo, 'saneamento', 'financeiro', null, 'Saneamento automático do histórico executado', $r);
        fin_q($pdo, "REPLACE INTO fin_meta (chave, valor) VALUES ('saneamento_executado', ?)", [date('Y-m-d H:i:s')]);
        return $r;
    });
}

// ---------------------------------------------------------------------
// Correções com decisão do usuário
// ---------------------------------------------------------------------

// Lança no extrato um pagamento antigo que não tinha conta, na conta escolhida
function san_definir_conta($pdo, $tipo, $id_pagamento, $id_banco)
{
    $c = fin_cfg($tipo);
    return fin_transacao($pdo, function () use ($pdo, $tipo, $c, $id_pagamento, $id_banco) {
        $pg = fin_q($pdo, "SELECT p.*, t.descricao FROM {$c['tab_pg']} p JOIN {$c['tabela']} t ON t.id = p.{$c['fk']} WHERE p.id = ?", [$id_pagamento])->fetch(PDO::FETCH_ASSOC);
        if (!$pg) throw new FinErro('Pagamento não encontrado.');
        if (!fin_banco_existe($pdo, $id_banco)) throw new FinErro('Escolha a conta bancária.');
        if (fin_q($pdo, "SELECT COUNT(*) FROM linha_bancos WHERE origem = ? AND id_origem = ?", [$c['origem'], $id_pagamento])->fetchColumn()) {
            throw new FinErro('Este pagamento já está no extrato.');
        }
        fin_q($pdo, "UPDATE {$c['tab_pg']} SET banco = ? WHERE id = ?", [$id_banco, $id_pagamento]);
        $lado = $tipo === 'receber' ? 'credito' : 'debito';
        $linha = san_linha_legado($pdo, $lado, $pg['valor'], $pg['data_pgto'], $id_banco);
        if ($linha) {
            san_vincular_linha($pdo, $linha['id'], $c['origem'], $id_pagamento);
        } else {
            fin_lancar($pdo, ['id_banco' => $id_banco, 'data' => $pg['data_pgto'], 'descricao' => $c['verbo'] . ' ' . $pg['descricao'],
                $lado => $pg['valor'], 'origem' => $c['origem'], 'id_origem' => $id_pagamento, 'documento' => $pg['numero_operacao']]);
        }
        fin_log($pdo, 'saneamento_conta', $c['tab_pg'], $id_pagamento, 'Definiu a conta ' . fin_nome_banco($pdo, $id_banco) . ' para o pagamento de "' . $pg['descricao'] . '" ' . fin_moeda($pg['valor']));
    });
}

// Pagamento em dobro confirmado: remove o registro órfão e a saída dele no extrato
function san_remover_dobro($pdo, $id_pagamento)
{
    return fin_transacao($pdo, function () use ($pdo, $id_pagamento) {
        $pg = fin_q($pdo, "SELECT * FROM pagar_pagamentos WHERE id = ? AND orfao = 1", [$id_pagamento])->fetch(PDO::FETCH_ASSOC);
        if (!$pg) throw new FinErro('Pagamento órfão não encontrado.');
        fin_remover_lancamentos($pdo, 'pagar_pgto', $id_pagamento);
        fin_q($pdo, "DELETE FROM pagar_pagamentos WHERE id = ?", [$id_pagamento]);
        fin_log($pdo, 'saneamento_dobro', 'pagar_pagamentos', $id_pagamento, 'Removeu pagamento em dobro de ' . fin_moeda($pg['valor']) . ' de ' . fin_data_br($pg['data_pgto']) . ' (título já apagado)', $pg);
    });
}

// Pagamento em dobro que de fato aconteceu: mantém no extrato e some da lista
function san_manter_dobro($pdo, $id_pagamento)
{
    fin_q($pdo, "UPDATE pagar_pagamentos SET numero_operacao = CONCAT(COALESCE(numero_operacao, ''), ' [revisado]') WHERE id = ? AND orfao = 1", [$id_pagamento]);
    fin_log($pdo, 'saneamento_dobro_mantido', 'pagar_pagamentos', $id_pagamento, 'Confirmou que o pagamento órfão realmente saiu do banco');
}
