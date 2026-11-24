<?php
require_once("../../../conexao.php");
require_once(__DIR__ . "/../../seguranca.php");
seguranca_exigir_acesso(['conciliacao']);
require_once(__DIR__ . "/../../funcoes/financeiro.php");

// Volta um lançamento importado para "pendente" e desmarca a linha do sistema a que estava ligado
fin_responder(function () use ($pdo) {
	$id = (int) ($_POST['extrato'] ?? 0);
	return fin_transacao($pdo, function () use ($pdo, $id) {
		$e = fin_q($pdo, "SELECT * FROM extrato_importado WHERE id = ?", [$id])->fetch(PDO::FETCH_ASSOC);
		if (!$e) return 'Lançamento não encontrado.';
		if ($e['id_linha_banco']) fin_marcar_conciliado($pdo, $e['id_linha_banco'], false);
		fin_q($pdo, "UPDATE extrato_importado SET status = 'pendente', id_linha_banco = NULL WHERE id = ?", [$id]);
		fin_log($pdo, 'desfazer_conciliacao', 'extrato_importado', $id, 'Desfez a conciliação de "' . $e['descricao'] . '" ' . fin_moeda($e['valor']));
		return 'Salvo com Sucesso';
	});
});
