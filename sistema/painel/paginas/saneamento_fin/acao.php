<?php
require_once("../../../conexao.php");
require_once(__DIR__ . "/../../seguranca.php");
seguranca_exigir_login();
require_once(__DIR__ . "/../vendas/funcoes_venda.php");
require_once(__DIR__ . "/../../funcoes/financeiro_saneamento.php");

fin_responder(function () use ($pdo) {
	if (@$_SESSION['nivel'] != 'Administrador') return 'Somente o administrador pode executar o saneamento.';
	switch ($_POST['acao'] ?? '') {
		case 'automatico':
			$r = san_executar_automatico($pdo);
			return 'Correções concluídas:<br>' .
				"• Baixas antigas registradas: {$r['antigos']['titulos']} ({$r['antigos']['vinculados']} ligadas ao extrato, {$r['antigos']['sem_conta']} sem conta)<br>" .
				"• Resíduos convertidos: {$r['residuos']['residuos']} ({$r['residuos']['vinculados']} ligados ao extrato, {$r['residuos']['sem_conta']} sem conta)<br>" .
				"• Recebimentos no extrato: {$r['recebimentos']['lancados']} lançados (" . fin_moeda($r['recebimentos']['total']) . "), {$r['recebimentos']['vinculados']} já existiam e foram ligados<br>" .
				"• Situações corrigidas: {$r['status']}";
		case 'conta':
			san_definir_conta($pdo, $_POST['tipo'] === 'pagar' ? 'pagar' : 'receber', (int) $_POST['id'], (int) $_POST['banco']);
			return 'Lançado no extrato.';
		case 'contas_lote':
			$itens = json_decode($_POST['itens'] ?? '[]', true) ?: [];
			fin_transacao($pdo, function () use ($pdo, $itens) {
				foreach ($itens as $i) san_definir_conta($pdo, $i['tipo'] === 'pagar' ? 'pagar' : 'receber', (int) $i['id'], (int) $i['banco']);
			});
			return count($itens) . ' pagamento(s) lançados no extrato.';
		case 'remover_dobro':
			san_remover_dobro($pdo, (int) $_POST['id']);
			return 'Pagamento em dobro removido do extrato.';
		case 'excluir_linha':
			fin_excluir_lancamento($pdo, (int) $_POST['id']);
			return 'Linha removida do extrato.';
		case 'manter_linha':
			san_manter_linha($pdo, (int) $_POST['id']);
			return 'Mantida como lançamento avulso.';
		case 'manter_dobro':
			san_manter_dobro($pdo, (int) $_POST['id']);
			return 'Mantido. O caso saiu da lista.';
	}
	return 'Ação inválida.';
});
