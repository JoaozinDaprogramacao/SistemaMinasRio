<?php
require_once("../../../conexao.php");
require_once(__DIR__ . "/../../seguranca.php");
seguranca_exigir_acesso(['contas_bancarias', 'conciliacao']);
require_once(__DIR__ . "/../../funcoes/financeiro.php");

// Marca/desmarca a conferência de uma linha do extrato com o extrato do banco
fin_responder(function () use ($pdo) {
	fin_marcar_conciliado($pdo, (int) ($_POST['id'] ?? 0), (int) ($_POST['conciliado'] ?? 0) === 1);
	return 'Salvo com Sucesso';
});
