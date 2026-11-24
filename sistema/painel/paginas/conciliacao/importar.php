<?php
require_once("../../../conexao.php");
require_once(__DIR__ . "/../../seguranca.php");
seguranca_exigir_acesso(['conciliacao']);
require_once(__DIR__ . "/../../funcoes/financeiro.php");

fin_responder(function () use ($pdo) {
	$id_banco = (int) ($_POST['id_banco'] ?? 0);
	if (empty($_FILES['arquivo']['tmp_name']) || $_FILES['arquivo']['error'] !== UPLOAD_ERR_OK) return 'Selecione o arquivo OFX.';
	if ($_FILES['arquivo']['size'] > 5 * 1024 * 1024) return 'Arquivo muito grande (máximo 5 MB).';
	$conteudo = file_get_contents($_FILES['arquivo']['tmp_name']);
	$nome = basename($_FILES['arquivo']['name']);
	$r = fin_importar_ofx($pdo, $id_banco, $conteudo, $nome);

	$msg = "Importado: {$r['lidos']} lançamentos lidos, {$r['novos']} novos, {$r['conciliados']} conciliados automaticamente.";
	// Saldo informado pelo banco no arquivo × saldo do sistema na mesma data
	if (preg_match('/<LEDGERBAL>.*?<BALAMT>\s*([-\d.,]+).*?<DTASOF>\s*(\d{8})/si', $conteudo, $m)) {
		$saldo_banco = round((float) str_replace(',', '.', $m[1]), 2);
		$data = substr($m[2], 0, 4) . '-' . substr($m[2], 4, 2) . '-' . substr($m[2], 6, 2);
		$saldo_sis = fin_saldo($pdo, $id_banco, $data);
		$dif = round($saldo_banco - $saldo_sis, 2);
		$msg .= ' Saldo do banco em ' . fin_data_br($data) . ': ' . fin_moeda($saldo_banco) . ' | no sistema: ' . fin_moeda($saldo_sis) .
			($dif == 0 ? ' — saldos batem.' : ' — diferença de ' . fin_moeda($dif) . '.');
	}
	return $msg;
});
