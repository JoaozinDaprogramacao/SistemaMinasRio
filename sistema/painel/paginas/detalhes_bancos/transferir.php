<?php
require_once("../../../conexao.php");
require_once(__DIR__ . "/../../seguranca.php");
seguranca_exigir_acesso(['contas_bancarias']);
require_once(__DIR__ . "/../../funcoes/financeiro.php");

fin_responder(function () use ($pdo) {
	fin_transferir($pdo, (int) ($_POST['origem'] ?? 0), (int) ($_POST['destino'] ?? 0), fin_num($_POST['valor'] ?? 0),
		$_POST['data'] ?? '', trim($_POST['descricao'] ?? ''), trim($_POST['documento'] ?? ''));
	return 'Transferido com Sucesso';
});
