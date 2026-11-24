<?php
require_once("../../../conexao.php");
require_once(__DIR__ . "/../../seguranca.php");
require_once(__DIR__ . "/../../funcoes/financeiro.php");
require_once(__DIR__ . "/config.php");

fin_responder(function () use ($pdo) {
	$c = cadfin_config($_POST['e'] ?? '');
	seguranca_exigir_acesso([$c['permissao']]);
	$id = (int) ($_POST['id'] ?? 0);
	$item = fin_q($pdo, "SELECT * FROM {$c['tabela']} WHERE id = ?", [$id])->fetch(PDO::FETCH_ASSOC);
	if (!$item) return 'Cadastro não encontrado.';
	if (!empty($item['protegida'])) return 'Esta categoria é usada pelo sistema e não pode ser excluída.';
	return fin_transacao($pdo, function () use ($pdo, $c, $id, $item) {
		// Lançamentos que usavam o cadastro ficam "sem categoria / sem centro de custo"
		foreach ($c['uso'] as [$t, $col]) fin_q($pdo, "UPDATE $t SET $col = NULL WHERE $col = ?", [$id]);
		fin_q($pdo, "DELETE FROM {$c['tabela']} WHERE id = ?", [$id]);
		fin_log($pdo, 'cadastro_excluir', $c['tabela'], $id, 'Excluiu "' . $item['nome'] . '" de ' . $c['titulo']);
		return 'Excluído com Sucesso';
	});
});
