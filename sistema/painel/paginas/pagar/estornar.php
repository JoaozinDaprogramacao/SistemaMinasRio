<?php
require_once("../../../conexao.php");
require_once(__DIR__ . "/../../seguranca.php");
seguranca_exigir_acesso(['pagar']);
require_once(__DIR__ . "/../vendas/funcoes_venda.php");
require_once(__DIR__ . "/../../funcoes/financeiro.php");

// Desfaz a baixa: tira os pagamentos do extrato e volta o título para "em aberto"
fin_responder(function () use ($pdo) {
	if (!fin_pode_estornar()) return 'Você não tem permissão para estornar baixas.';
	$motivo = trim($_POST['motivo'] ?? '');
	if ($motivo === '') return 'Informe o motivo do estorno.';
	fin_estornar_baixa($pdo, 'pagar', (int) ($_POST['id'] ?? 0), $motivo);
	return 'Estornado com Sucesso';
});
