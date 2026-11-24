<?php
require_once("../../../conexao.php");
require_once(__DIR__ . "/../../seguranca.php");
seguranca_exigir_acesso(['conciliacao']);
require_once(__DIR__ . "/../../funcoes/financeiro.php");

fin_responder(function () use ($pdo) {
	fin_transacao($pdo, function () use ($pdo) {
		fin_vincular_extrato($pdo, (int) ($_POST['extrato'] ?? 0), (int) ($_POST['linha'] ?? 0));
	});
	return 'Salvo com Sucesso';
});
