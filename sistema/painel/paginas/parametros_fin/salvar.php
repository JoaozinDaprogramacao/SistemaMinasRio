<?php
require_once("../../../conexao.php");
require_once(__DIR__ . "/../../seguranca.php");
seguranca_exigir_acesso(['parametros_fin']);
require_once(__DIR__ . "/../../funcoes/financeiro.php");

fin_responder(function () use ($pdo) {
	$cfg = fin_q($pdo, "SELECT * FROM config LIMIT 1")->fetch(PDO::FETCH_ASSOC);
	$bloqueio = $_POST['data_bloqueio_fin'] ?? '';
	if ($bloqueio !== '' && !fin_data_valida($bloqueio)) return 'Data de bloqueio inválida.';
	$bloqueio = $bloqueio ?: null;
	if ($bloqueio != ($cfg['data_bloqueio_fin'] ?? null) && @$_SESSION['nivel'] != 'Administrador') {
		return 'Somente o administrador pode alterar o fechamento de período.';
	}
	$pix = trim($_POST['pix_chave'] ?? '');
	return fin_transacao($pdo, function () use ($pdo, $cfg, $bloqueio, $pix) {
		fin_q($pdo, "UPDATE config SET data_bloqueio_fin = ?, pix_chave = ?, pix_nome = ?, pix_cidade = ? WHERE id = ?", [
			$bloqueio, $pix ?: null, mb_substr(trim($_POST['pix_nome'] ?? ''), 0, 25) ?: null, mb_substr(trim($_POST['pix_cidade'] ?? ''), 0, 15) ?: null, $cfg['id'],
		]);
		foreach ((array) ($_POST['formas'] ?? []) as $id => $f) {
			$banco = (int) ($f['banco'] ?? 0);
			fin_q($pdo, "UPDATE formas_pgto SET banco_padrao = ?, prazo_recebimento = ?, taxa = ? WHERE id = ?", [
				$banco && fin_banco_existe($pdo, $banco) ? $banco : null, max(0, min(365, (int) ($f['prazo'] ?? 0))), max(0, fin_num($f['taxa'] ?? 0)), (int) $id,
			]);
		}
		if ($bloqueio != ($cfg['data_bloqueio_fin'] ?? null)) {
			fin_log($pdo, 'fechamento_periodo', 'config', $cfg['id'], $bloqueio ? 'Fechou o período até ' . fin_data_br($bloqueio) : 'Reabriu todos os períodos');
		}
		fin_log($pdo, 'parametros', 'config', $cfg['id'], 'Alterou parâmetros financeiros (PIX / formas de pagamento)');
		return 'Salvo com Sucesso';
	});
});
