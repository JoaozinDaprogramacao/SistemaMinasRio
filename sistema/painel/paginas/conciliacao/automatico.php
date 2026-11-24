<?php
require_once("../../../conexao.php");
require_once(__DIR__ . "/../../seguranca.php");
seguranca_exigir_acesso(['conciliacao']);
require_once(__DIR__ . "/../../funcoes/financeiro.php");

fin_responder(function () use ($pdo) {
	$n = fin_transacao($pdo, fn() => fin_conciliar_automatico($pdo, (int) ($_POST['banco'] ?? 0)));
	if ($n) fin_log($pdo, 'conciliar_automatico', 'extrato_importado', (int) $_POST['banco'], "Conciliação automática: $n lançamentos");
	return $n ? "$n lançamento(s) conciliado(s) automaticamente." : 'Nenhum par novo encontrado. Vincule ou lance manualmente os que sobraram.';
});
