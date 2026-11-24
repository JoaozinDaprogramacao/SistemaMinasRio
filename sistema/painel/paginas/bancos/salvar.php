<?php
require_once("../../../conexao.php");
require_once(__DIR__ . "/../../seguranca.php");
seguranca_exigir_acesso(['contas_bancarias']);
require_once(__DIR__ . "/../../funcoes/financeiro.php");

fin_responder(function () use ($pdo) {
	$id = (int) ($_POST['id'] ?? 0);
	$banco = trim($_POST['banco'] ?? '');
	if ($banco === '') return 'Informe o nome da conta.';
	$saldo = fin_num($_POST['saldo_inicial'] ?? 0);
	$data = $_POST['data_saldo_inicial'] ?? '';
	if (!fin_data_valida($data)) $data = date('Y-m-d');
	$dados = [trim($_POST['correntista'] ?? ''), $banco, trim($_POST['agencia'] ?? ''), trim($_POST['conta'] ?? '')];

	return fin_transacao($pdo, function () use ($pdo, $id, $dados, $saldo, $data) {
		if ($id) {
			fin_q($pdo, "UPDATE bancos SET correntista = ?, banco = ?, agencia = ?, conta = ? WHERE id = ?", array_merge($dados, [$id]));
		} else {
			fin_q($pdo, "INSERT INTO bancos SET correntista = ?, banco = ?, agencia = ?, conta = ?, saldo = 0, id_usuario = ?", array_merge($dados, [fin_usuario()]));
			$id = (int) $pdo->lastInsertId();
		}
		fin_definir_saldo_inicial($pdo, $id, $saldo, $data);
		fin_log($pdo, 'salvar_conta', 'bancos', $id, 'Salvou a conta ' . $dados[1]);
		return 'Salvo com Sucesso';
	});
});
