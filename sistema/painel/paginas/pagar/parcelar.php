<?php
require_once("../../../conexao.php");
require_once(__DIR__ . "/../../seguranca.php");
seguranca_exigir_acesso(['pagar']);
require_once(__DIR__ . "/../../funcoes/financeiro.php");

fin_responder(function () use ($pdo) {
	fin_parcelar($pdo, 'pagar', (int) ($_POST['id-parcelar'] ?? 0), (int) ($_POST['qtd-parcelar'] ?? 0), (int) ($_POST['frequencia'] ?? 0));
	return 'Parcelado com Sucesso';
});
