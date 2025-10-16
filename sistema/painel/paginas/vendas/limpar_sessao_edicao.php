<?php
@session_start();

// Este script tem uma única finalidade: remover a "etiqueta"
// que marca o carrinho como estando em modo de edição.
unset($_SESSION['carrinho_em_modo_edicao']);
?>