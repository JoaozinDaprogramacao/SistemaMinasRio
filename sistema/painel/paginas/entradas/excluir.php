<?php
$tabela = 'entradas';
require_once("../../../conexao.php");
require_once(__DIR__ . "/../../seguranca.php");
seguranca_exigir_acesso(['entradas']);

$id = (int) ($_POST['id'] ?? 0);

try {
	$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
	$pdo->beginTransaction();

	$stmt = $pdo->prepare("SELECT material, quantidade FROM $tabela WHERE id = ? FOR UPDATE");
	$stmt->execute([$id]);
	$entrada = $stmt->fetch(PDO::FETCH_ASSOC);
	if (!$entrada) {
		throw new Exception('Entrada não encontrada!');
	}

	// A entrada manual tinha somado no estoque do material: retira
	$pdo->prepare("UPDATE materiais SET estoque = estoque - ? WHERE id = ?")
		->execute([(float) $entrada['quantidade'], (int) $entrada['material']]);
	$pdo->prepare("DELETE FROM $tabela WHERE id = ?")->execute([$id]);

	$pdo->commit();
	echo 'Excluído com Sucesso';
} catch (Exception $e) {
	if ($pdo->inTransaction()) $pdo->rollBack();
	echo 'Erro ao excluir: ' . $e->getMessage();
}
