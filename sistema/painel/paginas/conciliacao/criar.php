<?php
require_once("../../../conexao.php");
require_once(__DIR__ . "/../../seguranca.php");
seguranca_exigir_acesso(['conciliacao']);
require_once(__DIR__ . "/../../funcoes/financeiro.php");

fin_responder(function () use ($pdo) {
	fin_lancar_do_extrato($pdo, (int) ($_POST['id'] ?? 0), trim($_POST['descricao'] ?? ''),
		(int) ($_POST['categoria_receber'] ?? 0) ?: null, (int) ($_POST['categoria_pagar'] ?? 0) ?: null, (int) ($_POST['centro_custo'] ?? 0) ?: null);
	return 'Salvo com Sucesso';
});
