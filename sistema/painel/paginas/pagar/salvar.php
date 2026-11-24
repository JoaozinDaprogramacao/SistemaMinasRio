<?php
require_once("../../../conexao.php");
require_once(__DIR__ . "/../../seguranca.php");
seguranca_exigir_acesso(['pagar']);
require_once(__DIR__ . "/../../funcoes/financeiro.php");

fin_responder(function () use ($pdo) {
	$id = (int) ($_POST['id'] ?? 0);
	$descricao = trim($_POST['descricao'] ?? '');
	$valor = fin_num($_POST['valor'] ?? 0);
	$fornecedor = (int) ($_POST['fornecedor'] ?? 0);
	$funcionario = (int) ($_POST['funcionario'] ?? 0);
	$vencimento = $_POST['vencimento'] ?? '';
	$data_pgto = $_POST['data_pgto'] ?? '';
	$forma_pgto = (int) ($_POST['forma_pgto'] ?? 0);
	$frequencia = (int) ($_POST['frequencia'] ?? 0);
	$obs = trim($_POST['obs'] ?? '');
	$banco = (int) ($_POST['banco'] ?? 0);
	$categoria = (int) ($_POST['categoria_pagar'] ?? 0) ?: null;
	$centro = (int) ($_POST['centro_custo'] ?? 0) ?: null;

	if ($descricao == '' && !$fornecedor && !$funcionario) return 'Selecione um Fornecedor ou um Funcionário ou uma Descrição!';
	if ($fornecedor && $funcionario) return 'Selecione um Fornecedor ou um Funcionário!';
	if ($valor <= 0) return 'Informe o valor da conta!';
	if (!fin_data_valida($vencimento)) return 'Informe o vencimento!';
	if ($data_pgto !== '' && !fin_data_valida($data_pgto)) return 'Data de pagamento inválida!';

	if ($descricao == '') {
		$descricao = $fornecedor
			? fin_q($pdo, "SELECT nome_atacadista FROM fornecedores WHERE id = ?", [$fornecedor])->fetchColumn()
			: fin_q($pdo, "SELECT nome FROM funcionarios WHERE id = ?", [$funcionario])->fetchColumn();
		$descricao = $descricao ?: 'Conta a pagar';
	}

	return fin_transacao($pdo, function () use ($pdo, $id, $descricao, $valor, $fornecedor, $funcionario, $vencimento, $data_pgto, $forma_pgto, $frequencia, $obs, $banco, $categoria, $centro) {
		if ($id) {
			$t = fin_titulo($pdo, 'pagar', $id);
			if ((int) $t['id_romaneio'] > 0) return 'Este lançamento veio de um romaneio. Edite pelo romaneio.';
			if ((int) $t['id_baldeio'] > 0) return 'Este lançamento veio de um baldeio. Edite pelo baldeio.';
			$tem_pgto = (bool) fin_pagamentos($pdo, 'pagar', $id);
			if ($tem_pgto && round($t['valor'], 2) != $valor) {
				return 'Esta conta já tem pagamentos. Para mudar o valor, estorne a baixa primeiro.';
			}
			fin_validar_periodo($pdo, $t['data_lanc'], 'alterar');
			$arquivo = fin_upload_anexo('foto', $t['arquivo']);
			fin_q($pdo, "UPDATE pagar SET descricao = ?, fornecedor = ?, funcionario = ?, valor = ?, vencimento = ?, forma_pgto = ?, frequencia = ?,
				obs = ?, arquivo = ?, categoria_pagar = ?, centro_custo = ?" . ($tem_pgto ? '' : ', subtotal = ?') . " WHERE id = ?",
				array_merge([$descricao, $fornecedor, $funcionario, $valor, $vencimento, $forma_pgto, $frequencia, mb_substr($obs, 0, 100), $arquivo, $categoria, $centro],
					$tem_pgto ? [] : [$valor], [$id]));
			if ($tem_pgto) fin_recalcular_titulo($pdo, 'pagar', $id);
			fin_log($pdo, 'editar', 'pagar', $id, 'Editou "' . $descricao . '" (' . fin_moeda($valor) . ')', ['antes' => $t]);
			if (!$tem_pgto && $data_pgto !== '') {
				fin_titulo_pago_na_criacao($pdo, 'pagar', $id, $data_pgto, $forma_pgto, $banco);
			}
			return 'Salvo com Sucesso';
		}

		fin_validar_periodo($pdo, date('Y-m-d'), 'lançar');
		$arquivo = fin_upload_anexo('foto');
		$caixa = (int) fin_q($pdo, "SELECT id FROM caixas WHERE operador = ? AND data_fechamento IS NULL ORDER BY id DESC LIMIT 1", [fin_usuario()])->fetchColumn();
		fin_q($pdo, "INSERT INTO pagar SET descricao = ?, fornecedor = ?, funcionario = ?, valor = ?, subtotal = ?, vencimento = ?, data_lanc = curDate(),
			forma_pgto = ?, frequencia = ?, obs = ?, arquivo = ?, usuario_lanc = ?, usuario_pgto = 0, pago = 'Não', referencia = 'Conta', caixa = ?,
			hora = curTime(), categoria_pagar = ?, centro_custo = ?", [
			$descricao, $fornecedor, $funcionario, $valor, $valor, $vencimento, $forma_pgto, $frequencia, mb_substr($obs, 0, 100), $arquivo,
			fin_usuario(), $caixa, $categoria, $centro,
		]);
		$novo = (int) $pdo->lastInsertId();
		fin_log($pdo, 'criar', 'pagar', $novo, 'Lançou "' . $descricao . '" de ' . fin_moeda($valor) . ' para ' . fin_data_br($vencimento));
		if ($data_pgto !== '') {
			fin_titulo_pago_na_criacao($pdo, 'pagar', $novo, $data_pgto, $forma_pgto, $banco);
		}
		return 'Salvo com Sucesso';
	});
});
