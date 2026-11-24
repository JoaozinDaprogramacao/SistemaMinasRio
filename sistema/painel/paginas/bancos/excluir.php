<?php
require_once("../../../conexao.php");
require_once(__DIR__ . "/../../seguranca.php");
seguranca_exigir_acesso(['contas_bancarias']);
require_once(__DIR__ . "/../../funcoes/financeiro.php");

fin_responder(function () use ($pdo) {
	$id = (int) ($_POST['id'] ?? 0);
	$movs = fin_q($pdo, "SELECT COUNT(*) FROM linha_bancos WHERE id_banco = ? AND COALESCE(origem, '') <> 'saldo_inicial'", [$id])->fetchColumn();
	$pgtos = fin_q($pdo, "SELECT (SELECT COUNT(*) FROM receber_pagamentos WHERE banco = ?) + (SELECT COUNT(*) FROM pagar_pagamentos WHERE banco = ?)", [$id, $id])->fetchColumn();
	if ($movs || $pgtos) return 'Esta conta tem movimentações e não pode ser excluída.';
	return fin_transacao($pdo, function () use ($pdo, $id) {
		fin_q($pdo, "DELETE FROM linha_bancos WHERE id_banco = ?", [$id]);
		fin_q($pdo, "UPDATE formas_pgto SET banco_padrao = NULL WHERE banco_padrao = ?", [$id]);
		fin_q($pdo, "DELETE FROM bancos WHERE id = ?", [$id]);
		fin_log($pdo, 'excluir_conta', 'bancos', $id, 'Excluiu conta bancária sem movimento');
		return 'Excluído com Sucesso';
	});
});
