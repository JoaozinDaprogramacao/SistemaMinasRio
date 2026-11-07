<?php 
require_once("../../../conexao.php");
require_once(__DIR__ . "/../../seguranca.php");
seguranca_exigir_acesso(['comissoes']);
$tabela = 'pagar';
@session_start();
$id_usuario = $_SESSION['id'];

//verificar caixa aberto
$query1 = $pdo->query("SELECT * from caixas where operador = '$id_usuario' and data_fechamento is null order by id desc limit 1");
$res1 = $query1->fetchAll(PDO::FETCH_ASSOC);
if(@count($res1) > 0){
	$id_caixa = @$res1[0]['id'];
}else{
	$id_caixa = 0;
}
// 

$id = $_POST['id'];

$pdo->query("UPDATE $tabela SET pago = 'Sim', usuario_pgto = '$id_usuario', data_pgto = curDate(), caixa = '$id_caixa' where id = '$id'");

echo 'Baixado com Sucesso';
 ?>