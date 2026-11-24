<?php 
$tabela = 'baldeio';
require_once("../../../conexao.php");
require_once(__DIR__ . "/../../seguranca.php");
require_once(__DIR__ . "/../../funcoes/financeiro.php");
seguranca_exigir_acesso(['bancos']);

$id = (int) $_POST['id'];

fin_responder(function () use ($pdo, $id) {
	return fin_transacao($pdo, function () use ($pdo, $id) {
		foreach (fin_q($pdo, "SELECT id FROM pagar WHERE id_baldeio = ?", [$id])->fetchAll(PDO::FETCH_COLUMN) as $id_pagar) {
			fin_excluir_titulo($pdo, 'pagar', $id_pagar, 'baldeio #' . $id . ' excluído');
		}
		fin_q($pdo, "DELETE FROM baldeio WHERE id = ?", [$id]);
		return 'Excluído com Sucesso';
	});
});
?>