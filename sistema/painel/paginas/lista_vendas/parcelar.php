<?php
$tabela = 'receber';
require_once("../../../conexao.php");

@session_start();
$id_usuario = $_SESSION['id'];

$id = intval($_POST['id-parcelar'] ?? 0);
$dias_frequencia = intval($_POST['frequencia'] ?? 0);
$qtd_parcelas = intval($_POST['qtd-parcelar'] ?? 0);

$query = $pdo->prepare("SELECT * FROM $tabela WHERE id = :id");
$query->execute([':id' => $id]);
$conta = $query->fetch(PDO::FETCH_ASSOC);

if (!$conta) {
	echo 'Conta não encontrada!';
	exit();
}
if ($conta['pago'] == 'Sim' || $conta['cancelada'] == 'Sim') {
	echo 'Não é possível parcelar uma conta paga ou cancelada!';
	exit();
}
if ($qtd_parcelas < 2) {
	echo 'Informe pelo menos 2 parcelas!';
	exit();
}
if ($dias_frequencia <= 0) {
	echo 'Selecione a frequência das parcelas!';
	exit();
}

// Parcelas em centavos inteiros; a diferença do arredondamento vai para a última
$valor_total = round($conta['valor'], 2);
$valor_parcela = floor($valor_total * 100 / $qtd_parcelas) / 100;
$data_venc = $conta['vencimento'];

$pdo->beginTransaction();
try {
	$insere = $pdo->prepare("INSERT INTO $tabela SET descricao = :descr, cliente = :cli, valor = :val, subtotal = :sub,
			usuario_lanc = :u, usuario_pgto = 0, data_lanc = curDate(), vencimento = :venc, frequencia = 0, forma_pgto = :forma,
			arquivo = :arq, pago = 'Não', referencia = :ref, id_ref = :id_ref, id_venda_real = :venda");

	for ($i = 1; $i <= $qtd_parcelas; $i++) {
		$parcela = ($i == $qtd_parcelas) ? round($valor_total - $valor_parcela * ($qtd_parcelas - 1), 2) : $valor_parcela;

		// 30/31 dias = mensal (mesmo dia do mês), 90 trimestral, 180 semestral, 360/365 anual
		$meses = ['30' => 1, '31' => 1, '90' => 3, '180' => 6, '360' => 12, '365' => 12][$dias_frequencia] ?? 0;
		if ($meses > 0) {
			$vencimento = date('Y-m-d', strtotime('+' . (($i - 1) * $meses) . ' month', strtotime($data_venc)));
		} else {
			$vencimento = date('Y-m-d', strtotime('+' . (($i - 1) * $dias_frequencia) . ' days', strtotime($data_venc)));
		}

		$insere->execute([
			':descr' => $conta['descricao'] . ' - Parcela ' . $i . '/' . $qtd_parcelas,
			':cli' => $conta['cliente'], ':val' => $parcela, ':sub' => $parcela, ':u' => $id_usuario, ':venc' => $vencimento,
			':forma' => $conta['forma_pgto'], ':arq' => $conta['arquivo'], ':ref' => $conta['referencia'],
			':id_ref' => $conta['id_ref'], ':venda' => $conta['id_venda_real'],
		]);
		if ($i == 1) {
			$id_primeira_parcela = $pdo->lastInsertId();
		}
	}

	$pdo->prepare("DELETE FROM $tabela WHERE id = :id")->execute([':id' => $id]);
	// Itens da venda apontavam para a conta apagada: passam a apontar para a 1ª parcela
	$pdo->prepare("UPDATE itens_venda SET id_venda = :novo WHERE id_venda = :antigo")
		->execute([':novo' => $id_primeira_parcela, ':antigo' => $id]);

	$pdo->commit();
	echo 'Parcelado com Sucesso';
} catch (Exception $e) {
	$pdo->rollBack();
	echo 'Erro ao parcelar: ' . $e->getMessage();
}
