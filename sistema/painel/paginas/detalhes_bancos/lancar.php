<?php
require_once("../../../conexao.php");
require_once(__DIR__ . "/../../seguranca.php");
seguranca_exigir_acesso(['contas_bancarias']);
require_once(__DIR__ . "/../../funcoes/financeiro.php");

// Lançamento avulso direto no extrato (tarifa, rendimento, depósito não identificado...)
fin_responder(function () use ($pdo) {
	$tipo = $_POST['tipo'] ?? '';
	if (!in_array($tipo, ['credito', 'debito'], true)) return 'Tipo inválido.';
	$valor = fin_num($_POST['valor'] ?? 0);
	if ($valor <= 0) return 'Informe o valor.';
	$descricao = trim($_POST['descricao'] ?? '');
	if ($descricao === '') return 'Informe a descrição.';
	return fin_transacao($pdo, function () use ($pdo, $tipo, $valor, $descricao) {
		$id = fin_lancar($pdo, [
			'id_banco' => (int) ($_POST['id_banco'] ?? 0), 'data' => $_POST['data'] ?? '', 'descricao' => $descricao,
			$tipo => $valor, 'origem' => 'manual', 'documento' => trim($_POST['documento'] ?? ''), 'obs' => trim($_POST['obs'] ?? ''),
			'categoria_receber' => $tipo === 'credito' ? (int) ($_POST['categoria_receber'] ?? 0) : null,
			'categoria_pagar' => $tipo === 'debito' ? (int) ($_POST['categoria_pagar'] ?? 0) : null,
			'centro_custo' => (int) ($_POST['centro_custo'] ?? 0),
		]);
		fin_log($pdo, 'lancamento_avulso', 'linha_bancos', $id, ($tipo === 'credito' ? 'Entrada' : 'Saída') . " avulsa \"$descricao\": " . fin_moeda($valor));
		return 'Lançado com Sucesso';
	});
});
