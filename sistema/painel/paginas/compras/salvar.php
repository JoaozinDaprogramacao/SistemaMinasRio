<?php
require_once("../../../conexao.php");
require_once(__DIR__ . "/../../seguranca.php");
seguranca_exigir_acesso(['compras']);
require_once(__DIR__ . "/../vendas/funcoes_venda.php");
require_once(__DIR__ . "/../pdv_diversos/pagamento_comum.php");
require_once(__DIR__ . "/../../funcoes/financeiro.php");

$id_usuario = $_SESSION['id'];
$data_atual = date('Y-m-d');

function compra_data($v, $padrao)
{
	return preg_match('/^\d{4}-\d{2}-\d{2}$/', (string) $v) ? $v : $padrao;
}

// --- 1. DADOS DO FORMULÁRIO (mesmas regras do PDV de vendas) ---
$fornecedor = intval($_POST['pessoa'] ?? 0);
$saida = intval($_POST['saida'] ?? 0);
$data = compra_data($_POST['data2'] ?? '', $data_atual);
$desconto = venda_valor_br($_POST['desconto'] ?? '');
$frete = venda_valor_br($_POST['frete'] ?? '');
$tipo_desconto = ($_POST['tipo_desconto'] ?? '') == '%' ? '%' : 'valor';
$valor_pago = venda_valor_br($_POST['valor_pago'] ?? '');
$forma_pgto2 = intval($_POST['forma_pgto2'] ?? 0);

if ($saida <= 0) {
	echo 'Selecione a forma de pagamento!';
	exit();
}
if ($desconto < 0 || $frete < 0 || $valor_pago < 0) {
	echo 'Desconto, frete e valor pago não podem ser negativos!';
	exit();
}

