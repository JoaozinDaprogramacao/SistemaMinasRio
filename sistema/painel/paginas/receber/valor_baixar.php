<?php
require_once("../../../conexao.php");
require_once(__DIR__ . "/../../seguranca.php");
seguranca_exigir_acesso(['receber', 'clientes']);
require_once(__DIR__ . "/../../funcoes/financeiro.php");

// Soma do que falta quitar nos títulos selecionados
$ids = array_unique(array_filter(array_map('intval', explode('-', (string) ($_POST['ids'] ?? '')))));
$total = 0;
$qtd = 0;
foreach ($ids as $id) {
	$t = fin_q($pdo, "SELECT * FROM receber WHERE id = ?", [$id])->fetch(PDO::FETCH_ASSOC);
	if (!$t || $t['pago'] === 'Sim') continue;
	$total += $t['pago'] === 'Parcial' ? (float) $t['valor_restante'] : (float) $t['valor'];
	$qtd++;
}
fin_json(['total' => number_format($total, 2, ',', '.'), 'qtd' => $qtd]);
