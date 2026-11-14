<?php
// Tela do PDV de diversos, incluída por vendas.php e compras.php com $pdv já montado.
if (!isset($pdv)) {
	exit();
}

$eh_venda = $pdv['tipo'] == 'venda';
$fmt_moeda = function ($v) {
	return ($v === '' || $v === null || (float) $v == 0) ? '' : number_format((float) $v, 2, ',', '.');
};

// Materiais com o último preço praticado, para já sugerir no lançamento
$tabela_itens = $eh_venda ? 'itens_venda' : 'itens_compra';
$item_fechado = $eh_venda ? 'i.id_venda_real IS NOT NULL' : 'i.id_compra > 0';
$materiais = $pdo->query("SELECT m.id, m.nome, m.tem_estoque, m.estoque,
		(SELECT i.valor FROM $tabela_itens i WHERE i.material = m.id AND $item_fechado AND i.valor > 0 ORDER BY i.id DESC LIMIT 1) AS ultimo_preco
	FROM materiais m ORDER BY m.nome ASC")->fetchAll(PDO::FETCH_ASSOC);

if ($eh_venda) {
	$pessoas = $pdo->query("SELECT id, nome, cpf AS doc FROM clientes ORDER BY nome ASC")->fetchAll(PDO::FETCH_ASSOC);
} else {
	$pessoas = $pdo->query("SELECT id, nome_atacadista AS nome, COALESCE(NULLIF(cnpj, ''), cpf) AS doc FROM fornecedores ORDER BY nome_atacadista ASC")->fetchAll(PDO::FETCH_ASSOC);
}
$formas = $pdo->query("SELECT id, nome FROM formas_pgto ORDER BY id ASC")->fetchAll(PDO::FETCH_ASSOC);

$h = function ($v) {
	return htmlspecialchars((string) $v, ENT_QUOTES, 'UTF-8');
};
$em_edicao = !empty($pdv['id_edicao']);
$rotulo_pessoa = $eh_venda ? 'Cliente' : 'Fornecedor';
$rotulo_op = $eh_venda ? 'venda' : 'compra';
?>

<style>
	.pdv {
		--pdv-borda: #e3e6ec;
		--pdv-suave: #f6f7f9;
		--pdv-texto-2: #6b7280;
		--pdv-destaque: var(--primary-bg-color, #0d6efd);
		--pdv-ok: #198754;
		--pdv-erro: #dc3545;
		display: grid;
		grid-template-columns: minmax(0, 1fr);
		gap: 16px;
		margin-top: 15px;
		padding-bottom: 72px;
	}

	.pdv-cartao {
		background: #fff;
		border: 1px solid var(--pdv-borda);
		border-radius: 10px;
	}

	/* ---------- Catálogo ---------- */
	.pdv-catalogo {
		padding: 14px;
		min-width: 0;
	}

	.pdv-busca {
		position: relative;
		margin-bottom: 12px;
	}

	.pdv-busca .fa {
		position: absolute;
		left: 12px;
		top: 50%;
		transform: translateY(-50%);
		color: var(--pdv-texto-2);
	}

	.pdv-busca input {
		padding-left: 36px;
		height: 44px;
		font-size: 15px;
	}

	.pdv-busca kbd {
		position: absolute;
		right: 10px;
		top: 50%;
		transform: translateY(-50%);
		font-size: 11px;
		background: var(--pdv-suave);
		color: var(--pdv-texto-2);
		border: 1px solid var(--pdv-borda);
	}

	.pdv-grid {
		display: grid;
		grid-template-columns: repeat(auto-fill, minmax(150px, 1fr));
		gap: 10px;
	}

	.pdv-mat {
		display: flex;
		flex-direction: column;
		gap: 4px;
		text-align: left;
		padding: 12px;
		min-height: 84px;
		background: #fff;
		border: 1px solid var(--pdv-borda);
		border-radius: 8px;
		cursor: pointer;
		transition: border-color .15s, box-shadow .15s;
		position: relative;
	}

	.pdv-mat:hover,
	.pdv-mat:focus-visible {
		border-color: var(--pdv-destaque);
		box-shadow: 0 2px 8px rgba(0, 0, 0, .06);
		outline: none;
	}

	.pdv-mat-nome {
		font-weight: 600;
		font-size: 13px;
		color: #1f2937;
		line-height: 1.25;
	}

	.pdv-mat-info {
		font-size: 12px;
		color: var(--pdv-texto-2);
		margin-top: auto;
	}

	.pdv-mat-info .negativo {
		color: var(--pdv-erro);
		font-weight: 600;
	}

	.pdv-mat.no-carrinho {
		border-color: var(--pdv-ok);
		background: #f3fbf6;
	}

	.pdv-mat-badge {
		display: none;
		position: absolute;
		top: 8px;
		right: 8px;
		font-size: 11px;
		font-weight: 600;
		color: #fff;
		background: var(--pdv-ok);
		border-radius: 10px;
		padding: 1px 7px;
	}

	.pdv-mat.no-carrinho .pdv-mat-badge {
		display: inline-block;
	}

	.pdv-mat.no-carrinho .pdv-mat-nome {
		padding-right: 48px;
	}

	.pdv-sem-resultado {
		display: none;
		text-align: center;
		color: var(--pdv-texto-2);
		padding: 30px 0;
	}

	/* ---------- Painel (carrinho + pagamento) ---------- */
	.pdv-painel {
		display: flex;
		flex-direction: column;
		min-width: 0;
	}

	.pdv-painel>section {
		padding: 14px;
		border-bottom: 1px solid var(--pdv-borda);
	}

	.pdv-painel>section:last-child {
		border-bottom: 0;
	}

	.pdv-titulo {
		display: flex;
		align-items: center;
		justify-content: space-between;
		gap: 8px;
	}

	.pdv-titulo h5 {
		margin: 0;
		font-size: 16px;
		font-weight: 700;
	}

	.pdv-titulo.edicao h5 {
		color: #b45309;
	}

	.pdv-label {
		display: block;
		font-size: 12px;
		font-weight: 600;
		color: var(--pdv-texto-2);
		margin-bottom: 4px;
	}

	.pdv-pessoa {
		display: flex;
		gap: 6px;
	}

	.pdv-pessoa .select2-container {
		flex: 1;
		min-width: 0;
	}

	.pdv-pessoa .select2-container .select2-selection--single {
		height: 38px;
		display: flex;
		align-items: center;
	}

	.pdv-pessoa .select2-container .select2-selection__arrow {
		height: 36px;
	}

	.pdv-pessoa .select2-container .select2-selection__rendered {
		flex: 1;
		min-width: 0;
		border: 0;
	}

	.pdv-carrinho {
		flex: 1 1 auto;
		min-height: 170px;
		overflow-y: auto;
	}

	.pdv-carrinho-cab {
		display: flex;
		justify-content: space-between;
		align-items: baseline;
		margin-bottom: 6px;
	}

	.pdv-vazio {
		text-align: center;
		color: var(--pdv-texto-2);
		padding: 22px 8px;
		font-size: 13px;
	}

	.pdv-vazio .fa {
		font-size: 26px;
		display: block;
		margin-bottom: 8px;
		opacity: .5;
	}

	.pdv-item {
		padding: 10px 0;
		border-bottom: 1px dashed var(--pdv-borda);
	}

	.pdv-item:last-child {
		border-bottom: 0;
	}

	.pdv-item.flash {
		animation: pdvFlash 1.2s ease-out;
	}

	@keyframes pdvFlash {
		from {
			background: #e8f5ee;
		}

		to {
			background: transparent;
		}
	}

	.pdv-item-topo {
		display: flex;
		justify-content: space-between;
		align-items: flex-start;
		gap: 8px;
	}

	.pdv-item-nome {
		font-weight: 600;
		font-size: 13px;
	}

	.pdv-item-remover {
		border: 0;
		background: none;
		color: var(--pdv-texto-2);
		padding: 2px 6px;
		border-radius: 6px;
		line-height: 1;
	}

	.pdv-item-remover:hover,
	.pdv-item-remover:focus-visible {
		color: var(--pdv-erro);
		background: #fdecee;
	}

	.pdv-item-campos {
		display: grid;
		grid-template-columns: 78px auto 104px 1fr;
		align-items: end;
		gap: 6px;
		margin-top: 6px;
	}

	.pdv-mini span {
		display: block;
		font-size: 11px;
		color: var(--pdv-texto-2);
		margin-bottom: 2px;
	}

	.pdv-mini input {
		height: 34px;
		text-align: right;
		font-size: 14px;
	}

	.pdv-x {
		padding-bottom: 8px;
		color: var(--pdv-texto-2);
	}

	.pdv-item-total {
		text-align: right;
		font-weight: 700;
		font-size: 14px;
		padding-bottom: 7px;
		white-space: nowrap;
	}

	.pdv-item .is-invalid {
		border-color: var(--pdv-erro);
		background: #fff5f5;
	}

	.pdv-item.salvando .pdv-item-total {
		opacity: .5;
	}

	.pdv-seg {
		display: inline-flex;
		border: 1px solid var(--pdv-borda);
		border-radius: 6px;
		overflow: hidden;
		vertical-align: middle;
		margin-left: 4px;
	}

	.pdv-seg button {
		border: 0;
		background: #fff;
		font-size: 11px;
		font-weight: 600;
		padding: 1px 8px;
		color: var(--pdv-texto-2);
	}

	.pdv-seg button.ativo {
		background: var(--pdv-destaque);
		color: #fff;
	}

	.pdv-resumo {
		font-size: 13px;
	}

	.pdv-linha {
		display: flex;
		justify-content: space-between;
		padding: 2px 0;
	}

	.pdv-linha.desconto {
		color: var(--pdv-erro);
	}

	.pdv-total {
		display: flex;
		justify-content: space-between;
		align-items: baseline;
		margin-top: 6px;
		padding-top: 8px;
		border-top: 1px solid var(--pdv-borda);
	}

	.pdv-total span:first-child {
		font-weight: 600;
	}

	.pdv-total strong {
		font-size: 24px;
		font-weight: 800;
	}

	.pdv-aviso {
		font-size: 12px;
		margin-top: 4px;
	}

	.pdv-troco {
		color: var(--pdv-ok);
		font-weight: 600;
	}

	.pdv-restante {
		margin-top: 10px;
		padding: 10px;
		border-radius: 8px;
		background: #fff8e6;
		border: 1px solid #f5d98b;
	}

	.pdv-restante-titulo {
		font-size: 13px;
		margin-bottom: 8px;
	}

	.pdv-link {
		font-size: 11px;
		font-weight: 600;
		background: none;
		border: 0;
		padding: 0;
		color: var(--pdv-destaque);
		text-decoration: underline;
	}

	.pdv-acoes .btn-fechar {
		width: 100%;
		height: 50px;
		font-size: 16px;
		font-weight: 700;
	}

	.pdv-acoes .rodape {
		display: flex;
		justify-content: space-between;
		align-items: center;
		margin-top: 8px;
		font-size: 12px;
		color: var(--pdv-texto-2);
	}

	.pdv-acoes .btn-limpar {
		border: 0;
		background: none;
		color: var(--pdv-texto-2);
		font-size: 12px;
		padding: 0;
		text-decoration: underline;
	}

	.pdv-acoes .btn-limpar:hover {
		color: var(--pdv-erro);
	}

	/* Barra fixa no celular: total sempre à vista */
	.pdv-barra {
		position: fixed;
		left: 0;
		right: 0;
		bottom: 0;
		z-index: 1030;
		display: flex;
		align-items: center;
		justify-content: space-between;
		gap: 12px;
		padding: 10px 16px;
		background: #fff;
		border-top: 1px solid var(--pdv-borda);
		box-shadow: 0 -4px 12px rgba(0, 0, 0, .06);
	}

	.pdv-barra small {
		display: block;
		color: var(--pdv-texto-2);
	}

	.pdv-barra strong {
		font-size: 18px;
	}

	/* Modal de quantidade */
	#pdv_modal_item .pdv-mi-qtd {
		height: 52px;
		font-size: 22px;
		font-weight: 700;
		text-align: right;
	}

	#pdv_modal_item .pdv-mi-preco {
		height: 44px;
		font-size: 17px;
		text-align: right;
	}

	#pdv_modal_item .pdv-mi-total {
		font-size: 20px;
		font-weight: 800;
	}

	@media (min-width: 992px) {
		.pdv {
			grid-template-columns: minmax(0, 1fr) 410px;
			align-items: start;
			padding-bottom: 0;
		}

		.pdv-painel {
			position: sticky;
			/* cabeçalho fixo do painel tem 64px */
			top: 79px;
			max-height: calc(100vh - 94px);
			overflow-y: auto;
		}

		/* Em tela baixa o painel rola, mas o botão de fechar fica sempre à vista */
		.pdv-carrinho {
			flex: 0 0 auto;
			max-height: 42vh;
		}

		.pdv-acoes {
			position: sticky;
			bottom: 0;
			z-index: 2;
			background: #fff;
			border-radius: 0 0 10px 10px;
			box-shadow: 0 -6px 10px -8px rgba(0, 0, 0, .15);
		}

		.dark-theme .pdv-acoes {
			background: #1f2433;
		}

		.pdv-barra {
			display: none;
		}
	}

	@media (max-width: 420px) {
		.pdv-grid {
			grid-template-columns: repeat(2, minmax(0, 1fr));
		}

		.pdv-item-campos {
			grid-template-columns: 70px auto 96px 1fr;
		}
	}

	/* Tema escuro do painel */
	.dark-theme .pdv-cartao,
	.dark-theme .pdv-mat,
	.dark-theme .pdv-barra,
	.dark-theme .pdv-seg button:not(.ativo) {
		background: #1f2433;
		color: #e5e7eb;
	}

	.dark-theme .pdv {
		--pdv-borda: #333a4d;
		--pdv-suave: #262c3d;
		--pdv-texto-2: #9aa3b5;
	}

	.dark-theme .pdv-mat-nome {
		color: #e5e7eb;
	}

	.dark-theme .pdv-mat.no-carrinho {
		background: #1c3328;
	}

	.dark-theme .pdv-restante {
		background: #3a3220;
		border-color: #6b5a2a;
	}
