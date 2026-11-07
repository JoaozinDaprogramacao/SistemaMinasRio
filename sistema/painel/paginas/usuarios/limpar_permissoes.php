<?php 
require_once("../../../conexao.php");
require_once(__DIR__ . "/../../seguranca.php");
seguranca_exigir_acesso(['usuarios']);

$id_usuario = $_POST['id_usuario'];

$pdo->query("DELETE FROM usuarios_permissoes where usuario = '$id_usuario'");

?>