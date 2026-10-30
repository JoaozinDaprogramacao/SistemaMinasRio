<?php
$tabela = 'receber';
require_once("../../../conexao.php");
require_once("../vendas/funcoes_venda.php");
@session_start();
$id_usuario = $_SESSION['id'];

$id = intval($_POST['id-baixar'] ?? 0);
$valor = round(venda_valor_br($_POST['valor-baixar'] ?? ''), 2);      // principal quitado
$subtotal = round(venda_valor_br($_POST['subtotal'] ?? ''), 2);       // efetivamente recebido
$multa = venda_valor_br($_POST['valor-multa'] ?? '');
$juros = venda_valor_br($_POST['valor-juros'] ?? '');
$desconto = venda_valor_br($_POST['valor-desconto'] ?? '');
$saida = intval($_POST['saida-baixar'] ?? 0);
$data_baixar = $_POST['data-baixar'] ?? '';
if (empty($data_baixar)) {
	$data_baixar = date('Y-m-d');
}

$query = $pdo->prepare("SELECT * FROM $tabela WHERE id = :id");
$query->execute([':id' => $id]);
$conta = $query->fetch(PDO::FETCH_ASSOC);

if (!$conta) {
	echo 'Conta não encontrada!';
	exit();
}
if ($conta['pago'] == 'Sim' || $conta['cancelada'] == 'Sim') {
	echo 'Esta conta já está paga ou cancelada!';
	exit();
}
if ($valor <= 0 || $subtotal <= 0) {
	echo 'O valor precisa ser maior que 0!';
	exit();
}
if ($valor > $conta['valor']) {
	echo 'O valor a ser pago não pode ser superior ao valor da conta! O valor da conta é de R$ ' . number_format($conta['valor'], 2, ',', '.');
	exit();
}

$query = $pdo->prepare("SELECT id FROM caixas WHERE operador = :op AND data_fechamento IS NULL ORDER BY id DESC LIMIT 1");
$query->execute([':op' => $id_usuario]);
$id_caixa = $query->fetchColumn() ?: 0;

$pdo->beginTransaction();
try {
	if (abs($valor - $conta['valor']) < 0.005) {
		// Quitação total da conta
		$pdo->prepare("UPDATE $tabela SET forma_pgto = :forma, usuario_pgto = :u, pago = 'Sim', valor = :sub, subtotal = :sub2,
				multa = :multa, juros = :juros, desconto = :desc, data_pgto = :dt, caixa = :caixa, hora = curTime() WHERE id = :id")
			->execute([':forma' => $saida, ':u' => $id_usuario, ':sub' => $subtotal, ':sub2' => $subtotal, ':multa' => $multa,
				':juros' => $juros, ':desc' => $desconto, ':dt' => $data_baixar, ':caixa' => $id_caixa, ':id' => $id]);
	} else {
		// Pagamento parcial: o recebido vira um resíduo pago e a conta fica com o saldo
		$pdo->prepare("INSERT INTO $tabela SET descricao = :descr, cliente = :cli, valor = :sub, subtotal = :sub2,
				multa = :multa, juros = :juros, desconto = :desc, vencimento = :venc, data_lanc = curDate(), data_pgto = :dt,
				forma_pgto = :forma, frequencia = 0, usuario_lanc = :u1, usuario_pgto = :u2, pago = 'Sim', residuo = 'Sim',
				referencia = 'Venda', id_ref = :ref, caixa = :caixa, hora = curTime(), id_venda_real = :venda")
			->execute([
				':descr' => '(Resíduo) ' . $conta['descricao'], ':cli' => $conta['cliente'], ':sub' => $subtotal, ':sub2' => $subtotal,
				':multa' => $multa, ':juros' => $juros, ':desc' => $desconto, ':venc' => $conta['vencimento'], ':dt' => $data_baixar,
				':forma' => $saida, ':u1' => $id_usuario, ':u2' => $id_usuario, ':ref' => $id, ':caixa' => $id_caixa,
				':venda' => $conta['id_venda_real'],
			]);
		$pdo->prepare("UPDATE $tabela SET valor = valor - :v WHERE id = :id")->execute([':v' => $valor, ':id' => $id]);
	}

	if ($conta['id_venda_real']) {
		venda_recalcular_status($pdo, $conta['id_venda_real']);
	}
	$pdo->commit();
	echo 'Baixado com Sucesso';
} catch (Exception $e) {
	$pdo->rollBack();
	echo 'Erro ao baixar: ' . $e->getMessage();
}
