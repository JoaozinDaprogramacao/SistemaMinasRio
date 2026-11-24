<?php
require_once("../../../conexao.php");
require_once(__DIR__ . "/../../seguranca.php");
seguranca_exigir_acesso(['receber', 'clientes']);
require_once(__DIR__ . "/../../funcoes/financeiro.php");

fin_responder(function () use ($pdo) {
	$id = (int) ($_POST['id'] ?? 0);
	$descricao = trim($_POST['descricao'] ?? '');
	$valor = fin_num($_POST['valor'] ?? 0);
	$cliente = (int) ($_POST['cliente'] ?? 0);
	$vencimento = $_POST['vencimento'] ?? '';
	$data_pgto = $_POST['data_pgto'] ?? '';
	$forma_pgto = (int) ($_POST['forma_pgto'] ?? 0);
	$frequencia = (int) ($_POST['frequencia'] ?? 0);
	$obs = trim($_POST['obs'] ?? '');
	$banco = (int) ($_POST['banco'] ?? 0);
	$categoria = (int) ($_POST['categoria_receber'] ?? 0) ?: null;
	$centro = (int) ($_POST['centro_custo'] ?? 0) ?: null;

	if ($descricao == '' && !$cliente) return 'Selecione um Cliente ou uma Descrição!';
	if ($valor <= 0) return 'Informe o valor da conta!';
	if (!fin_data_valida($vencimento)) return 'Informe o vencimento!';
	if ($data_pgto !== '' && !fin_data_valida($data_pgto)) return 'Data de recebimento inválida!';

	if ($descricao == '') {
		$descricao = fin_q($pdo, "SELECT nome FROM clientes WHERE id = ?", [$cliente])->fetchColumn() ?: 'Conta a receber';
	}

	return fin_transacao($pdo, function () use ($pdo, $id, $descricao, $valor, $cliente, $vencimento, $data_pgto, $forma_pgto, $frequencia, $obs, $banco, $categoria, $centro) {
		if ($id) {
			$t = fin_titulo($pdo, 'receber', $id);
			if ((int) $t['id_romaneio'] > 0) return 'Este lançamento veio de um romaneio. Edite pelo romaneio.';
			if ((int) $t['id_venda_real'] > 0) return 'Este lançamento veio de uma venda. Edite pela venda.';
			$tem_pgto = (bool) fin_pagamentos($pdo, 'receber', $id);
			if ($tem_pgto && round($t['valor'], 2) != $valor) {
				return 'Esta conta já tem recebimentos. Para mudar o valor, estorne a baixa primeiro.';
			}
			fin_validar_periodo($pdo, $t['data_lanc'], 'alterar');
			$arquivo = fin_upload_anexo('foto', $t['arquivo']);
			fin_q($pdo, "UPDATE receber SET descricao = ?, cliente = ?, valor = ?, vencimento = ?, forma_pgto = ?, frequencia = ?, obs = ?,
				arquivo = ?, categoria_receber = ?, centro_custo = ?" . ($tem_pgto ? '' : ', subtotal = ?') . " WHERE id = ?",
				array_merge([$descricao, $cliente, $valor, $vencimento, $forma_pgto, $frequencia, $obs, $arquivo, $categoria, $centro], $tem_pgto ? [] : [$valor], [$id]));
			if ($tem_pgto) fin_recalcular_titulo($pdo, 'receber', $id);
			fin_log($pdo, 'editar', 'receber', $id, 'Editou "' . $descricao . '" (' . fin_moeda($valor) . ')', ['antes' => $t]);
			// Conta em aberto em que agora se informou a data de recebimento: dá baixa
			if (!$tem_pgto && $data_pgto !== '') {
				fin_titulo_pago_na_criacao($pdo, 'receber', $id, $data_pgto, $forma_pgto, $banco);
			}
			return 'Salvo com Sucesso';
		}

		fin_validar_periodo($pdo, date('Y-m-d'), 'lançar');
		$arquivo = fin_upload_anexo('foto');
		$caixa = (int) fin_q($pdo, "SELECT id FROM caixas WHERE operador = ? AND data_fechamento IS NULL ORDER BY id DESC LIMIT 1", [fin_usuario()])->fetchColumn();
		fin_q($pdo, "INSERT INTO receber SET descricao = ?, cliente = ?, valor = ?, subtotal = ?, vencimento = ?, data_lanc = curDate(), forma_pgto = ?,
			frequencia = ?, obs = ?, arquivo = ?, usuario_lanc = ?, usuario_pgto = 0, pago = 'Não', referencia = 'Conta', caixa = ?, hora = curTime(),
			categoria_receber = ?, centro_custo = ?", [
			$descricao, $cliente, $valor, $valor, $vencimento, $forma_pgto, $frequencia, $obs, $arquivo, fin_usuario(), $caixa, $categoria, $centro,
		]);
		$novo = (int) $pdo->lastInsertId();
		fin_log($pdo, 'criar', 'receber', $novo, 'Lançou "' . $descricao . '" de ' . fin_moeda($valor) . ' para ' . fin_data_br($vencimento));
		if ($data_pgto !== '') {
			fin_titulo_pago_na_criacao($pdo, 'receber', $novo, $data_pgto, $forma_pgto, $banco);
		}
		return 'Salvo com Sucesso';
	});
});
