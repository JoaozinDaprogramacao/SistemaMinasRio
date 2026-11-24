<?php
require_once("../../../conexao.php");
require_once(__DIR__ . "/../../seguranca.php");
seguranca_exigir_acesso(['receber', 'clientes']);
require_once(__DIR__ . "/../../funcoes/financeiro.php");

// Monta o PIX "copia e cola" do saldo em aberto do título
$cfg = fin_config($pdo);
$t = fin_q($pdo, "SELECT r.*, c.nome nome_cliente, c.contato FROM receber r LEFT JOIN clientes c ON c.id = r.cliente WHERE r.id = ?", [(int) ($_POST['id'] ?? 0)])->fetch(PDO::FETCH_ASSOC);
if (!$t) { fin_json(['erro' => 'Título não encontrado.']); exit(); }
if (empty($cfg['pix_chave'])) { fin_json(['erro' => 'Cadastre a chave PIX em Financeiro > Parâmetros Financeiros.']); exit(); }
$valor = $t['pago'] === 'Parcial' ? (float) $t['valor_restante'] : (float) $t['valor'];
$payload = fin_pix_payload($cfg['pix_chave'], $cfg['pix_nome'] ?: $cfg['nome'], $cfg['pix_cidade'] ?: 'BRASIL', $valor, 'TIT' . $t['id']);
$msg = '*' . ($cfg['nome'] ?? '') . "*\n" . $t['descricao'] . "\nValor: " . fin_moeda($valor) . "\nVencimento: " . fin_data_br($t['vencimento']) .
	"\n\nPIX copia e cola:\n" . $payload;
$fone = preg_replace('/\D/', '', (string) $t['contato']);
fin_json([
	'titulo' => $t['descricao'] . ($t['nome_cliente'] ? ' - ' . $t['nome_cliente'] : ''),
	'valor' => fin_moeda($valor),
	'payload' => $payload,
	'whats' => 'https://wa.me/' . ($fone ? '55' . $fone : '') . '?text=' . rawurlencode($msg),
]);
