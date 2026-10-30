<?php
require_once("../../../conexao.php");

@session_start();
$id_usuario = $_SESSION['id'];

// O estoque só é abatido ao fechar a venda, então esvaziar o carrinho não devolve nada
$pdo->prepare("DELETE FROM itens_venda WHERE funcionario = :f AND id_venda = 0")->execute([':f' => $id_usuario]);
unset($_SESSION['carrinho_em_modo_edicao'], $_SESSION['id_venda_em_edicao']);

echo 'Limpo com Sucesso';
