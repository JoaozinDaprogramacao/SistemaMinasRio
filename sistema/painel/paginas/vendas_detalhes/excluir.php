<?php
require_once("../../../conexao.php");
require_once("../vendas/funcoes_venda.php");

@session_start();
$id_usuario = @$_SESSION['id'];
$nivel = @$_SESSION['nivel'];

// "Excluir" uma venda é cancelá-la: o histórico fica, estoque e financeiro são desfeitos
$erro = venda_cancelar($pdo, intval($_POST['id'] ?? 0), $id_usuario, $nivel);

echo ($erro == '') ? 'Excluído com Sucesso' : $erro;
