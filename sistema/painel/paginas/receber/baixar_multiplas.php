<?php
require_once("../../../conexao.php");
require_once(__DIR__ . "/../../seguranca.php");
seguranca_exigir_acesso(['receber', 'clientes']);
require_once(__DIR__ . "/../vendas/funcoes_venda.php");
require_once(__DIR__ . "/../../funcoes/financeiro.php");

// Baixa em lote: quita o saldo em aberto de cada título selecionado, tudo ou nada.
fin_responder(function () use ($pdo) {
	$ids = array_unique(array_filter(array_map('intval', explode('-', (string) ($_POST['ids'] ?? '')))));
	$data = $_POST['data_baixar'] ?? date('Y-m-d');
	$forma = (int) ($_POST['forma_baixar'] ?? 0);
	$banco = (int) ($_POST['banco_baixar'] ?? 0);
	$operacao = trim($_POST['numero_operacao_baixar'] ?? '');
	$obs = trim($_POST['obs_baixar'] ?? '');

	if (!$ids) return 'Nenhum título selecionado!';
	if (!$forma) return 'Selecione a forma de pagamento!';
	if (!$banco) return 'Selecione a conta bancária para realizar a baixa em massa!';

	$n = fin_transacao($pdo, function () use ($pdo, $ids, $data, $forma, $banco, $operacao, $obs) {
		$n = 0;
		foreach ($ids as $id) {
			$t = fin_titulo($pdo, 'receber', $id);
			if ($t['pago'] === 'Sim' || ($t['cancelada'] ?? '') === 'Sim') continue;
			$pagamentos = [];
			foreach (fin_pagamentos($pdo, 'receber', $id) as $pg) {
				$pagamentos[] = ['valor' => $pg['valor'], 'data' => $pg['data_pgto'], 'forma' => $pg['forma_pgto'], 'banco' => $pg['banco'],
					'operacao' => $pg['numero_operacao'], 'id_adiantamento' => $pg['id_adiantamento']];
			}
			$subtotal = round($t['valor'] + (float) $t['multa'] + (float) $t['juros'] + (float) $t['taxa'] - (float) $t['desconto'], 2);
			$falta = round($subtotal - array_sum(array_column($pagamentos, 'valor')), 2);
			if ($falta <= 0) continue;
			$pagamentos[] = ['valor' => $falta, 'data' => $data, 'forma' => $forma, 'banco' => $banco, 'operacao' => $operacao];
			fin_baixar($pdo, 'receber', $id, $pagamentos,
				['multa' => $t['multa'], 'juros' => $t['juros'], 'acrescimo' => $t['taxa'], 'desconto' => $t['desconto']],
				$obs !== '' ? $obs : ($t['obs'] ?? ''));
			$n++;
		}
		return $n;
	});
	return $n > 0 ? 'Baixado com Sucesso' : 'Nenhum título pendente encontrado para baixa!';
});
