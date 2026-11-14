<?php
$pag = 'vendas';

if (@$vendas == 'ocultar') {
	echo "<script>window.location='index.php'</script>";
	exit();
}

$id_usuario_logado = $_SESSION['id'];

// Saiu de uma edição sem salvar: o carrinho copiado da venda não vale mais
if (!isset($_SESSION['modo_edicao_venda']) && isset($_SESSION['carrinho_em_modo_edicao'])) {
	$pdo->prepare("DELETE FROM itens_venda WHERE id_venda = 0 AND funcionario = :f")->execute([':f' => $id_usuario_logado]);
	unset($_SESSION['carrinho_em_modo_edicao'], $_SESSION['id_venda_em_edicao']);
}

$pdv = [
	'tipo' => 'venda',
	'pag' => $pag,
	'id_edicao' => 0,
	'pessoa' => '',
	'desconto' => '',
	'tipo_desconto' => 'reais',
	'frete' => '',
	'valor_pago' => '',
	'forma_pgto' => '',
	'data' => date('Y-m-d'),
	'impressao_automatica' => @$impressao_automatica,
];

// Clicou em "Editar" em Vendas Detalhes: copia os itens da venda para o carrinho do usuário
if (@$_SESSION['modo_edicao_venda'] === true) {
	$dados = $_SESSION['dados_edicao_venda'];
	$id_venda_para_editar = $dados['id'];

	try {
		$pdo->beginTransaction();
		$pdo->prepare("DELETE FROM itens_venda WHERE id_venda = 0 AND funcionario = :f")->execute([':f' => $id_usuario_logado]);
		$pdo->prepare("INSERT INTO itens_venda (material, valor, quantidade, total, funcionario, id_venda, codigo)
			SELECT iv.material, iv.valor, iv.quantidade, iv.total, :f, 0, :codigo
			FROM itens_venda AS iv WHERE iv.id_venda_real = :origem")
			->execute([':f' => $id_usuario_logado, ':codigo' => session_id(), ':origem' => $id_venda_para_editar]);
		$pdo->commit();

		$_SESSION['carrinho_em_modo_edicao'] = true;
		$_SESSION['id_venda_em_edicao'] = $id_venda_para_editar;
	} catch (Exception $e) {
		$pdo->rollBack();
		die("Erro ao carregar a venda para edição: " . htmlspecialchars($e->getMessage()));
	}

	$pdv['id_edicao'] = $dados['id'];
	$pdv['pessoa'] = $dados['cliente_id'];
	$pdv['desconto'] = $dados['desconto'];
	$pdv['tipo_desconto'] = $dados['tipo_desconto'] == '%' ? '%' : 'reais';
	$pdv['frete'] = $dados['frete'];
	$pdv['valor_pago'] = number_format((float) $dados['valor_pago'], 2, ',', '.');
	$pdv['forma_pgto'] = $dados['forma_pagamento_id'];
	$pdv['data'] = date('Y-m-d', strtotime($dados['data_venda']));

	unset($_SESSION['modo_edicao_venda'], $_SESSION['dados_edicao_venda']);
}

require __DIR__ . '/pdv_diversos/tela.php';
