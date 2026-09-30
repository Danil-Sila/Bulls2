async function callApi(sender, params = {}) {
	const response = await fetch('api.php', {
		method: 'POST',
		headers: { 'Content-Type': 'application/json' },
		body: JSON.stringify({ sender, ...params }),
	});

	let data;
	try {
		data = await response.json();
	} catch {
		throw new Error(`Сервер вернул неожиданный ответ (HTTP ${response.status})`);
	}
	if (!data.success) {
		const error = new Error(data.error || `Ошибка сервера (HTTP ${response.status})`);
		error.fields = data.fields || {};
		throw error;
	}
	return data;
}

// Данные вставляются только через textContent, чтобы текст из базы не мог выполниться как HTML.
function el(tag, className = '', text = '') {
	const node = document.createElement(tag);
	if (className) node.className = className;
	if (text !== '') node.textContent = text;
	return node;
}

function formatDate(iso) {
	const [y, m, d] = iso.split('-');
	return `${d}.${m}.${y}`;
}

function formatNumber(n) {
	return Number(n).toLocaleString('ru-RU');
}

function isoDate(date) {
	const pad = n => String(n).padStart(2, '0');
	return `${date.getFullYear()}-${pad(date.getMonth() + 1)}-${pad(date.getDate())}`;
}

function plural(n, one, few, many) {
	const mod10 = n % 10, mod100 = n % 100;
	if (mod10 === 1 && mod100 !== 11) return one;
	if (mod10 >= 2 && mod10 <= 4 && (mod100 < 12 || mod100 > 14)) return few;
	return many;
}

function showError(box, message) {
	box.textContent = message;
	box.hidden = !message;
}

function toast(message, type = 'success') {
	let box = document.getElementById('toasts');
	if (!box) {
		box = el('div', 'toast-container position-fixed bottom-0 end-0 p-3');
		box.id = 'toasts';
		document.body.append(box);
	}
	const item = el('div', `toast align-items-center text-bg-${type} border-0`);
	item.setAttribute('role', 'status');
	const row = el('div', 'd-flex');
	const close = el('button', 'btn-close btn-close-white me-2 m-auto');
	close.type = 'button';
	close.dataset.bsDismiss = 'toast';
	close.setAttribute('aria-label', 'Закрыть');
	row.append(el('div', 'toast-body', message), close);
	item.append(row);
	box.append(item);
	item.addEventListener('hidden.bs.toast', () => item.remove());
	bootstrap.Toast.getOrCreateInstance(item).show();
}

function confirmDialog(message, okText = 'Удалить') {
	return new Promise ( resolve => {
		const modal = el('div', 'modal fade');
		modal.tabIndex = -1;
		const dialog = el('div', 'modal-dialog modal-dialog-centered');
		const content = el('div', 'modal-content');
		const footer = el('div', 'modal-footer');
		const cancel = el('button', 'btn btn-outline-secondary', 'Отмена');
		const ok = el('button', 'btn btn-danger', okText);
		cancel.type = ok.type = 'button';
		cancel.dataset.bsDismiss = 'modal';
		footer.append(cancel, ok);
		content.append(el('div', 'modal-body', message), footer);
		dialog.append(content);
		modal.append(dialog);
		document.body.append(modal);

		const instance = new bootstrap.Modal(modal);
		let confirmed = false;
		ok.addEventListener('click', () => {
			confirmed = true;
			instance.hide();
		});
		modal.addEventListener('hidden.bs.modal', () => {
			modal.remove();
			resolve(confirmed);
		});
		instance.show();
	});
}

function debounce(fn, ms = 300) {
	let timer;
	return (...args) => {
		clearTimeout(timer);
		timer = setTimeout(() => fn(...args), ms);
	};
}
