<?php
require_once("../../../conexao.php");
require_once(__DIR__ . "/../../seguranca.php");
seguranca_exigir_acesso(['contas_bancarias']);
require_once(__DIR__ . "/../../funcoes/financeiro.php");

fin_responder(function () use ($pdo) {
	fin_excluir_lancamento($pdo, (int) ($_POST['id'] ?? 0));
	return 'Excluído com Sucesso';
});
