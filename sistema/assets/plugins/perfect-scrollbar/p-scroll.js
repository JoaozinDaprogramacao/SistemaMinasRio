(function($) {
	"use strict";

	// --- CÓDIGO CORRIGIDO ---
	// Adicionada uma verificação para cada seletor. O PerfectScrollbar só será
	// inicializado se o elemento correspondente for encontrado na página,
	// evitando erros em páginas que não os possuem.

	// 1. P-scrolling para o Chat
	const chatElement = document.querySelector('.chat-scroll');
	if (chatElement) {
		const ps2 = new PerfectScrollbar(chatElement, {
			useBothWheelAxes: true,
			suppressScrollX: true,
		});
	}

	// 2. P-scrolling para Notificações
	const notificationElement = document.querySelector('.Notification-scroll');
	if (notificationElement) {
		const ps3 = new PerfectScrollbar(notificationElement, {
			useBothWheelAxes: true,
			suppressScrollX: true,
		});
	}

	// 3. P-scrolling para o Carrinho
	const cartElement = document.querySelector('.cart-scroll');
	if (cartElement) {
		const ps4 = new PerfectScrollbar(cartElement, {
			useBothWheelAxes: true,
			suppressScrollX: true,
		});
	}

})(jQuery);