// O carrinho no banco é a fonte da verdade: a tela só exibe.
$stmt = $pdo->prepare("SELECT ic.*, m.nome FROM itens_compra ic
	JOIN materiais m ON m.id = ic.material
	WHERE ic.funcionario = :f AND ic.id_compra = 0 ORDER BY ic.id");
$stmt->execute([':f' => $id_usuario]);
$itens = $stmt->fetchAll(PDO::FETCH_ASSOC);
if (count($itens) == 0) {
	echo 'Adicione pelo menos um item à compra!';
	exit();
}

$subtotal = 0;
foreach ($itens as $item) {
	if ($item['valor'] <= 0) {
		echo 'Informe o preço unitário do item ' . $item['nome'] . '!';
		exit();
	}
	$subtotal += $item['total'];
}
$subtotal = round($subtotal, 2);

$valor_desconto = ($tipo_desconto == '%') ? round($subtotal * $desconto / 100, 2) : $desconto;
if ($valor_desconto > $subtotal) {
	echo 'O desconto não pode ser maior que o valor dos itens!';
	exit();
}
$total_final = round($subtotal - $valor_desconto + $frete, 2);
if ($total_final <= 0) {
	echo 'O valor final da compra tem que ser maior que zero.';
	exit();
}

if ($valor_pago > $total_final) {
	$valor_pago = $total_final;
}
$valor_restante = round($total_final - $valor_pago, 2);
if ($valor_restante > 0 && $forma_pgto2 <= 0) {
	$forma_pgto2 = $saida;
}

$parcelas = [];
if ($valor_restante > 0) {
	$parcelas = pdv_parcelas($valor_restante, $data);
	if (is_string($parcelas)) {
		echo $parcelas;
		exit();
	}
}

// Compra com data futura é a prazo: a entrada fica a pagar até a baixa.
$entrada_paga = ($data <= $data_atual);

$stmt = $pdo->prepare("SELECT id FROM caixas WHERE operador = :op AND data_fechamento IS NULL ORDER BY id DESC LIMIT 1");
$stmt->execute([':op' => $id_usuario]);
$id_caixa = (int) $stmt->fetchColumn();

// --- 2. GRAVAÇÃO ---
$pdo->beginTransaction();
try {
	// Contas a pagar: entrada (o que foi pago agora) e restante
	$sql_pagar = "INSERT INTO pagar SET descricao = 'Compra', valor = :valor, subtotal = :subtotal, vencimento = :vencimento,
		data_lanc = curDate(), data_pgto = :data_pgto, usuario_lanc = :usuario_lanc, usuario_pgto = :usuario_pgto,
		arquivo = 'sem-foto.png', pago = :pago, fornecedor = :fornecedor, funcionario = 0, referencia = 'Compra',
		id_ref = :id_ref, hora = curTime(), forma_pgto = :forma_pgto, desconto = :desconto, frete = :frete,
		tipo_desconto = :tipo_desconto, valor_restante = :valor_restante, caixa = :caixa, frequencia = 0";
	$comuns = [
		':usuario_lanc' => $id_usuario,
		':fornecedor' => $fornecedor,
		':desconto' => $desconto,
		':frete' => $frete,
		':tipo_desconto' => $tipo_desconto,
		':caixa' => $id_caixa,
	];
	$ids_entrada = 0;
	$ids_parcelas = [];

	if ($valor_pago > 0) {
		$pdo->prepare($sql_pagar)->execute($comuns + [
			':valor' => $valor_pago,
			':subtotal' => $valor_pago,
			':vencimento' => $data,
			':data_pgto' => null,
			':usuario_pgto' => 0,
			':pago' => 'Não',
			':forma_pgto' => $saida,
			':id_ref' => null,
			':valor_restante' => null,
		]);
		$ids_entrada = (int) $pdo->lastInsertId();
	}

	// O restante (a prazo) vira uma conta a pagar por parcela
	foreach ($parcelas as $parcela) {
		$pdo->prepare($sql_pagar)->execute($comuns + [
			':valor' => $parcela['valor'],
			':subtotal' => $parcela['valor'],
			':vencimento' => $parcela['vencimento'],
			':data_pgto' => null,
			':usuario_pgto' => 0,
			':pago' => 'Não',
			':forma_pgto' => $forma_pgto2,
			':id_ref' => ($ids_entrada ?: ($ids_parcelas[0] ?? null)),
			':valor_restante' => $parcela['valor'],
		]);
		$ids_parcelas[] = (int) $pdo->lastInsertId();
	}

	// O número da compra é o id do primeiro lançamento
	$id_compra = $ids_entrada ?: $ids_parcelas[0];
	if ($ids_entrada) {
		$pdo->prepare("UPDATE pagar SET descricao = :d WHERE id = :id")
			->execute([':d' => 'Compra #' . $id_compra . ($ids_parcelas ? ' (Entrada)' : ''), ':id' => $ids_entrada]);
	}
	foreach ($ids_parcelas as $i => $id_pagar) {
		$pdo->prepare("UPDATE pagar SET descricao = :d WHERE id = :id")
			->execute([':d' => pdv_descricao_parcela('Compra #' . $id_compra, $i, count($ids_parcelas), $ids_entrada > 0), ':id' => $id_pagar]);
	}

	// Entrada paga no ato: registra o pagamento na conta padrão da forma de pagamento.
	// Sem conta padrão a entrada fica em aberto em Contas a Pagar para ser baixada lá.
	if ($ids_entrada && $entrada_paga) {
		if ((int) (fin_forma($pdo, $saida)['banco_padrao'] ?? 0) > 0) {
			fin_titulo_pago_na_criacao($pdo, 'pagar', $ids_entrada, $data, $saida);
		} else {
			fin_log($pdo, 'entrada_em_aberto', 'pagar', $ids_entrada, 'Entrada da compra #' . $id_compra . ' ficou em aberto: forma de pagamento sem conta padrão');
		}
	}

	$pdo->prepare("UPDATE itens_compra SET id_compra = :c WHERE funcionario = :f AND id_compra = 0")
		->execute([':c' => $id_compra, ':f' => $id_usuario]);

	// Estoque sobe uma única vez, aqui; o contador de compras anda para todos os materiais
	$stmt_estoque = $pdo->prepare("UPDATE materiais SET
			estoque = IF(tem_estoque = 'Sim', estoque + :q1, estoque),
			compras = COALESCE(compras, 0) + :q2
		WHERE id = :id");
	// Movimentação que aparece em Detalhes Materiais
	$stmt_detalhes = $pdo->prepare("INSERT INTO detalhes_materiais (material_id, data, compra, valor_compra, preco_unidade, entrada_estoque, descricao)
		VALUES (:material_id, :data, :compra, :valor_compra, :preco_unidade, :entrada_estoque, 0)");
	foreach ($itens as $item) {
		$stmt_estoque->execute([':q1' => $item['quantidade'], ':q2' => $item['quantidade'], ':id' => $item['material']]);
		$stmt_detalhes->execute([
			':material_id' => $item['material'],
			':data' => $data . ' ' . date('H:i:s'),
			':compra' => $item['quantidade'],
			':valor_compra' => $item['total'],
			':preco_unidade' => $item['valor'],
			':entrada_estoque' => $item['quantidade'],
		]);
	}

	$pdo->commit();
	echo 'Salvo com Sucesso-' . $id_compra;
} catch (Exception $e) {
	$pdo->rollBack();
	echo 'Erro ao salvar: ' . $e->getMessage();
}
