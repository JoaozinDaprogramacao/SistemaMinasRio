<?php
require_once("../../../conexao.php");
require_once(__DIR__ . "/../../seguranca.php");
require_once(__DIR__ . "/../../funcoes/financeiro.php");
require_once(__DIR__ . "/config.php");

fin_responder(function () use ($pdo) {
	$c = cadfin_config($_POST['e'] ?? '');
	seguranca_exigir_acesso([$c['permissao']]);
	$id = (int) ($_POST['id'] ?? 0);
	$nome = trim($_POST['nome'] ?? '');
	if ($nome === '') return 'Informe o nome.';
	$dup = fin_q($pdo, "SELECT id FROM {$c['tabela']} WHERE nome = ? AND id <> ?", [$nome, $id])->fetchColumn();
	if ($dup) return 'Já existe um cadastro com esse nome.';
	$campos = ['nome' => $nome];
	if ($c['grupos']) {
		$g = $_POST['grupo_dre'] ?? '';
		if (!isset($c['grupos'][$g])) return 'Escolha o grupo da DRE.';
		$campos['grupo_dre'] = $g;
	}
	if (fin_col_existe($pdo, $c['tabela'], 'ativo')) $campos['ativo'] = (int) ($_POST['ativo'] ?? 1) ? 1 : 0;
	if ($id && fin_col_existe($pdo, $c['tabela'], 'protegida')) {
		$atual = fin_q($pdo, "SELECT * FROM {$c['tabela']} WHERE id = ?", [$id])->fetch(PDO::FETCH_ASSOC);
		if ($atual && $atual['protegida'] && $atual['nome'] !== $nome) return 'Esta categoria é usada pelo sistema e não pode ser renomeada.';
	}
	$sets = implode(', ', array_map(fn($k) => "`$k` = ?", array_keys($campos)));
	if ($id) fin_q($pdo, "UPDATE {$c['tabela']} SET $sets WHERE id = ?", array_merge(array_values($campos), [$id]));
	else fin_q($pdo, "INSERT INTO {$c['tabela']} SET $sets", array_values($campos));
	fin_log($pdo, 'cadastro', $c['tabela'], $id ?: (int) $pdo->lastInsertId(), 'Salvou "' . $nome . '" em ' . $c['titulo']);
	return 'Salvo com Sucesso';
});
