<?php
require_once("../../../conexao.php");
require_once(__DIR__ . "/../../seguranca.php");
seguranca_exigir_acesso(['fluxo_caixa']);
require_once(__DIR__ . "/../../funcoes/financeiro.php");

// Fluxo de caixa: realizado (extrato) até hoje + previsto (títulos em aberto) a partir de hoje
$banco = (int) ($_POST['banco'] ?? 0);
$hoje = date('Y-m-d');
$ini = fin_data_valida($_POST['ini'] ?? '') ? $_POST['ini'] : $hoje;
$fim = fin_data_valida($_POST['fim'] ?? '') ? $_POST['fim'] : date('Y-m-d', strtotime('+90 days'));
if ($fim < $ini) $fim = $ini;
$grupo = in_array($_POST['grupo'] ?? '', ['dia', 'semana', 'mes'], true) ? $_POST['grupo'] : 'semana';
$incluir_atraso = ($_POST['atraso'] ?? '1') === '1';

$chave = function ($data) use ($grupo) {
    if ($grupo === 'dia') return $data;
    if ($grupo === 'mes') return substr($data, 0, 7) . '-01';
    return date('Y-m-d', strtotime($data . ' -' . (date('N', strtotime($data)) - 1) . ' days')); // segunda-feira
};
$rotulo = function ($k) use ($grupo) {
    if ($grupo === 'dia') return fin_data_br($k);
    if ($grupo === 'mes') return ['Jan', 'Fev', 'Mar', 'Abr', 'Mai', 'Jun', 'Jul', 'Ago', 'Set', 'Out', 'Nov', 'Dez'][(int) substr($k, 5, 2) - 1] . '/' . substr($k, 0, 4);
    return 'Sem. ' . date('d/m', strtotime($k));
};

$linhas = [];
$add = function ($k, $campo, $v) use (&$linhas) {
    if (!isset($linhas[$k])) $linhas[$k] = ['real_ent' => 0, 'real_sai' => 0, 'prev_ent' => 0, 'prev_sai' => 0];
    $linhas[$k][$campo] += $v;
};
// Garante todos os períodos, mesmo vazios
for ($d = $ini; $d <= $fim; $d = date('Y-m-d', strtotime($d . ' +1 day'))) $add($chave($d), 'real_ent', 0);

// Realizado: extrato (sem transferências quando são todas as contas — elas se anulam)
$sql = "SELECT DATE(data) d, SUM(credito) c, SUM(debito) db FROM linha_bancos WHERE DATE(data) BETWEEN ? AND ? AND DATE(data) <= ?";
$p = [$ini, $fim, $hoje];
if ($banco) { $sql .= " AND id_banco = ?"; $p[] = $banco; } else { $sql .= " AND COALESCE(origem, '') <> 'transferencia'"; }
foreach (fin_q($pdo, "$sql GROUP BY DATE(data)", $p)->fetchAll(PDO::FETCH_ASSOC) as $r) {
    $add($chave($r['d']), 'real_ent', (float) $r['c']);
    $add($chave($r['d']), 'real_sai', (float) $r['db']);
}

// Previsto: títulos em aberto com vencimento a partir de hoje (o que falta receber/pagar)
$aberto = "COALESCE(NULLIF(pago, ''), 'Não') IN ('Não', 'Parcial') AND COALESCE(cancelada, '') <> 'Sim'";
$falta = "CASE WHEN pago = 'Parcial' THEN COALESCE(valor_restante, valor) ELSE valor END";
$ini_prev = max($ini, $hoje);
foreach (['receber' => 'prev_ent', 'pagar' => 'prev_sai'] as $tab => $campo) {
    $canc = $tab === 'pagar' ? str_replace(" AND COALESCE(cancelada, '') <> 'Sim'", '', $aberto) : $aberto;
    foreach (fin_q($pdo, "SELECT vencimento d, SUM($falta) v FROM $tab WHERE $canc AND vencimento BETWEEN ? AND ? GROUP BY vencimento", [$ini_prev, $fim])->fetchAll(PDO::FETCH_ASSOC) as $r) {
        $add($chave($r['d']), $campo, (float) $r['v']);
    }
}

// Em atraso (vencidos e não pagos): entram no primeiro período a partir de hoje, se marcado
$atraso_rec = (float) fin_q($pdo, "SELECT COALESCE(SUM($falta), 0) FROM receber WHERE $aberto AND vencimento < ?", [$hoje])->fetchColumn();
$atraso_pag = (float) fin_q($pdo, "SELECT COALESCE(SUM($falta), 0) FROM pagar WHERE " . str_replace(" AND COALESCE(cancelada, '') <> 'Sim'", '', $aberto) . " AND vencimento < ?", [$hoje])->fetchColumn();
if ($incluir_atraso && $hoje >= $ini && $hoje <= $fim) {
    $add($chave($hoje), 'prev_ent', $atraso_rec);
    $add($chave($hoje), 'prev_sai', $atraso_pag);
}

ksort($linhas);
$saldo_ini = fin_saldo($pdo, $banco ?: null, date('Y-m-d', strtotime($ini . ' -1 day')));
$saldo = $saldo_ini;
$saida = [];
$menor = null;
foreach ($linhas as $k => $l) {
    $saldo += $l['real_ent'] - $l['real_sai'] + $l['prev_ent'] - $l['prev_sai'];
    $saldo = round($saldo, 2);
    if ($menor === null || $saldo < $menor['saldo']) $menor = ['saldo' => $saldo, 'rotulo' => $rotulo($k)];
    $saida[] = ['rotulo' => $rotulo($k), 'chave' => $k, 'futuro' => $k >= $chave($hoje)] + array_map(fn($v) => round($v, 2), $l) + ['saldo' => $saldo];
}
fin_json([
    'saldo_inicial' => $saldo_ini,
    'saldo_final' => $saldo,
    'menor' => $menor,
    'atraso_rec' => $atraso_rec,
    'atraso_pag' => $atraso_pag,
    'totais' => [
        'real_ent' => array_sum(array_column($saida, 'real_ent')), 'real_sai' => array_sum(array_column($saida, 'real_sai')),
        'prev_ent' => array_sum(array_column($saida, 'prev_ent')), 'prev_sai' => array_sum(array_column($saida, 'prev_sai')),
    ],
    'linhas' => $saida,
]);
