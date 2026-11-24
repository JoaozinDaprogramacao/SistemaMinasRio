<?php
require_once("../../../conexao.php");
require_once(__DIR__ . "/../../seguranca.php");
seguranca_exigir_acesso(['receber', 'clientes']);
require_once(__DIR__ . "/../../funcoes/financeiro.php");

$t = fin_q($pdo, "SELECT id, descricao, valor, cliente, vencimento, data_pgto, forma_pgto, frequencia, obs, arquivo, categoria_receber, centro_custo, pago
	FROM receber WHERE id = ?", [(int) ($_POST['id'] ?? 0)])->fetch(PDO::FETCH_ASSOC);
fin_json($t ?: ['erro' => 'Registro não encontrado']);
