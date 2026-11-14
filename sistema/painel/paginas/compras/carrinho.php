<?php
require_once("../../../conexao.php");
require_once(__DIR__ . "/../../seguranca.php");
seguranca_exigir_acesso(['compras']);
require_once(__DIR__ . "/../vendas/funcoes_venda.php");
require_once(__DIR__ . "/../pdv_diversos/carrinho_comum.php");

// Compra não tem limite de estoque; ele só sobe ao fechar a compra.
pdv_carrinho_executar($pdo, [
	'tabela' => 'itens_compra',
	'coluna_pai' => 'id_compra',
]);
