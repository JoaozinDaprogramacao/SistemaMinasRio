<?php
require_once("../../../conexao.php");
require_once(__DIR__ . "/../../seguranca.php");
seguranca_exigir_acesso(['vendas']);
require_once("funcoes_venda.php");
require_once(__DIR__ . "/../pdv_diversos/carrinho_comum.php");

pdv_carrinho_executar($pdo, [
	'tabela' => 'itens_venda',
	'coluna_pai' => 'id_venda',
	// O estoque só é abatido ao fechar a venda; aqui apenas confere.
	// Na edição, o que a venda já reservou volta ao estoque ao salvar, então conta como disponível.
	'disponivel' => function ($pdo, $material) {
		if ($material['tem_estoque'] != 'Sim') return null;
		return (float) $material['estoque'] + venda_qtd_em_edicao($pdo, @$_SESSION['id_venda_em_edicao'], $material['id']);
	},
	'ao_limpar' => function () {
		unset($_SESSION['carrinho_em_modo_edicao'], $_SESSION['id_venda_em_edicao']);
	},
]);
