<?php
// Carrinho do PDV de diversos, usado pelas telas Vendas e Compras.
// Quem inclui já abriu $pdo, conferiu a permissão e incluiu vendas/funcoes_venda.php.
//
// $cfg:
//   tabela      itens_venda | itens_compra
//   coluna_pai  id_venda | id_compra (0 = item ainda no carrinho)
//   disponivel  function($pdo, $material): float|null  quanto pode sair do estoque (null = sem limite)
//   ao_limpar   function(): void  (opcional)

function pdv_responder($dados)
{
	header('Content-Type: application/json; charset=utf-8');
	echo json_encode($dados);
	exit();
}

function pdv_erro($msg)
{
	pdv_responder(['ok' => false, 'msg' => $msg]);
}

// A coluna quantidade é DECIMAL(10,1)
function pdv_quantidade($v)
{
	return round(venda_valor_br($v), 1);
}

function pdv_itens($pdo, $cfg, $id_usuario)
{
	$t = $cfg['tabela'];
	$c = $cfg['coluna_pai'];
	$stmt = $pdo->prepare("SELECT i.id, i.material, i.quantidade, i.valor, i.total, m.nome, m.tem_estoque, m.estoque
		FROM $t i LEFT JOIN materiais m ON m.id = i.material
		WHERE i.funcionario = :f AND i.$c = 0 ORDER BY i.id");
	$stmt->execute([':f' => $id_usuario]);
	$itens = [];
	foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $r) {
		$itens[] = [
			'id' => (int) $r['id'],
			'material' => (int) $r['material'],
			'nome' => $r['nome'] ?? ('[Material excluído #' . $r['material'] . ']'),
			'quantidade' => (float) $r['quantidade'],
			'valor' => (float) $r['valor'],
			'total' => (float) $r['total'],
		];
	}
	return $itens;
}

function pdv_material($pdo, $id_material)
{
	$stmt = $pdo->prepare("SELECT id, nome, tem_estoque, estoque FROM materiais WHERE id = :id");
	$stmt->execute([':id' => $id_material]);
	return $stmt->fetch(PDO::FETCH_ASSOC);
}

