<?php
require_once("../../../conexao.php");
require_once(__DIR__ . "/../../seguranca.php");
seguranca_exigir_acesso(['receber', 'clientes']);
require_once(__DIR__ . "/../vendas/funcoes_venda.php");
require_once(__DIR__ . "/../../funcoes/financeiro.php");

fin_responder(function () use ($pdo) {
	$id = (int) ($_POST['id-baixar'] ?? 0);
	$obs = trim($_POST['obs-baixar'] ?? '') ?: 'Baixa de Título';
	$ajustes = [
		'multa' => $_POST['valor-multa'] ?? 0,
		'juros' => $_POST['valor-juros'] ?? 0,
		'acrescimo' => $_POST['valor-acrescimo'] ?? 0,
		'desconto' => $_POST['valor-desconto'] ?? 0,
	];
	$pagamentos = fin_pagamentos_do_post();
	$tem_valor = array_filter($pagamentos, fn($p) => fin_num($p['valor']) > 0);
	if (!$tem_valor) {
		$t = fin_titulo($pdo, 'receber', $id);
		if ($t['pago'] === 'Não') return 'Informe ao menos um recebimento!';
		if (!fin_pode_estornar()) return 'Você não tem permissão para estornar baixas.';
	}

	fin_transacao($pdo, function () use ($pdo, $id, $pagamentos, $ajustes, $obs) {
		fin_baixar($pdo, 'receber', $id, $pagamentos, $ajustes, $obs);
		// Comprovante anexado na baixa vira o arquivo do título
		if (!empty($_FILES['comprovante']['name'])) {
			$t = fin_titulo($pdo, 'receber', $id);
			fin_q($pdo, "UPDATE receber SET arquivo = ? WHERE id = ?", [fin_upload_anexo('comprovante', $t['arquivo']), $id]);
		}
	});
	return 'Baixado com Sucesso';
});
