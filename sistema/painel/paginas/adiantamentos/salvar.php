<?php
require_once("../../../conexao.php");
require_once(__DIR__ . "/../../seguranca.php");
seguranca_exigir_acesso(['adiantamentos']);
require_once(__DIR__ . "/../../funcoes/financeiro.php");

fin_responder(function () use ($pdo) {
	$tipo = $_POST['tipo'] ?? '';
	$pessoa = (int) ($tipo === 'cliente' ? ($_POST['cliente'] ?? 0) : ($_POST['fornecedor'] ?? 0));
	$data = $_POST['data'] ?? '';
	if (!fin_data_valida($data)) return 'Informe a data.';
	fin_adiantamento_criar($pdo, $tipo, $pessoa, fin_num($_POST['valor'] ?? 0), $data, (int) ($_POST['banco'] ?? 0),
		(int) ($_POST['forma'] ?? 0), trim($_POST['descricao'] ?? ''));
	return 'Salvo com Sucesso';
});