</style>

<div class="pdv" id="pdv">

	<!-- Catálogo -->
	<div class="pdv-catalogo pdv-cartao">
		<div class="pdv-busca">
			<i class="fa fa-search"></i>
			<input type="search" class="form-control" id="pdv_busca" placeholder="Buscar material e Enter para lançar" autocomplete="off" aria-label="Buscar material">
			<kbd class="d-none d-lg-inline">F2</kbd>
		</div>

		<div class="pdv-grid" id="pdv_grid">
			<?php foreach ($materiais as $m) {
				$controla = $m['tem_estoque'] == 'Sim';
				$estoque = (float) $m['estoque'];
			?>
				<button type="button" class="pdv-mat"
					data-id="<?= (int) $m['id'] ?>"
					data-nome="<?= $h($m['nome']) ?>"
					data-controla="<?= $controla ? 1 : 0 ?>"
					data-estoque="<?= $estoque ?>"
					data-preco="<?= $m['ultimo_preco'] !== null ? (float) $m['ultimo_preco'] : '' ?>">
					<span class="pdv-mat-badge"></span>
					<span class="pdv-mat-nome"><?= $h($m['nome']) ?></span>
					<span class="pdv-mat-info">
						<?php if ($controla) { ?>
							Estoque: <span class="<?= $estoque <= 0 ? 'negativo' : '' ?>"><?= number_format($estoque, 0, ',', '.') ?></span>
						<?php } else { ?>
							Sem controle de estoque
						<?php } ?>
						<?php if ($m['ultimo_preco'] !== null) { ?>
							<br>Último: R$ <?= number_format((float) $m['ultimo_preco'], 2, ',', '.') ?>
						<?php } ?>
					</span>
				</button>
			<?php } ?>
		</div>
		<div class="pdv-sem-resultado" id="pdv_sem_resultado">Nenhum material encontrado.</div>
		<?php if (count($materiais) == 0) { ?>
			<p class="text-muted text-center my-4">Nenhum material cadastrado. Cadastre em <a href="materiais">Materiais</a>.</p>
		<?php } ?>
	</div>

	<!-- Carrinho e pagamento -->
	<form id="pdv_form" class="pdv-painel pdv-cartao" autocomplete="off" novalidate>
		<input type="hidden" name="id_venda_edicao" value="<?= (int) ($pdv['id_edicao'] ?? 0) ?>">
		<input type="hidden" name="tipo_desconto" id="tipo_desconto" value="<?= ($pdv['tipo_desconto'] ?? '') == '%' ? '%' : 'reais' ?>">
		<input type="hidden" name="troco" id="troco" value="">

		<section>
			<div class="pdv-titulo <?= $em_edicao ? 'edicao' : '' ?>">
				<h5><?= $em_edicao ? 'Editando venda #' . (int) $pdv['id_edicao'] : 'Nova ' . $rotulo_op ?></h5>
				<?php if ($em_edicao) { ?>
					<button type="button" class="pdv-link" id="pdv_cancelar_edicao">Cancelar edição</button>
				<?php } ?>
			</div>

			<label class="pdv-label mt-2" for="pdv_pessoa"><?= $rotulo_pessoa ?><?= $eh_venda ? '' : ' <span class="fw-normal">(opcional)</span>' ?></label>
			<div class="pdv-pessoa">
				<select id="pdv_pessoa" name="pessoa" style="width:100%">
					<option value=""></option>
					<?php foreach ($pessoas as $p) { ?>
						<option value="<?= (int) $p['id'] ?>" <?= (string) $p['id'] === (string) ($pdv['pessoa'] ?? '') ? 'selected' : '' ?>><?= $h(trim($p['nome'] . ($p['doc'] ? ' - ' . $p['doc'] : ''))) ?></option>
					<?php } ?>
				</select>
				<button type="button" class="btn btn-outline-primary" id="pdv_nova_pessoa" title="Cadastrar <?= strtolower($rotulo_pessoa) ?> (abre em nova aba)" aria-label="Cadastrar <?= strtolower($rotulo_pessoa) ?>"><i class="fa fa-user-plus"></i></button>
			</div>
		</section>

		<section class="pdv-carrinho">
			<div class="pdv-carrinho-cab">
				<span class="pdv-label mb-0">Itens</span>
				<span class="small text-muted" id="pdv_qtd_itens"></span>
			</div>
			<div id="pdv_itens">
				<div class="pdv-vazio"><i class="fa fa-spinner fa-spin"></i>Carregando…</div>
			</div>
		</section>

		<section>
			<div class="row g-2">
				<div class="col-7">
					<label class="pdv-label" for="saida">Forma de pagamento</label>
					<select class="form-select" name="saida" id="saida">
						<option value="">Selecione…</option>
						<?php foreach ($formas as $f) { ?>
							<option value="<?= (int) $f['id'] ?>" <?= (string) $f['id'] === (string) ($pdv['forma_pgto'] ?? '') ? 'selected' : '' ?>><?= $h($f['nome']) ?></option>
						<?php } ?>
					</select>
				</div>
				<div class="col-5">
					<label class="pdv-label" for="data2">Data</label>
					<input type="date" class="form-control" id="data2" name="data2" value="<?= $h($pdv['data'] ?? date('Y-m-d')) ?>">
				</div>
				<?php $tem_ajuste = (float) ($pdv['desconto'] ?? 0) > 0 || (float) ($pdv['frete'] ?? 0) > 0; ?>
				<div class="col-12" id="pdv_abrir_ajustes_box" <?= $tem_ajuste ? 'style="display:none"' : '' ?>>
					<button type="button" class="pdv-link" id="pdv_abrir_ajustes">+ Desconto ou frete</button>
				</div>
				<div class="col-6 pdv-ajuste" <?= $tem_ajuste ? '' : 'style="display:none"' ?>>
					<label class="pdv-label" for="desconto">Desconto
						<span class="pdv-seg" role="group" aria-label="Tipo de desconto">
							<button type="button" data-tipo="reais">R$</button><button type="button" data-tipo="%">%</button>
						</span>
					</label>
					<input type="text" inputmode="decimal" class="form-control text-end" id="desconto" name="desconto" placeholder="0,00" value="<?= $h($fmt_moeda($pdv['desconto'] ?? '')) ?>" data-formato="moeda">
				</div>
				<div class="col-6 pdv-ajuste" <?= $tem_ajuste ? '' : 'style="display:none"' ?>>
					<label class="pdv-label" for="frete">Frete (R$)</label>
					<input type="text" inputmode="decimal" class="form-control text-end" id="frete" name="frete" placeholder="0,00" value="<?= $h($fmt_moeda($pdv['frete'] ?? '')) ?>" data-formato="moeda">
				</div>
			</div>

			<div class="pdv-resumo mt-2">
				<div class="pdv-linha" id="pdv_linha_sub" style="display:none"><span>Subtotal</span><span id="pdv_subtotal">R$ 0,00</span></div>
				<div class="pdv-linha desconto" id="pdv_linha_desc" style="display:none"><span id="pdv_desc_rotulo">Desconto</span><span id="pdv_desc_valor"></span></div>
				<div class="pdv-linha" id="pdv_linha_frete" style="display:none"><span>Frete</span><span id="pdv_frete_valor"></span></div>
				<div class="pdv-aviso text-danger" id="pdv_desc_erro" style="display:none">O desconto é maior que o valor dos itens.</div>
				<div class="pdv-total"><span>Total</span><strong id="pdv_total">R$ 0,00</strong></div>
			</div>

			<div class="mt-3">
				<label class="pdv-label" for="valor_pago">
					<?= $eh_venda ? 'Valor recebido agora' : 'Valor pago agora' ?> (R$)
					<button type="button" class="pdv-link ms-1" id="pdv_usar_total" style="display:none">usar o total</button>
				</label>
				<input type="text" inputmode="decimal" class="form-control text-end" id="valor_pago" name="valor_pago" placeholder="0,00" value="<?= $h($pdv['valor_pago'] ?? '') ?>" data-formato="moeda" data-manual="<?= ($pdv['valor_pago'] ?? '') !== '' ? 1 : 0 ?>" style="font-weight:600">
				<div class="pdv-aviso pdv-troco" id="pdv_troco" style="display:none"></div>
				<div class="pdv-aviso text-muted" id="pdv_acima" style="display:none">Valor acima do total: será lançado só o total.</div>
			</div>

			<div class="pdv-restante" id="pdv_restante" style="display:none">
				<div class="pdv-restante-titulo">
					<?= $eh_venda ? 'Fica a receber' : 'Fica a pagar' ?>: <strong id="pdv_restante_valor">R$ 0,00</strong>
				</div>
				<div class="row g-2">
					<div class="col-7">
						<label class="pdv-label" for="forma_pgto2">Forma do restante</label>
						<select class="form-select form-select-sm" name="forma_pgto2" id="forma_pgto2">
							<option value="">Mesma forma</option>
							<?php foreach ($formas as $f) { ?>
								<option value="<?= (int) $f['id'] ?>"><?= $h($f['nome']) ?></option>
							<?php } ?>
						</select>
					</div>
					<div class="col-5">
						<label class="pdv-label" for="data_restante">Vencimento</label>
						<input type="date" class="form-control form-control-sm" name="data_restante" id="data_restante" value="<?= date('Y-m-d', strtotime('+30 days')) ?>">
					</div>
				</div>
			</div>
		</section>

		<section class="pdv-acoes">
			<button type="submit" class="btn btn-success btn-fechar" id="pdv_fechar" disabled>
				<?= $em_edicao ? 'Salvar alterações' : 'Fechar ' . $rotulo_op ?>
			</button>
			<div class="rodape">
				<span class="d-none d-lg-inline">Ctrl+Enter fecha a <?= $rotulo_op ?></span>
				<button type="button" class="btn-limpar" id="pdv_limpar">Limpar carrinho</button>
			</div>
		</section>
	</form>