// Quanto do material já está no carrinho, somando todas as linhas (o mesmo
// material pode estar em várias linhas, uma por preço). $exceto = id de uma
// linha que não entra na soma (a que está sendo alterada).
function pdv_qtd_no_carrinho($pdo, $cfg, $id_usuario, $id_material, $exceto = 0)
{
	$t = $cfg['tabela'];
	$c = $cfg['coluna_pai'];
	$stmt = $pdo->prepare("SELECT COALESCE(SUM(quantidade), 0) FROM $t
		WHERE funcionario = :f AND $c = 0 AND material = :m AND id <> :exceto");
	$stmt->execute([':f' => $id_usuario, ':m' => $id_material, ':exceto' => $exceto]);
	return (float) $stmt->fetchColumn();
}

// $quantidade = total do material no carrinho depois da alteração (todas as linhas)
function pdv_conferir_estoque($pdo, $cfg, $material, $quantidade)
{
	if (empty($cfg['disponivel'])) return;
	$disponivel = $cfg['disponivel']($pdo, $material);
	if ($disponivel !== null && $quantidade > $disponivel) {
		$disp = rtrim(rtrim(number_format($disponivel, 1, ',', '.'), '0'), ',');
		pdv_erro('Estoque insuficiente de ' . $material['nome'] . '. Disponível: ' . $disp);
	}
}

function pdv_carrinho_executar($pdo, $cfg)
{
	$id_usuario = $_SESSION['id'];
	$t = $cfg['tabela'];
	$c = $cfg['coluna_pai'];
	$acao = $_POST['acao'] ?? 'listar';

	if ($acao == 'inserir') {
		$material = pdv_material($pdo, intval($_POST['id_material'] ?? 0));
		if (!$material) pdv_erro('Material não encontrado!');
		$quantidade = pdv_quantidade($_POST['quantidade'] ?? '');
		if ($quantidade <= 0) pdv_erro('Informe uma quantidade maior que zero.');
		$valor_post = trim($_POST['valor'] ?? '');
		$valor = round(venda_valor_br($valor_post), 2);
		if ($valor < 0) pdv_erro('O preço não pode ser negativo.');

		// Sem preço informado vale o da última linha desse material
		if ($valor_post === '') {
			$stmt = $pdo->prepare("SELECT valor FROM $t WHERE funcionario = :f AND $c = 0 AND material = :m ORDER BY id DESC LIMIT 1");
			$stmt->execute([':f' => $id_usuario, ':m' => $material['id']]);
			$ultimo = $stmt->fetchColumn();
			if ($ultimo !== false) $valor = round((float) $ultimo, 2);
		}

		// O mesmo material pode entrar com preços diferentes na mesma venda/compra:
		// só soma numa linha existente quando o preço é o mesmo; senão abre outra linha.
		$stmt = $pdo->prepare("SELECT * FROM $t WHERE funcionario = :f AND $c = 0 AND material = :m AND ABS(valor - :v) < 0.005 ORDER BY id LIMIT 1");
		$stmt->execute([':f' => $id_usuario, ':m' => $material['id'], ':v' => $valor]);
		$item = $stmt->fetch(PDO::FETCH_ASSOC);

		$nova_quantidade = $quantidade + ($item ? (float) $item['quantidade'] : 0);
		pdv_conferir_estoque($pdo, $cfg, $material, $quantidade + pdv_qtd_no_carrinho($pdo, $cfg, $id_usuario, $material['id']));
		$total = round($nova_quantidade * $valor, 2);

		if ($item) {
			$pdo->prepare("UPDATE $t SET quantidade = :q, total = :tot WHERE id = :id")
				->execute([':q' => $nova_quantidade, ':tot' => $total, ':id' => $item['id']]);
			$id = (int) $item['id'];
		} else {
			$pdo->prepare("INSERT INTO $t SET material = :m, quantidade = :q, valor = :v, total = :tot, $c = 0, funcionario = :f")
				->execute([':m' => $material['id'], ':q' => $nova_quantidade, ':v' => $valor, ':tot' => $total, ':f' => $id_usuario]);
			$id = (int) $pdo->lastInsertId();
		}
		pdv_responder(['ok' => true, 'id' => $id, 'itens' => pdv_itens($pdo, $cfg, $id_usuario)]);
	}

	if ($acao == 'atualizar') {
		$id = intval($_POST['id'] ?? 0);
		$stmt = $pdo->prepare("SELECT * FROM $t WHERE id = :id AND funcionario = :f AND $c = 0");
		$stmt->execute([':id' => $id, ':f' => $id_usuario]);
		$item = $stmt->fetch(PDO::FETCH_ASSOC);
		if (!$item) pdv_erro('Item não encontrado no carrinho. Atualize a página.');

		$quantidade = pdv_quantidade($_POST['quantidade'] ?? '');
		$valor = round(venda_valor_br($_POST['valor'] ?? ''), 2);
		if ($quantidade <= 0) pdv_erro('Informe uma quantidade maior que zero.');
		if ($valor < 0) pdv_erro('O preço não pode ser negativo.');

		$material = pdv_material($pdo, $item['material']);
		if ($material) {
			pdv_conferir_estoque($pdo, $cfg, $material, $quantidade + pdv_qtd_no_carrinho($pdo, $cfg, $id_usuario, $material['id'], $id));
		}

		$total = round($quantidade * $valor, 2);
		$pdo->prepare("UPDATE $t SET quantidade = :q, valor = :v, total = :tot WHERE id = :id")
			->execute([':q' => $quantidade, ':v' => $valor, ':tot' => $total, ':id' => $id]);
		pdv_responder(['ok' => true, 'item' => ['id' => $id, 'quantidade' => $quantidade, 'valor' => $valor, 'total' => $total]]);
	}

	if ($acao == 'excluir') {
		// O estoque só muda ao fechar, então tirar do carrinho não devolve nada
		$pdo->prepare("DELETE FROM $t WHERE id = :id AND funcionario = :f AND $c = 0")
			->execute([':id' => intval($_POST['id'] ?? 0), ':f' => $id_usuario]);
		pdv_responder(['ok' => true, 'itens' => pdv_itens($pdo, $cfg, $id_usuario)]);
	}

	if ($acao == 'limpar') {
		$pdo->prepare("DELETE FROM $t WHERE funcionario = :f AND $c = 0")->execute([':f' => $id_usuario]);
		if (!empty($cfg['ao_limpar'])) $cfg['ao_limpar']();
		pdv_responder(['ok' => true, 'itens' => []]);
	}

	pdv_responder(['ok' => true, 'itens' => pdv_itens($pdo, $cfg, $id_usuario)]);
}
