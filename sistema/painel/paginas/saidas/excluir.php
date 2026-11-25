<?php
$tabela = 'saidas';
require_once("../../../conexao.php");
require_once(__DIR__ . "/../../seguranca.php");
seguranca_exigir_acesso(['saidas']);

$id = (int) ($_POST['id'] ?? 0);

try {
	$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
	$pdo->beginTransaction();

	$stmt = $pdo->prepare("SELECT material, quantidade FROM $tabela WHERE id = ? FOR UPDATE");
	$stmt->execute([$id]);
	$saida = $stmt->fetch(PDO::FETCH_ASSOC);
	if (!$saida) {
		throw new Exception('Saída não encontrada!');
	}

	// A saída manual tinha abatido do estoque do material: devolve
	$pdo->prepare("UPDATE materiais SET estoque = estoque + ? WHERE id = ?")
		->execute([(float) $saida['quantidade'], (int) $saida['material']]);
	$pdo->prepare("DELETE FROM $tabela WHERE id = ?")->execute([$id]);

	$pdo->commit();
	echo 'Excluído com Sucesso';
} catch (Exception $e) {
	if ($pdo->inTransaction()) $pdo->rollBack();
	echo 'Erro ao excluir: ' . $e->getMessage();
}
