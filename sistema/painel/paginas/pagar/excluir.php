<?php 
$tabela = 'pagar';
require_once("../../../conexao.php");

$id = $_POST['id'];

$query = $pdo->query("SELECT * FROM $tabela where id = '$id'");
$res = $query->fetchAll(PDO::FETCH_ASSOC);
$foto = @$res[0]['arquivo'];
$hash = @$res[0]['hash'];

// Lançamento gerado por romaneio só pode ser alterado/excluído pelo próprio romaneio
if(@$res[0]['id_romaneio'] > 0){
	echo 'Este lançamento veio de um romaneio. Exclua ou edite pelo romaneio.';
	exit();
}

if($foto != "sem-foto.png"){
	@unlink('../../images/contas/'.$foto);
}

if($hash != ""){
	require("../../apis/cancelar_agendamento.php");
}

$pdo->query("DELETE FROM $tabela WHERE id = '$id' ");
echo 'Excluído com Sucesso';
?>