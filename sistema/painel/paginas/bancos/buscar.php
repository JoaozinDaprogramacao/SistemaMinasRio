<?php
require_once("../../../conexao.php");
require_once(__DIR__ . "/../../seguranca.php");
seguranca_exigir_acesso(['contas_bancarias']);
require_once(__DIR__ . "/../../funcoes/financeiro.php");

$id = (int) ($_POST['id'] ?? 0);
$c = fin_q($pdo, "SELECT id, banco, correntista, agencia, conta FROM bancos WHERE id = ?", [$id])->fetch(PDO::FETCH_ASSOC) ?: [];
$si = fin_q($pdo, "SELECT credito - debito valor, DATE(data) data FROM linha_bancos WHERE id_banco = ? AND origem = 'saldo_inicial'", [$id])->fetch(PDO::FETCH_ASSOC);
$c['saldo_inicial'] = $si ? number_format($si['valor'], 2, ',', '.') : '0,00';
$c['data_saldo_inicial'] = $si ? $si['data'] : date('Y-m-d');
fin_json($c);
