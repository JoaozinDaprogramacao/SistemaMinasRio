<?php
require_once("../../../conexao.php");
require_once(__DIR__ . "/../../seguranca.php");
seguranca_exigir_acesso(['vendas']);

// Clientes para o select do PDV (recarregado quando o operador volta do cadastro)
$res = $pdo->query("SELECT id, nome, cpf, plano_pagamento, forma_pagamento, prazo_pagamento FROM clientes ORDER BY nome ASC")->fetchAll(PDO::FETCH_ASSOC);
header('Content-Type: application/json; charset=utf-8');
echo json_encode(array_map(function ($c) {
	return [
		'id' => (int) $c['id'],
		'nome' => trim($c['nome'] . ($c['cpf'] ? ' - ' . $c['cpf'] : '')),
		'plano' => (int) $c['plano_pagamento'],
		'forma' => (int) $c['forma_pagamento'],
		'prazo' => (int) $c['prazo_pagamento'],
	];
}, $res));
