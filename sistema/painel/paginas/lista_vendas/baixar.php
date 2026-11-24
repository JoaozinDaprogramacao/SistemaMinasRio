<?php
require_once("../../../conexao.php");
require_once(__DIR__ . "/../../seguranca.php");
seguranca_exigir_acesso(['lista_vendas']);
require_once("../vendas/funcoes_venda.php");
require_once(__DIR__ . "/../../funcoes/financeiro.php");

// Baixa (total ou parcial) de uma parcela da venda. O recebimento fica registrado no próprio
// título, com a conta bancária, e entra no extrato — sem criar "resíduos" separados.
fin_responder(function () use ($pdo) {
	$id = (int) ($_POST['id-baixar'] ?? 0);
	$valor = round(venda_valor_br($_POST['valor-baixar'] ?? ''), 2);   // principal quitado
	$multa = round(venda_valor_br($_POST['valor-multa'] ?? ''), 2);
	$juros = round(venda_valor_br($_POST['valor-juros'] ?? ''), 2);
	$desconto = round(venda_valor_br($_POST['valor-desconto'] ?? ''), 2);
	$forma = (int) ($_POST['saida-baixar'] ?? 0);
	$banco = (int) ($_POST['banco-baixar'] ?? 0);
	$data = ($_POST['data-baixar'] ?? '') ?: date('Y-m-d');

	$conta = fin_titulo($pdo, 'receber', $id);
	if ($conta['pago'] == 'Sim' || $conta['cancelada'] == 'Sim') return 'Esta conta já está paga ou cancelada!';
	$ja_pago = fin_total_pago($pdo, 'receber', $id);
	// Principal ainda em aberto: o que já foi pago cobre acréscimos primeiro; desconto abate o principal
	$em_aberto = round($conta['valor'] - $ja_pago + (float) $conta['multa'] + (float) $conta['juros'] + (float) $conta['taxa'] - (float) $conta['desconto'], 2);
	if ($valor <= 0) return 'O valor precisa ser maior que 0!';
	if ($valor > $em_aberto + 0.009) return 'O valor a ser pago não pode ser superior ao saldo da conta (' . fin_moeda($em_aberto) . ')!';

	$recebido = round($valor + $multa + $juros - $desconto, 2);
	if ($recebido <= 0) return 'O valor recebido precisa ser maior que 0!';

	$pagamentos = [];
	foreach (fin_pagamentos($pdo, 'receber', $id) as $pg) {
		$pagamentos[] = ['valor' => $pg['valor'], 'data' => $pg['data_pgto'], 'forma' => $pg['forma_pgto'], 'banco' => $pg['banco'],
			'operacao' => $pg['numero_operacao'], 'id_adiantamento' => $pg['id_adiantamento']];
	}
	$pagamentos[] = ['valor' => $recebido, 'data' => $data, 'forma' => $forma, 'banco' => $banco, 'operacao' => ''];
	$ajustes = [
		'multa' => (float) $conta['multa'] + $multa,
		'juros' => (float) $conta['juros'] + $juros,
		'acrescimo' => (float) $conta['taxa'],
		'desconto' => (float) $conta['desconto'] + $desconto,
	];
	fin_baixar($pdo, 'receber', $id, $pagamentos, $ajustes, $conta['obs'] ?? '');
	return 'Baixado com Sucesso';
});
