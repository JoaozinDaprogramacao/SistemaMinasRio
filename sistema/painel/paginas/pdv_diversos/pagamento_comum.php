<?php
// Parcelas do restante (o que não foi pago na hora) nas telas Vendas e Compras.
// A tela manda só as datas em parcela_venc[]; os valores são divididos aqui, em centavos,
// com a diferença do arredondamento na última parcela (o js/pdv_diversos.js faz a mesma conta).

const PDV_MAX_PARCELAS = 24;

function pdv_data_valida($v)
{
	$v = (string) $v;
	if (!preg_match('/^(\d{4})-(\d{2})-(\d{2})$/', $v, $m)) return false;
	return checkdate((int) $m[2], (int) $m[3], (int) $m[1]);
}

// Retorna [['vencimento' => 'Y-m-d', 'valor' => float], ...] ou uma string com o erro.
function pdv_parcelas($restante, $data_base)
{
	$datas = [];
	foreach ((array) ($_POST['parcela_venc'] ?? []) as $d) {
		if (trim((string) $d) === '') continue;
		if (!pdv_data_valida($d)) return 'Há um vencimento de parcela inválido.';
		$datas[] = $d;
	}
	// Telas antigas mandavam um único vencimento do restante
	if (!$datas) {
		$antigo = $_POST['data_restante'] ?? '';
		$datas[] = pdv_data_valida($antigo) ? $antigo : date('Y-m-d', strtotime($data_base . ' +30 days'));
	}
	if (count($datas) > PDV_MAX_PARCELAS) return 'No máximo ' . PDV_MAX_PARCELAS . ' parcelas.';
	foreach ($datas as $d) {
		if ($d < $data_base) return 'O vencimento de uma parcela é anterior à data da operação.';
	}

	$n = count($datas);
	$centavos = (int) round($restante * 100);
	$base = intdiv($centavos, $n);
	$parcelas = [];
	foreach ($datas as $i => $d) {
		$c = ($i == $n - 1) ? $centavos - $base * ($n - 1) : $base;
		$parcelas[] = ['vencimento' => $d, 'valor' => $c / 100];
	}
	return $parcelas;
}

function pdv_descricao_parcela($prefixo, $i, $n, $tem_entrada)
{
	if ($n > 1) return $prefixo . ' (Parcela ' . ($i + 1) . '/' . $n . ')';
	return $prefixo . ($tem_entrada ? ' (Restante)' : '');
}
