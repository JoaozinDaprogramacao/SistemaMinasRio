jQuery(function(e) {
    'use strict';

    // --- CÓDIGO CORRIGIDO ---
    // Cada inicialização de popover agora está dentro de uma verificação 'if'.
    // Isso garante que o script não quebre em páginas que não contenham
    // todos os exemplos de popovers.

    // Popover de exemplo simples
    const examplePopoverEl = document.querySelector('.example');
    if (examplePopoverEl) {
        new bootstrap.Popover(examplePopoverEl, {
            container: 'body'
        });
    }

    // Ativação geral e segura para todos os popovers com [data-bs-toggle="popover"]
    const popoverTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="popover"]'));
    popoverTriggerList.forEach(function(popoverTriggerEl) {
        // Só inicializa se o elemento tiver conteúdo (title ou data-bs-content)
        if (popoverTriggerEl.getAttribute('title') || popoverTriggerEl.getAttribute('data-bs-content')) {
            new bootstrap.Popover(popoverTriggerEl);
        } else {
            console.warn('Popover ignorado por falta de "title" ou "data-bs-content":', popoverTriggerEl);
        }
    });

    // Função auxiliar para inicializar popovers coloridos de forma segura
    const initPopoverWithTemplate = function(selector, template) {
        const element = document.querySelector(selector);
        if (element) {
            new bootstrap.Popover(element, { template: template });
        }
    };

    // Templates para os popovers
    const defaultTemplate = '<div class="popover" role="tooltip"><div class="popover-arrow"></div><h3 class="popover-header"></h3><div class="popover-body"></div></div>';
    const headPrimaryTemplate = '<div class="popover popover-head-primary" role="tooltip"><div class="popover-arrow"></div><h3 class="popover-header"></h3><div class="popover-body"></div></div>';
    const headSecondaryTemplate = '<div class="popover popover-head-secondary" role="tooltip"><div class="popover-arrow"></div><h3 class="popover-header"></h3><div class="popover-body"></div></div>';
    const primaryTemplate = '<div class="popover popover-primary" role="tooltip"><div class="popover-arrow"></div><h3 class="popover-header"></h3><div class="popover-body"></div></div>';
    const secondaryTemplate = '<div class="popover popover-secondary" role="tooltip"><div class="popover-arrow"></div><h3 class="popover-header"></h3><div class="popover-body"></div></div>';

    // Inicialização segura dos popovers coloridos
    initPopoverWithTemplate('[data-bs-popover-color="default"]', defaultTemplate);
    initPopoverWithTemplate('[data-bs-popover-color="default1"]', defaultTemplate);
    initPopoverWithTemplate('[data-bs-popover-color="default2"]', defaultTemplate);
    initPopoverWithTemplate('[data-bs-popover-color="default3"]', defaultTemplate);
    initPopoverWithTemplate('[data-bs-popover-color="head-primary"]', headPrimaryTemplate);
    initPopoverWithTemplate('[data-bs-popover-color="head-secondary"]', headSecondaryTemplate);
    initPopoverWithTemplate('[data-bs-popover-color="primary"]', primaryTemplate);
    initPopoverWithTemplate('[data-bs-popover-color="secondary"]', secondaryTemplate);


    // Função para fechar popovers ao clicar fora (código original mantido)
    $(document).on('click', function(e) {
        $('[data-bs-toggle="popover"]').each(function() {
            if (!$(this).is(e.target) && $(this).has(e.target).length === 0 && $('.popover').has(e.target).length === 0) {
                (($(this).popover('hide').data('bs.popover') || {}).inState || {}).click = false; // fix for BS 3.3.6
            }
        });
    });

    // Enable Eva-icons with SVG markup (código original mantido)
    if (typeof eva !== 'undefined') {
	    eva.replace();
    }

});