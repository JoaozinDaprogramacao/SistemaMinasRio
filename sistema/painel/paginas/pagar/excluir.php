<?php
require_once("../../../conexao.php");
require_once(__DIR__ . "/../../seguranca.php");
seguranca_exigir_acesso(['pagar']);
require_once(__DIR__ . "/../vendas/funcoes_venda.php");
require_once(__DIR__ . "/../../funcoes/financeiro.php");

fin_responder(function () use ($pdo) {
	$id = (int) ($_POST['id'] ?? 0);
	$t = fin_titulo($pdo, 'pagar', $id);
	if ((int) $t['id_romaneio'] > 0) return 'Este lançamento veio de um romaneio. Exclua ou edite pelo romaneio.';
	if ((int) ($t['id_venda_real'] ?? 0) > 0) return 'Este lançamento veio de uma venda. Cancele pela Lista de Vendas.';
	if (!empty($t['id_baldeio'])) return 'Este lançamento veio de um baldeio. Exclua pelo baldeio.';
	if (fin_pagamentos($pdo, 'pagar', $id) && !fin_pode_estornar()) return 'Esta conta tem baixa registrada. Só administrador, gerente ou quem tem a permissão "Estornar Baixas" pode excluí-la.';

	fin_excluir_titulo($pdo, 'pagar', $id, trim($_POST['motivo'] ?? ''));
	if ($t['arquivo'] && $t['arquivo'] != 'sem-foto.png') @unlink(__DIR__ . '/../../images/contas/' . basename($t['arquivo']));
	if (!empty($t['hash'])) {
		$hash = $t['hash'];
		require(__DIR__ . "/../../apis/cancelar_agendamento.php");
	}
	return 'Excluído com Sucesso';
});
