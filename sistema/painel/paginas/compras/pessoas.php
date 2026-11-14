<?php
require_once("../../../conexao.php");
require_once(__DIR__ . "/../../seguranca.php");
seguranca_exigir_acesso(['compras']);

// Fornecedores para o select do PDV (recarregado quando o operador volta do cadastro)
$res = $pdo->query("SELECT id, nome_atacadista, cpf, cnpj FROM fornecedores ORDER BY nome_atacadista ASC")->fetchAll(PDO::FETCH_ASSOC);
header('Content-Type: application/json; charset=utf-8');
echo json_encode(array_map(function ($f) {
	$doc = $f['cnpj'] ?: $f['cpf'];
	return ['id' => (int) $f['id'], 'nome' => trim($f['nome_atacadista'] . ($doc ? ' - ' . $doc : ''))];
}, $res));
