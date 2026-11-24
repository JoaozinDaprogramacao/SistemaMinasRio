<?php
require_once("../../../conexao.php");
require_once(__DIR__ . "/../../seguranca.php");
seguranca_exigir_acesso(['receber', 'clientes']);
require_once(__DIR__ . "/../../funcoes/financeiro.php");

$id = (int) ($_POST['id'] ?? 0);
$t = fin_q($pdo, "SELECT * FROM receber WHERE id = ?", [$id])->fetch(PDO::FETCH_ASSOC);
if (!$t) {
	fin_json(['erro' => 'Registro não encontrado']);
	exit();
}
$dados = [
	'multa' => $t['multa'], 'juros' => $t['juros'], 'acrescimo' => $t['taxa'], 'desconto' => $t['desconto'],
	'obs' => $t['obs'], 'pago' => $t['pago'], 'pagamentos' => [], 'adiantamentos' => [],
	'pode_estornar' => fin_pode_estornar(),
];
foreach (fin_pagamentos($pdo, 'receber', $id) as $pg) {
	$dados['pagamentos'][] = [
		'valor' => $pg['valor'], 'data' => $pg['data_pgto'], 'banco' => $pg['banco'], 'operacao' => $pg['numero_operacao'],
		'forma' => $pg['id_adiantamento'] ? 'adi:' . $pg['id_adiantamento'] : $pg['forma_pgto'],
	];
}
// Adiantamentos da pessoa com saldo (e os já usados neste título, para a edição continuar válida)
$pessoa = 'receber' === 'receber' ? (int) $t['cliente'] : (int) $t['fornecedor'];
$tipo_adi = 'receber' === 'receber' ? 'cliente' : 'fornecedor';
if ($pessoa) {
	$usados = array_filter(array_column(fin_pagamentos($pdo, 'receber', $id), 'id_adiantamento'));
	$lista = fin_q($pdo, "SELECT * FROM fin_adiantamentos WHERE tipo = ? AND pessoa = ? AND (saldo > 0" . ($usados ? " OR id IN (" . implode(',', array_map('intval', $usados)) . ")" : '') . ") ORDER BY data", [$tipo_adi, $pessoa])->fetchAll(PDO::FETCH_ASSOC);
	foreach ($lista as $a) {
		$dados['adiantamentos'][] = ['id' => $a['id'], 'rotulo' => 'Adiantamento #' . $a['id'] . ' (' . fin_data_br($a['data']) . ') - saldo ' . fin_moeda($a['saldo'])];
	}
}
fin_json($dados);
