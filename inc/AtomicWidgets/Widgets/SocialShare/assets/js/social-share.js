const { register } = window.elementorV2?.frontendHandlers || window.elementorFrontend?.elementsHandler || {};

const COUNT_CLASS = 'aae-a-social-share-item__count';

const ensureCountSpan = (item) => {
	let span = item.querySelector('.' + COUNT_CLASS);
	if (!span) {
		span = document.createElement('span');
		span.className = COUNT_CLASS + ' aae-share-count';
		span.setAttribute('data-type', item.getAttribute('data-aae-vendor') || '');
		span.textContent = item.getAttribute('data-aae-share-count') || '0';
		item.appendChild(span);
	}
	return span;
};

const openShareWindow = (url) => {
	const width = 600;
	const height = 500;
	const left = (window.screen.width / 2) - (width / 2);
	const top = (window.screen.height / 2) - (height / 2);
	const features = `toolbar=no,location=no,directories=no,status=no,menubar=no,scrollbars=yes,resizable=yes,width=${width},height=${height},top=${top},left=${left}`;
	return window.open(url, 'aae_social_share', features);
};

const initSocialShare = (container) => {
	if (container.dataset.aaeSocialShareInit === 'true') {
		return;
	}
	container.dataset.aaeSocialShareInit = 'true';

	const items = container.querySelectorAll('.aae-a-social-share-item');

	items.forEach((item) => {
		ensureCountSpan(item);

		item.style.cursor = 'pointer';
		item.addEventListener('click', (e) => {
			e.preventDefault();
			const url = item.getAttribute('data-aae-share-url');
			const type = item.getAttribute('data-aae-vendor') || 'facebook';

			if (url && url !== '#') {
				openShareWindow(url);
			}

			const wcfData = window.WCF_ADDONS_JS;
			if (!wcfData || !wcfData.post_id || !wcfData.ajaxUrl) {
				return;
			}

			const formData = new FormData();
			formData.append('action', 'aae_post_shares');
			formData.append('post_id', wcfData.post_id);
			formData.append('nonce', wcfData._wpnonce || '');
			formData.append('social', type);

			fetch(wcfData.ajaxUrl, {
				method: 'POST',
				body: formData,
				credentials: 'same-origin',
			})
				.then((res) => res.json())
				.then((response) => {
					if (response && response.success && response.data && response.data.post_shares) {
						const shares = response.data.post_shares;
						container.querySelectorAll('.' + COUNT_CLASS).forEach((el) => {
							const t = el.getAttribute('data-type');
							if (t && shares[t] !== undefined) {
								el.textContent = shares[t];
							}
						});
					}
				})
				.catch(() => { });
		});
	});
};

register({
	elementType: 'e-aae-a-social-share',
	id: 'aae-a-social-share-handler',
	callback: ({ element }) => {
		
		const container = element.classList && element.classList.contains('aae-a-social-share')
			? element
			: element.querySelector('.aae-a-social-share');
		if (container) {
			initSocialShare(container);
		}
	},
});
