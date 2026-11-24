<?php
require_once("../../../conexao.php");
require_once(__DIR__ . "/../../seguranca.php");
seguranca_exigir_acesso(['conciliacao']);
require_once(__DIR__ . "/../../funcoes/financeiro.php");

fin_responder(function () use ($pdo) {
	$id = (int) ($_POST['extrato'] ?? 0);
	$e = fin_q($pdo, "SELECT * FROM extrato_importado WHERE id = ? AND status = 'pendente'", [$id])->fetch(PDO::FETCH_ASSOC);
	if (!$e) return 'Lançamento não encontrado ou já tratado.';
	fin_q($pdo, "UPDATE extrato_importado SET status = 'ignorado' WHERE id = ?", [$id]);
	fin_log($pdo, 'ignorar_extrato', 'extrato_importado', $id, 'Ignorou lançamento do banco "' . $e['descricao'] . '" ' . fin_moeda($e['valor']) . ' em ' . fin_data_br($e['data']));
	return 'Salvo com Sucesso';
});