</div>

<!-- Barra do celular -->
<div class="pdv-barra" id="pdv_barra">
	<div>
		<small id="pdv_barra_itens">Nenhum item</small>
		<strong id="pdv_barra_total">R$ 0,00</strong>
	</div>
	<button type="button" class="btn btn-success" id="pdv_ir_pagamento">Ir para pagamento <i class="fa fa-arrow-down ms-1"></i></button>
</div>

<!-- Lançar material -->
<div class="modal fade" id="pdv_modal_item" tabindex="-1" aria-labelledby="pdv_mi_titulo" aria-hidden="true">
	<div class="modal-dialog modal-dialog-centered" style="max-width: 420px">
		<form class="modal-content" id="pdv_mi_form" autocomplete="off" novalidate>
			<div class="modal-header">
				<h5 class="modal-title" id="pdv_mi_titulo">Material</h5>
				<button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fechar"></button>
			</div>
			<div class="modal-body">
				<div class="small text-muted mb-3" id="pdv_mi_info"></div>
				<div class="mb-3">
					<label class="pdv-label" for="pdv_mi_qtd">Quantidade</label>
					<input type="text" inputmode="decimal" class="form-control pdv-mi-qtd" id="pdv_mi_qtd" placeholder="0">
					<div class="pdv-aviso" id="pdv_mi_aviso"></div>
				</div>
				<div class="mb-3">
					<label class="pdv-label" for="pdv_mi_preco">Preço unitário (R$)</label>
					<input type="text" inputmode="decimal" class="form-control pdv-mi-preco" id="pdv_mi_preco" placeholder="0,00">
				</div>
				<div class="d-flex justify-content-between align-items-baseline">
					<span class="text-muted">Total do item</span>
					<span class="pdv-mi-total" id="pdv_mi_total">R$ 0,00</span>
				</div>
			</div>
			<div class="modal-footer">
				<button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancelar</button>
				<button type="submit" class="btn btn-primary" id="pdv_mi_ok">Adicionar <small class="opacity-75">(Enter)</small></button>
			</div>
		</form>
	</div>
</div>

<script>
	window.PDV_CONFIG = {
		tipo: <?= json_encode($pdv['tipo']) ?>,
		pag: <?= json_encode($pdv['pag']) ?>,
		operacao: <?= json_encode($rotulo_op) ?>,
		pessoa: <?= json_encode($rotulo_pessoa) ?>,
		pessoaObrigatoria: <?= $eh_venda ? 'true' : 'false' ?>,
		cadastroPessoa: <?= json_encode($eh_venda ? 'clientes' : 'fornecedores') ?>,
		edicao: <?= (int) ($pdv['id_edicao'] ?? 0) ?>,
		impressaoAutomatica: <?= json_encode(($pdv['impressao_automatica'] ?? '') == 'Sim') ?>
	};
</script>
<script src="js/pdv_diversos.js?v=<?= @filemtime(__DIR__ . '/../../js/pdv_diversos.js') ?>"></script>
