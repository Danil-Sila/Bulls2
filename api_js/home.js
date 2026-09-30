const view = {
	error: document.getElementById('error'),
	stockDoses: document.getElementById('stockDoses'),
	stockPositions: document.getElementById('stockPositions'),
	negative: document.getElementById('negative'),
	negativeDoses: document.getElementById('negativeDoses'),
	negativePositions: document.getElementById('negativePositions'),
	search: document.getElementById('bullSearch'),
	suggest: document.getElementById('bullSuggest'),
};
let lastSearch = 0;

function doses(n) {
	return `${formatNumber(n)} ${plural(Math.abs(n), 'доза', 'дозы', 'доз')}`;
}

function positions(n) {
	return `${formatNumber(n)} ${plural(n, 'позиция', 'позиции', 'позиций')}`;
}

async function loadSummary() {
	try {
		const data = await callApi('stockSummary');
		view.stockDoses.textContent = doses(data.doses);
		view.stockPositions.textContent = positions(data.positions - data.negative_positions);
		view.negativeDoses.textContent = doses(data.negative_doses);
		view.negativePositions.textContent = positions(data.negative_positions);
		view.negative.hidden = data.negative_positions === 0;
	} catch (error) {
		view.stockDoses.textContent = '—';
		showError(view.error, `Не удалось загрузить остатки: ${error.message}`);
	}
}

loadSummary();

function suggestionRow(bull) {
	const link = el('a', 'list-group-item list-group-item-action d-flex justify-content-between align-items-baseline gap-3');
	link.href = `bulls.php?${new URLSearchParams({ search: bull.name })}`;
	const title = el('span');
	title.append(
		el('span', 'fw-medium', bull.name),
		el('span', 'text-body-secondary ms-2', bull.num),
		el('span', 'text-body-secondary ms-2 d-none d-sm-inline', bull.breed),
	);
	const stock = el('span', 'num text-nowrap', bull.stock === null ? 'нет' : doses(bull.stock));
	if (bull.stock === null) {
		stock.classList.add('text-body-secondary');
	} else if (bull.stock < 0) {
		stock.classList.add('text-danger', 'fw-semibold');
	}
	link.append(title, stock);
	return link;
}

function renderSuggestions(data, text) {
	if (data.rows.length === 0) {
		view.suggest.replaceChildren(el('div', 'list-group-item text-body-secondary', 'Ничего не найдено'));
	} else {
		view.suggest.replaceChildren(...data.rows.map(suggestionRow));
		if (data.total > data.rows.length) {
			const all = el('a', 'list-group-item list-group-item-action text-primary', `Показать все ${formatNumber(data.total)} →`);
			all.href = `bulls.php?${new URLSearchParams({ search: text })}`;
			view.suggest.append(all);
		}
	}
	view.suggest.hidden = false;
}

async function loadSuggestions() {
	// Ответы на быстрый набор могут прийти не по порядку — рисуем только последний запрос
	const request = ++lastSearch;
	const text = view.search.value.trim();
	if (text.length < 2) {
		view.suggest.hidden = true;
		return;
	}
	try {
		const data = await callApi('bullSearch', { search: text });
		if (request !== lastSearch) return;
		renderSuggestions(data, text);
	} catch (error) {
		if (request !== lastSearch) return;
		view.suggest.replaceChildren(el('div', 'list-group-item text-danger', `Не удалось выполнить поиск: ${error.message}`));
		view.suggest.hidden = false;
	}
}

view.search.addEventListener('input', debounce(loadSuggestions));
view.search.addEventListener('keydown', event => {
	if (event.key === 'Escape') view.suggest.hidden = true;
});
document.addEventListener('click', event => {
	if (!view.search.form.contains(event.target)) view.suggest.hidden = true;
});
