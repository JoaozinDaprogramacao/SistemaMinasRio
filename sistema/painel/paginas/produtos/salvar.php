<?php
$tabela = 'produtos';
require_once("../../../conexao.php");
require_once("../../verificar.php");

$nome = trim($_POST['nome'] ?? '');
$categoria = intval($_POST['categoria'] ?? 0);
$obs = trim($_POST['obs'] ?? '');
$id = intval($_POST['id'] ?? 0);

if($nome == ""){
	echo 'Informe o nome!';
	exit();
}

//validacao
$query = $pdo->prepare("SELECT id from $tabela where nome = :nome and categoria = :categoria and id <> :id");
$query->execute([':nome' => $nome, ':categoria' => $categoria, ':id' => $id]);
if($query->fetchColumn()){
	echo 'Produto já Cadastrado!';
	exit();
}

if($id == 0){
$query = $pdo->prepare("INSERT INTO $tabela SET nome = :nome, categoria = :categoria, obs = :obs, vendas = 0, ativo = 'Sim'");

}else{
$query = $pdo->prepare("UPDATE $tabela SET nome = :nome, categoria = :categoria, obs = :obs where id = :id");
$query->bindValue(":id", $id);
}
$query->bindValue(":nome", $nome);
$query->bindValue(":categoria", $categoria);
$query->bindValue(":obs", $obs);
$query->execute();

echo 'Salvo com Sucesso';
