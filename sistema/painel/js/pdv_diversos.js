// PDV de diversos (telas Vendas e Compras). A configuração vem de window.PDV_CONFIG,
// montada em paginas/pdv_diversos/tela.php. O carrinho fica no banco; aqui só se exibe
// e se manda cada alteração para paginas/<pag>/carrinho.php.
//
// Roda no DOMContentLoaded porque o index.php carrega o jQuery definitivo, o Bootstrap
// e o Select2 só no fim da página.
document.addEventListener('DOMContentLoaded', function () {
	'use strict';
	var $ = window.jQuery;
	var C = window.PDV_CONFIG;
	var URL_BASE = 'paginas/' + C.pag + '/';

	var itens = [];        // [{id, material, nome, quantidade, valor, total}]
	var pendentes = [];    // requisições de atualização ainda em voo
	var salvando = false;

	// ---------- Números no formato brasileiro ----------
	// Mesmo critério do venda_valor_br() no PHP: "1.234,56", "1234.56" e "1.000" (milhar)
	function num(v) {
		v = String(v == null ? '' : v).replace(/R\$|\s/g, '');
		if (v.indexOf(',') >= 0 || /^-?\d{1,3}(\.\d{3})+$/.test(v)) {
			v = v.replace(/\./g, '').replace(',', '.');
		}
		var n = parseFloat(v);
		return isNaN(n) ? 0 : n;
	}
	function r2(n) { return Math.round(n * 100) / 100; }
	function moeda(n) { return (n || 0).toLocaleString('pt-BR', { minimumFractionDigits: 2, maximumFractionDigits: 2 }); }
	function rs(n) { return 'R$ ' + moeda(n); }
	function qtd(n) { return (n || 0).toLocaleString('pt-BR', { maximumFractionDigits: 1 }); }

	// ---------- Avisos ----------
	var Toast = Swal.mixin({ toast: true, position: 'top-end', showConfirmButton: false, timer: 2200, timerProgressBar: true });
	function aviso(msg) { Toast.fire({ icon: 'success', title: msg }); }
	function erro(msg) { Toast.fire({ icon: 'error', title: msg, timer: 5000 }); }

	function textoErro(xhr) {
		var t = $('<div>').html((xhr && xhr.responseText) || '').text().trim();
		return t || 'Falha de comunicação com o servidor. Tente de novo.';
	}

	function api(dados) {
		return $.ajax({ url: URL_BASE + 'carrinho.php', method: 'POST', data: dados, dataType: 'json' })
			.then(function (r) {
				return (r && r.ok) ? r : $.Deferred().reject((r && r.msg) || 'Erro inesperado.');
			}, function (xhr) {
				return $.Deferred().reject(textoErro(xhr));
			});
	}

	// ---------- Catálogo ----------
	var $grid = $('#pdv_grid');
	var $cards = $grid.find('.pdv-mat');

	function semAcento(s) {
		return String(s).toLowerCase().normalize('NFD').replace(/[̀-ͯ]/g, '');
	}

	function filtrar() {
		var termo = semAcento($('#pdv_busca').val().trim());
		var visiveis = 0;
		$cards.each(function () {
			var ok = !termo || semAcento(this.getAttribute('data-nome')).indexOf(termo) >= 0;
			this.style.display = ok ? '' : 'none';
			if (ok) visiveis++;
		});
		$('#pdv_sem_resultado').toggle($cards.length > 0 && visiveis === 0);
	}

	function marcarCatalogo() {
		var porMaterial = {};
		itens.forEach(function (it) { porMaterial[it.material] = (porMaterial[it.material] || 0) + it.quantidade; });
		$cards.each(function () {
			var q = porMaterial[this.getAttribute('data-id')];
			$(this).toggleClass('no-carrinho', !!q).find('.pdv-mat-badge').text(q ? qtd(q) : '');
		});
	}

	$('#pdv_busca').on('input', filtrar).on('keydown', function (e) {
		if (e.key === 'Enter') {
			e.preventDefault();
			var $primeiro = $cards.filter(function () { return this.style.display !== 'none'; }).first();
			if ($primeiro.length) abrirLancamento($primeiro);
		} else if (e.key === 'Escape') {
			$(this).val('');
			filtrar();
		}
	});

	$grid.on('click', '.pdv-mat', function () { abrirLancamento($(this)); });

	// ---------- Lançar material (modal de quantidade) ----------
	var modalEl = document.getElementById('pdv_modal_item');
	var modal = bootstrap.Modal.getOrCreateInstance(modalEl);
	var lancando = null;

	function noCarrinho(idMaterial) {
		for (var i = 0; i < itens.length; i++) {
			if (String(itens[i].material) === String(idMaterial)) return itens[i];
		}
		return null;
	}

	function abrirLancamento($card) {
		var m = {
			id: $card.data('id'),
			nome: $card.attr('data-nome'),
			controla: String($card.data('controla')) === '1',
			estoque: parseFloat($card.data('estoque')) || 0,
			preco: $card.attr('data-preco')
		};
		lancando = m;
		var item = noCarrinho(m.id);

		$('#pdv_mi_titulo').text(m.nome);
		var info = [];
		info.push(m.controla ? 'Estoque atual: <strong>' + qtd(m.estoque) + '</strong>' : 'Sem controle de estoque');
		if (item) info.push('Já no carrinho: <strong>' + qtd(item.quantidade) + '</strong> (a quantidade será somada)');
		$('#pdv_mi_info').html(info.join(' · '));

		var preco = item ? item.valor : (m.preco !== '' ? parseFloat(m.preco) : 0);
		$('#pdv_mi_qtd').val('').removeClass('is-invalid');
		$('#pdv_mi_preco').val(preco > 0 ? moeda(preco) : '').removeClass('is-invalid');
		atualizarModal();
		modal.show();
	}

	function atualizarModal() {
		if (!lancando) return;
		var q = num($('#pdv_mi_qtd').val());
		var p = num($('#pdv_mi_preco').val());
		$('#pdv_mi_total').text(rs(r2(q * p)));

		var aviso = '';
		if (C.tipo === 'venda' && lancando.controla && q > 0) {
			var item = noCarrinho(lancando.id);
			var total = q + (item ? item.quantidade : 0);
			if (total > lancando.estoque) {
				aviso = '<span class="text-danger">Acima do estoque atual (' + qtd(lancando.estoque) + ').</span>';
			}
		}
		$('#pdv_mi_aviso').html(aviso);
	}

	modalEl.addEventListener('shown.bs.modal', function () { $('#pdv_mi_qtd').trigger('focus'); });
	modalEl.addEventListener('hidden.bs.modal', function () {
		lancando = null;
		$('#pdv_busca').trigger('focus').trigger('select');
	});
	$('#pdv_mi_qtd, #pdv_mi_preco').on('input', atualizarModal).on('focus', function () { this.select(); });
	$('#pdv_mi_qtd').on('keydown', function (e) {
		// Enter na quantidade vai para o preço quando ele ainda está vazio
		if (e.key === 'Enter' && num($('#pdv_mi_preco').val()) <= 0 && num(this.value) > 0) {
			e.preventDefault();
			$('#pdv_mi_preco').trigger('focus');
		}
	});
	$('#pdv_mi_preco').on('blur', function () { if (this.value.trim() !== '') this.value = moeda(num(this.value)); });

	$('#pdv_mi_form').on('submit', function (e) {
		e.preventDefault();
		if (!lancando) return;
		var q = num($('#pdv_mi_qtd').val());
		if (q <= 0) {
			$('#pdv_mi_qtd').addClass('is-invalid').trigger('focus');
			return;
		}
		var precoTxt = $('#pdv_mi_preco').val().trim();
		var $btn = $('#pdv_mi_ok').prop('disabled', true);
		var nome = lancando.nome;
		api({ acao: 'inserir', id_material: lancando.id, quantidade: q, valor: precoTxt === '' ? '' : num(precoTxt) })
			.done(function (r) {
				itens = r.itens;
				renderCarrinho(r.id);
				modal.hide();
				aviso(nome + ' adicionado');
			})
			.fail(function (msg) { erro(msg); $('#pdv_mi_qtd').trigger('focus'); })
			.always(function () { $btn.prop('disabled', false); });
	});

	// ---------- Carrinho ----------
	var $lista = $('#pdv_itens');

	function linhaItem(it) {
		var $l = $(
			'<div class="pdv-item">' +
			'<div class="pdv-item-topo"><span class="pdv-item-nome"></span>' +
			'<button type="button" class="pdv-item-remover" title="Remover item" aria-label="Remover item"><i class="fa fa-trash"></i></button></div>' +
			'<div class="pdv-item-campos">' +
			'<label class="pdv-mini mb-0"><span>Qtd</span><input type="text" inputmode="decimal" class="form-control" data-campo="quantidade"></label>' +
			'<span class="pdv-x">×</span>' +
			'<label class="pdv-mini mb-0"><span>Preço un. (R$)</span><input type="text" inputmode="decimal" class="form-control" data-campo="valor" placeholder="0,00"></label>' +
			'<div class="pdv-item-total"></div>' +
			'</div></div>'
		);
		$l.attr('data-id', it.id);
		$l.find('.pdv-item-nome').text(it.nome);
		preencherLinha($l, it);
		return $l;
	}

	function preencherLinha($l, it) {
		var $q = $l.find('[data-campo=quantidade]');
		var $v = $l.find('[data-campo=valor]');
		if (!$q.is(':focus')) $q.val(qtd(it.quantidade));
		if (!$v.is(':focus')) $v.val(it.valor > 0 ? moeda(it.valor) : '');
		$v.toggleClass('is-invalid', it.valor <= 0).attr('title', it.valor <= 0 ? 'Informe o preço' : '');
		$l.find('.pdv-item-total').text(rs(it.total));
	}

	function renderCarrinho(idDestaque) {
		$lista.empty();
		if (!itens.length) {
			$lista.html('<div class="pdv-vazio"><i class="fa fa-shopping-basket"></i>Nenhum item ainda.<br>Clique num material ou busque pelo nome.</div>');
		}
		itens.forEach(function (it) {
			var $l = linhaItem(it);
			if (idDestaque && it.id === idDestaque) $l.addClass('flash');
			$lista.append($l);
		});
		if (idDestaque) {
			var el = $lista.find('.flash')[0];
			if (el && el.scrollIntoView) el.scrollIntoView({ block: 'nearest' });
		}
		marcarCatalogo();
		recalcular();
	}

	function itemPorLinha($l) {
		var id = parseInt($l.attr('data-id'), 10);
		for (var i = 0; i < itens.length; i++) if (itens[i].id === id) return itens[i];
		return null;
	}

	// Digitando: o total da linha e o resumo acompanham na hora
	$lista.on('input', 'input', function () {
		var $l = $(this).closest('.pdv-item');
		var it = itemPorLinha($l);
		if (!it) return;
		var q = num($l.find('[data-campo=quantidade]').val());
		var v = num($l.find('[data-campo=valor]').val());
		$l.find('.pdv-item-total').text(rs(r2(q * v)));
		recalcular(true);
	});

	$lista.on('focus', 'input', function () { this.select(); });

	// Enter passa para o próximo campo (quantidade → preço → próximo item)
	$lista.on('keydown', 'input', function (e) {
		if (e.key !== 'Enter' || e.ctrlKey) return;
		e.preventDefault();
		var $todos = $lista.find('input');
		var i = $todos.index(this);
		if (i < $todos.length - 1) $todos.eq(i + 1).trigger('focus');
		else this.blur();
	});

	// Saiu do campo com valor alterado: grava no carrinho
	$lista.on('change', 'input', function () {
		var $l = $(this).closest('.pdv-item');
		var it = itemPorLinha($l);
		if (!it) return;
		var q = num($l.find('[data-campo=quantidade]').val());
		var v = num($l.find('[data-campo=valor]').val());

		if (q <= 0) {
			erro('A quantidade precisa ser maior que zero. Para tirar o item, use a lixeira.');
			preencherLinha($l, it);
			recalcular();
			return;
		}

		$l.addClass('salvando');
		var req = api({ acao: 'atualizar', id: it.id, quantidade: q, valor: v })
			.done(function (r) {
				$.extend(it, r.item);
				preencherLinha($l, it);
				marcarCatalogo();
				recalcular();
			})
			.fail(function (msg) {
				erro(msg);
				preencherLinha($l, it);  // volta para o que está gravado
				recalcular();
			})
			.always(function () { $l.removeClass('salvando'); });
		pendentes.push(req);
	});

	$lista.on('blur', '[data-campo=valor]', function () {
		if (this.value.trim() !== '') this.value = moeda(num(this.value));
	});

	$lista.on('click', '.pdv-item-remover', function () {
		var $l = $(this).closest('.pdv-item');
		var it = itemPorLinha($l);
		if (!it) return;
		$l.css('opacity', .4);
		api({ acao: 'excluir', id: it.id })
			.done(function (r) {
				itens = r.itens;
				renderCarrinho();
				Toast.fire({
					icon: 'info', title: it.nome + ' removido', timer: 5000,
					showConfirmButton: true, confirmButtonText: 'Desfazer'
				}).then(function (res) {
					if (!res.isConfirmed) return;
					api({ acao: 'inserir', id_material: it.material, quantidade: it.quantidade, valor: it.valor })
						.done(function (r2) { itens = r2.itens; renderCarrinho(r2.id); })
						.fail(erro);
				});
			})
			.fail(function (msg) { $l.css('opacity', 1); erro(msg); });
	});

	// ---------- Pagamento ----------
	var $pago = $('#valor_pago');

	function tipoDesconto(t) {
		$('#tipo_desconto').val(t);
		$('.pdv-seg button').each(function () { $(this).toggleClass('ativo', $(this).data('tipo') === t); });
		$('#desconto').attr('placeholder', t === '%' ? '0' : '0,00').attr('data-formato', t === '%' ? 'pct' : 'moeda');
		recalcular();
	}
	$('.pdv-seg button').on('click', function () { tipoDesconto($(this).data('tipo')); });

	function totais(provisorio) {
		var subtotal = 0;
		if (provisorio) {
			// Usa o que está digitado nas linhas, mesmo antes de gravar
			$lista.find('.pdv-item').each(function () {
				subtotal += r2(num($(this).find('[data-campo=quantidade]').val()) * num($(this).find('[data-campo=valor]').val()));
			});
		} else {
			itens.forEach(function (it) { subtotal += it.total; });
		}
		subtotal = r2(subtotal);
		var desc = num($('#desconto').val());
		var pct = $('#tipo_desconto').val() === '%';
		var vDesc = pct ? r2(subtotal * desc / 100) : desc;
		var frete = num($('#frete').val());
		return { subtotal: subtotal, desc: desc, pct: pct, vDesc: vDesc, frete: frete, total: r2(subtotal - vDesc + frete) };
	}

	function recalcular(provisorio) {
		var t = totais(provisorio);
		var n = itens.length;

		$('#pdv_linha_sub').toggle(t.vDesc > 0 || t.frete > 0);
		$('#pdv_subtotal').text(rs(t.subtotal));
		$('#pdv_linha_desc').toggle(t.vDesc > 0);
		$('#pdv_desc_rotulo').text('Desconto' + (t.pct ? ' (' + qtd(t.desc) + '%)' : ''));
		$('#pdv_desc_valor').text('− ' + rs(t.vDesc));
		$('#pdv_linha_frete').toggle(t.frete > 0);
		$('#pdv_frete_valor').text('+ ' + rs(t.frete));
		$('#pdv_desc_erro').toggle(t.vDesc > t.subtotal && n > 0);
		$('#pdv_total').text(rs(t.total));

		// O valor pago acompanha o total até o operador digitar outro
		var manual = String($pago.attr('data-manual')) === '1';
		if (!manual && !$pago.is(':focus')) $pago.val(n ? moeda(Math.max(0, t.total)) : '');
		var pago = num($pago.val());
		$('#pdv_usar_total').toggle(manual && n > 0 && Math.abs(pago - t.total) > 0.004);

		var acima = n > 0 && pago - t.total > 0.004;
		var troco = (C.tipo === 'venda' && acima) ? r2(pago - t.total) : 0;
		$('#troco').val(troco > 0 ? moeda(pago) : '');
		$('#pdv_troco').toggle(troco > 0).text('Troco: ' + rs(troco));
		$('#pdv_acima').toggle(C.tipo !== 'venda' && acima);

		var restante = r2(Math.max(0, t.total - pago));
		$('#pdv_restante').toggle(n > 0 && restante > 0.004);
		$('#pdv_restante_valor').text(rs(restante));

		var rotulo = C.edicao ? 'Salvar alterações' : 'Fechar ' + C.operacao;
		if (!salvando) {
			$('#pdv_fechar').prop('disabled', n === 0).text(n ? rotulo + ' · ' + rs(t.total) : rotulo);
		}
		$('#pdv_qtd_itens').text(n ? n + (n === 1 ? ' item' : ' itens') : '');
		$('#pdv_barra_itens').text(n ? n + (n === 1 ? ' item' : ' itens') : 'Nenhum item');
		$('#pdv_barra_total').text(rs(t.total));
		return t;
	}

	$('#desconto, #frete').on('input', function () { recalcular(); });
	$('#pdv_abrir_ajustes').on('click', function () {
		$('#pdv_abrir_ajustes_box').hide();
		$('.pdv-ajuste').show();
		$('#desconto').trigger('focus');
	});
	$pago.on('input', function () {
		$pago.attr('data-manual', this.value.trim() === '' ? 0 : 1);
		recalcular();
	});
	$('#pdv_usar_total').on('click', function () {
		$pago.attr('data-manual', 0);
		recalcular();
	});

	// Campos de dinheiro: digita livre, formata ao sair
	$('#pdv_form').on('focus', '[data-formato]', function () { this.select(); });
	$('#pdv_form').on('blur', '[data-formato]', function () {
		if (this.value.trim() === '') { recalcular(); return; }
		this.value = $(this).attr('data-formato') === 'pct' ? qtd(num(this.value)) : moeda(num(this.value));
		recalcular();
	});

	// ---------- Cliente / fornecedor ----------
	var $pessoa = $('#pdv_pessoa');
	$pessoa.select2({ width: '100%', placeholder: 'Selecione o ' + C.pessoa.toLowerCase() + '…', allowClear: !C.pessoaObrigatoria });
	$(document).on('select2:open', function () {
		var campo = document.querySelector('.select2-container--open .select2-search__field');
		if (campo) campo.focus();
	});

	var esperandoCadastro = false;
	$('#pdv_nova_pessoa').on('click', function () {
		esperandoCadastro = true;
		window.open(C.cadastroPessoa + '#novo', '_blank');
	});

	// Voltou da aba de cadastro: recarrega a lista e seleciona quem acabou de entrar
	$(window).on('focus', function () {
		if (!esperandoCadastro) return;
		esperandoCadastro = false;
		var antes = {};
		$pessoa.find('option').each(function () { antes[this.value] = true; });
		$.getJSON(URL_BASE + 'pessoas.php').done(function (lista) {
			var atual = $pessoa.val();
			var novo = null;
			$pessoa.empty().append('<option value=""></option>');
			lista.forEach(function (p) {
				$pessoa.append(new Option(p.nome, p.id, false, false));
				if (!antes[p.id] && (!novo || p.id > novo.id)) novo = p;
			});
			$pessoa.val(novo ? String(novo.id) : atual).trigger('change');
			if (novo) aviso(C.pessoa + ' ' + novo.nome + ' selecionado');
		});
	});

	// ---------- Fechar ----------
	function validar() {
		var t = recalcular();
		if (!itens.length) { erro('Adicione pelo menos um material.'); return false; }
		if (C.pessoaObrigatoria && !$pessoa.val()) {
			erro('Selecione o ' + C.pessoa.toLowerCase() + '.');
			$pessoa.select2('open');
			return false;
		}
		var semPreco = itens.filter(function (it) { return it.valor <= 0; })[0];
		if (semPreco) {
			erro('Informe o preço de ' + semPreco.nome + '.');
			$lista.find('.pdv-item[data-id=' + semPreco.id + '] [data-campo=valor]').trigger('focus');
			return false;
		}
		if (t.vDesc > t.subtotal) { erro('O desconto é maior que o valor dos itens.'); $('#desconto').trigger('focus'); return false; }
		if (t.total <= 0) { erro('O total precisa ser maior que zero.'); return false; }
		if (!$('#saida').val()) { erro('Selecione a forma de pagamento.'); $('#saida').trigger('focus'); return false; }
		return true;
	}

	function estadoSalvando(sim) {
		salvando = sim;
		var $b = $('#pdv_fechar');
		if (sim) $b.prop('disabled', true).html('<span class="spinner-border spinner-border-sm me-2"></span>Salvando…');
		else recalcular();
	}

	function concluir(id, total) {
		var titulo = (C.tipo === 'venda' ? 'Venda #' : 'Compra #') + id + (C.edicao ? ' atualizada' : ' registrada');
		if (C.tipo === 'venda' && C.impressaoAutomatica) {
			window.open('rel/comprovante_pdf.php?venda=' + id);
		}
		Swal.fire({
			icon: 'success',
			title: titulo,
			html: 'Total: <strong>' + rs(total) + '</strong>' + (C.tipo === 'compra' ? '<br><small>Lançada em Contas a Pagar.</small>' : ''),
			confirmButtonText: 'Nova ' + C.operacao,
			showDenyButton: C.tipo === 'venda' && !C.impressaoAutomatica,
			denyButtonText: '<i class="fa fa-print"></i> Comprovante',
			denyButtonColor: '#6c757d',
			allowOutsideClick: false
		}).then(function (res) {
			if (res.isDenied) window.open('rel/comprovante_pdf.php?venda=' + id);
			window.location = C.pag;
		});
	}

	function enviar() {
		var total = totais().total;
		$.ajax({
			url: URL_BASE + 'salvar.php',
			type: 'POST',
			data: new FormData(document.getElementById('pdv_form')),
			cache: false, contentType: false, processData: false
		}).done(function (resp) {
			var partes = String(resp).trim().split('-');
			if (partes[0].trim() === 'Salvo com Sucesso') {
				concluir(partes[1].trim(), total);
			} else {
				estadoSalvando(false);
				Swal.fire({ icon: 'warning', title: 'Não foi possível salvar', text: $('<div>').html(resp).text().trim() });
			}
		}).fail(function (xhr) {
			estadoSalvando(false);
			Swal.fire({ icon: 'error', title: 'Erro ao salvar', text: textoErro(xhr) });
		});
	}

	$('#pdv_form').on('submit', function (e) {
		e.preventDefault();
		if (salvando) return;
		// Grava o campo que ainda está em edição antes de conferir
		if (document.activeElement && $.contains(this, document.activeElement)) document.activeElement.blur();
		var emVoo = pendentes.filter(function (p) { return p.state() === 'pending'; });
		pendentes = [];
		estadoSalvando(true);
		$.when.apply($, emVoo).then(function () {
			if (!validar()) { estadoSalvando(false); return; }
			enviar();
		}, function () {
			estadoSalvando(false);  // a falha já foi avisada
		});
	});

	// Enter nos campos do pagamento não fecha sem querer; Ctrl+Enter fecha
	$('#pdv_form').on('keydown', 'input', function (e) {
		if (e.key === 'Enter' && !e.ctrlKey) e.preventDefault();
	});

	function limparEsair() {
		api({ acao: 'limpar' }).done(function () { window.location = C.pag; }).fail(erro);
	}

	$('#pdv_limpar').on('click', function () {
		if (!itens.length && !C.edicao) return;
		Swal.fire({
			icon: 'question', title: 'Limpar o carrinho?', text: 'Todos os itens serão removidos.',
			showCancelButton: true, confirmButtonText: 'Limpar', cancelButtonText: 'Voltar', confirmButtonColor: '#dc3545'
		}).then(function (r) { if (r.isConfirmed) limparEsair(); });
	});

	$('#pdv_cancelar_edicao').on('click', function () {
		Swal.fire({
			icon: 'question', title: 'Cancelar a edição?', text: 'A venda continua como estava antes.',
			showCancelButton: true, confirmButtonText: 'Cancelar edição', cancelButtonText: 'Continuar editando'
		}).then(function (r) { if (r.isConfirmed) limparEsair(); });
	});

	// ---------- Atalhos e celular ----------
	$(document).on('keydown', function (e) {
		if (e.key === 'F2') {
			e.preventDefault();
			$('#pdv_busca').trigger('focus').trigger('select');
		} else if (e.key === 'Enter' && e.ctrlKey && !$(modalEl).hasClass('show')) {
			e.preventDefault();
			$('#pdv_form').trigger('submit');
		}
	});

	// No celular a barra some quando o botão de fechar já está na tela
	if ('IntersectionObserver' in window) {
		new IntersectionObserver(function (entradas) {
			$('#pdv_barra').css('visibility', entradas[0].isIntersecting ? 'hidden' : 'visible');
		}).observe(document.getElementById('pdv_fechar'));
	}

	$('#pdv_ir_pagamento').on('click', function () {
		document.getElementById('pdv_form').scrollIntoView({ behavior: 'smooth', block: 'start' });
	});

	// ---------- Início ----------
	tipoDesconto($('#tipo_desconto').val() === '%' ? '%' : 'reais');
	api({ acao: 'listar' })
		.done(function (r) { itens = r.itens; renderCarrinho(); })
		.fail(function (msg) { $lista.html('<div class="pdv-vazio text-danger">' + $('<div>').text(msg).html() + '</div>'); });

	if (C.edicao) {
		aviso('Venda #' + C.edicao + ' carregada para edição');
	} else if (window.matchMedia('(min-width: 992px)').matches) {
		$('#pdv_busca').trigger('focus');
	}
});
