<?php
require_once("../../../conexao.php");
require_once(__DIR__ . "/../../seguranca.php");
seguranca_exigir_acesso(['contas_bancarias']);
require_once(__DIR__ . "/../../funcoes/financeiro.php");
require_once(__DIR__ . "/consulta.php");

$r = extrato_consulta($pdo, $_GET);
header('Content-Type: text/csv; charset=utf-8');
header('Content-Disposition: attachment; filename="extrato_' . $r['ini'] . '_' . $r['fim'] . '.csv"');
$out = fopen('php://output', 'w');
fwrite($out, "\xEF\xBB\xBF"); // BOM para o Excel abrir com acentos
$br = fn($v) => number_format((float) $v, 2, ',', '');
fputcsv($out, ['Data', 'Conta', 'Descrição', 'Documento', 'Origem', 'Categoria', 'Centro de custo', 'Entrada', 'Saída', 'Saldo', 'Conciliado'], ';');
$saldo = $r['anterior'];
if ($saldo !== null) fputcsv($out, [fin_data_br($r['ini']), '', 'Saldo anterior', '', '', '', '', '', '', $br($saldo), ''], ';');
foreach ($r['linhas'] as $l) {
    if ($saldo !== null) $saldo += $l['credito'] - $l['debito'];
    fputcsv($out, [
        fin_data_br($l['data']), $l['nome_banco'], fin_descricao_linha($pdo, $l['descricao']), $l['documento'] ?: $l['n_fiscal'],
        extrato_origem_rotulo($l)[0], $l['cat_rec'] ?: $l['cat_pag'], $l['centro'],
        $l['credito'] > 0 ? $br($l['credito']) : '', $l['debito'] > 0 ? $br($l['debito']) : '',
        $saldo !== null ? $br($saldo) : '', $l['conciliado'] ? 'Sim' : 'Não',
    ], ';');
}
fclose($out);
