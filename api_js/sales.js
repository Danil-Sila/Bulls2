const form = {
	filters: document.getElementById('filters'),
	dateFrom: document.getElementById('dateFrom'),
	dateTo: document.getElementById('dateTo'),
	buyer: document.getElementById('buyer'),
	bull: document.getElementById('bull'),
};
const view = {
	error: document.getElementById('error'),
	summary: document.getElementById('summary'),
	rows: document.getElementById('rows'),
	prev: document.getElementById('prev'),
	next: document.getElementById('next'),
	pageInfo: document.getElementById('pageInfo'),
	addSale: document.getElementById('addSale'),
};
const fields = {
	sold_on: document.getElementById('saleDate'),
	buyer_id: document.getElementById('saleBuyer'),
	contractor_id: document.getElementById('saleContractor'),
	bull_id: document.getElementById('saleBull'),
	packaging_id: document.getElementById('salePackaging'),
	doses: document.getElementById('saleDoses'),
};
const editor = {
	root: document.getElementById('saleModal'),
	form: document.getElementById('saleForm'),
	title: document.getElementById('saleModalTitle'),
	error: document.getElementById('saleFormError'),
	submit: document.getElementById('saleSubmit'),
	stock: document.getElementById('saleStock'),
};
const saleModal = bootstrap.Modal.getOrCreateInstance(editor.root);

let page = 1;
let lastRequest = 0;
let formLists = null;
let editingId = null;
let available = null;
let stockRequest = 0;

// 30 дней, а не setMonth(-1): 31 марта минус месяц «переползает» на начало марта (31 февраля не бывает)
function defaultPeriod() {
	const to = new Date();
	const from = new Date(to);
	from.setDate(from.getDate() - 30);
	return { from: isoDate(from), to: isoDate(to) };
}

function doses(n) {
	return `${formatNumber(n)} ${plural(Math.abs(n), 'доза', 'дозы', 'доз')}`;
}

function renderRow(sale) {
	const bull = el('td');
	bull.append(el('span', 'fw-medium', sale.bull), el('span', 'text-body-secondary ms-1', sale.bull_num));

	const edit = el('button', 'btn btn-sm btn-outline-secondary', 'Изменить');
	edit.type = 'button';
	edit.addEventListener('click', () => openForm(sale));

	const remove = el('button', 'btn btn-sm btn-outline-danger ms-1', 'Удалить');
	remove.type = 'button';
	remove.addEventListener('click', () => deleteSale(sale));

	const actions = el('td', 'text-end text-nowrap');
	actions.append(edit, remove);

	const tr = el('tr');
	tr.append(
		el('td', 'num', formatDate(sale.sold_on)),
		el('td', 'wrap', sale.buyer),
		bull,
		el('td', '', sale.breed),
		el('td', 'text-end num fw-semibold', formatNumber(sale.doses)),
		el('td', '', sale.packaging),
		el('td', 'wrap text-body-secondary', sale.contractor),
		el('td', 'text-end num text-body-secondary', sale.id),
		actions,
	);
	return tr;
}

function render(data) {
	const n = data.total_rows;
	view.summary.textContent = `${formatNumber(n)} ${plural(n, 'продажа', 'продажи', 'продаж')}, ${doses(data.total_doses)} за период`;
	if (n === 0) {
		const td = el('td', 'text-center text-body-secondary py-4', 'За выбранный период продаж нет — измените период или фильтры');
		td.colSpan = 9;
		const tr = el('tr');
		tr.append(td);
		view.rows.replaceChildren(tr);
	} else {
		view.rows.replaceChildren(...data.rows.map(renderRow));
	}
	view.pageInfo.textContent = `Страница ${data.page} из ${data.pages}`;
	view.prev.disabled = data.page <= 1;
	view.next.disabled = data.page >= data.pages;
}

async function load() {
	// Ответы на быстрые правки фильтров могут прийти не по порядку — рисуем только последний запрос
	const request = ++lastRequest;
	try {
		const data = await callApi('salesList', {
			date_from: form.dateFrom.value,
			date_to: form.dateTo.value,
			buyer_id: form.buyer.value,
			bull_id: form.bull.value,
			page,
		});
		if (request !== lastRequest) return;
		showError(view.error, '');
		page = data.page;
		writeUrl();
		render(data);
	} catch (error) {
		if (request !== lastRequest) return;
		showError(view.error, `Не удалось загрузить журнал: ${error.message}`);
		view.summary.textContent = '';
		view.rows.replaceChildren();
		view.pageInfo.textContent = '';
		view.prev.disabled = view.next.disabled = true;
	}
}

function readUrl() {
	const q = new URLSearchParams(location.search);
	const period = defaultPeriod();
	form.dateFrom.value = q.get('from') || period.from;
	form.dateTo.value = q.get('to') || period.to;
	page = Number(q.get('page')) || 1;
	return { buyer: q.get('buyer') || '', bull: q.get('bull') || '', add: q.get('add') === '1' };
}

function writeUrl() {
	const q = new URLSearchParams({ from: form.dateFrom.value, to: form.dateTo.value });
	if (form.buyer.value) q.set('buyer', form.buyer.value);
	if (form.bull.value) q.set('bull', form.bull.value);
	if (page > 1) q.set('page', page);
	history.replaceState(null, '', `?${q}`);
}

function setSelect(select, value) {
	select.value = value;
	if (select.selectedIndex === -1) select.value = '';
}

function fillSelect(select, items, label) {
	for (const item of items) {
		const option = el('option', '', item.is_active ? label(item) : `${label(item)} (архив)`);
		option.value = item.id;
		select.append(option);
	}
}

function reload() {
	page = 1;
	load();
}

async function loadFormLists() {
	formLists ??= await callApi('saleForm');
	return formLists;
}

// Показывает активные значения и текущее, даже если оно уже в архиве
function fillOptions(select, items, placeholder, current, label = item => item.name) {
	const empty = el('option', '', placeholder);
	empty.value = '';
	select.replaceChildren(empty);
	for (const item of items) {
		const archived = item.is_active === 0;
		if (archived && item.id !== current) continue;
		const option = el('option', '', archived ? `${label(item)} (архив)` : label(item));
		option.value = item.id;
		select.append(option);
	}
	select.value = current ?? '';
}

function clearFormErrors() {
	showError(editor.error, '');
	for (const input of Object.values(fields)) {
		clearFieldError(input);
	}
}

function renderStockHint() {
	if (available === null) {
		editor.stock.textContent = '';
		editor.stock.classList.remove('text-danger');
		return;
	}
	const entered = Number(fields.doses.value);
	const after = available - entered;
	const overdraw = Number.isInteger(entered) && entered > 0 && after < 0;
	editor.stock.classList.toggle('text-danger', overdraw || available < 0);
	editor.stock.textContent = overdraw
		? `Доступно: ${doses(available)}. После продажи остаток станет ${doses(after)}`
		: `Доступно: ${doses(available)}`;
}

async function updateStockHint() {
	const bullId = fields.bull_id.value;
	const packagingId = fields.packaging_id.value;
	if (bullId === '' || packagingId === '') {
		available = null;
		renderStockHint();
		return;
	}
	// Подсказка необязательна: сбой или устаревший ответ не должны мешать вводу
	const request = ++stockRequest;
	try {
		const data = await callApi('stockAvailable', { bull_id: bullId, packaging_id: packagingId, sale_id: editingId });
		if (request !== stockRequest) return;
		available = data.doses;
	} catch {
		if (request !== stockRequest) return;
		available = null;
	}
	renderStockHint();
}

// sale === null — добавление, иначе правка строки журнала
async function openForm(sale) {
	let lists;
	try {
		lists = await loadFormLists();
	} catch (error) {
		toast(`Не удалось загрузить списки для формы: ${error.message}`, 'danger');
		return;
	}
	editingId = sale ? sale.id : null;
	clearFormErrors();
	editor.title.textContent = sale ? `Продажа № ${sale.id}` : 'Новая продажа';
	editor.submit.textContent = sale ? 'Сохранить' : 'Добавить';
	fields.sold_on.value = sale ? sale.sold_on : isoDate(new Date());
	fields.doses.value = sale ? sale.doses : '';
	fillOptions(fields.buyer_id, lists.buyers, '— выберите —', sale?.buyer_id);
	fillOptions(fields.contractor_id, lists.contractors, '— выберите —', sale?.contractor_id);
	fillOptions(fields.bull_id, lists.bulls, '— выберите —', sale?.bull_id, b => `${b.name} · ${b.num}`);
	fillOptions(fields.packaging_id, lists.packagings, '— выберите —', sale?.packaging_id);
	updateStockHint();
	saleModal.show();
}

function readFormValues() {
	const values = { id: editingId };
	for (const [key, input] of Object.entries(fields)) {
		values[key] = input.value;
	}
	return values;
}

function showFormErrors(errors) {
	for (const [key, input] of Object.entries(fields)) {
		const message = errors[key] || '';
		input.classList.toggle('is-invalid', message !== '');
		input.nextElementSibling.textContent = message;
	}
}

async function saveSale(event) {
	event.preventDefault();
	editor.submit.disabled = true;
	try {
		await callApi('saleSave', readFormValues());
	} catch (error) {
		const errors = error.fields || {};
		showFormErrors(errors);
		showError(editor.error, Object.keys(errors).length > 0 ? '' : error.message);
		return;
	} finally {
		editor.submit.disabled = false;
	}
	saleModal.hide();
	toast(editingId === null ? 'Продажа добавлена' : 'Продажа сохранена');
	load();
}

async function deleteSale(sale) {
	const text = `Удалить продажу от ${formatDate(sale.sold_on)}: ${sale.buyer}, ${sale.bull} · ${sale.bull_num}, ${doses(sale.doses)}? Она уйдёт в корзину.`;
	if (!await confirmDialog(text)) return;
	try {
		await callApi('saleDelete', { id: sale.id });
	} catch (error) {
		toast(`Не удалось удалить: ${error.message}`, 'danger');
		return;
	}
	toast('Продажа перенесена в корзину');
	load();
}

form.filters.addEventListener('submit', event => event.preventDefault());
for (const field of [form.dateFrom, form.dateTo]) {
	field.addEventListener('change', debounce(reload));
}
for (const field of [form.buyer, form.bull]) {
	field.addEventListener('change', reload);
}
view.prev.addEventListener('click', () => { page--; load(); });
view.next.addEventListener('click', () => { page++; load(); });
view.addSale.addEventListener('click', () => openForm(null));
editor.root.addEventListener('shown.bs.modal', () => fields.buyer_id.focus());
editor.form.addEventListener('submit', saveSale);
editor.form.addEventListener('input', event => clearFieldError(event.target));
fields.bull_id.addEventListener('change', updateStockHint);
fields.packaging_id.addEventListener('change', updateStockHint);
fields.doses.addEventListener('input', renderStockHint);

async function start() {
	const selected = readUrl();
	try {
		const filters = await callApi('salesFilters');
		fillSelect(form.buyer, filters.buyers, b => b.name);
		fillSelect(form.bull, filters.bulls, b => `${b.name} · ${b.num}`);
		setSelect(form.buyer, selected.buyer);
		setSelect(form.bull, selected.bull);
	} catch (error) {
		toast(`Не удалось загрузить списки покупателей и быков: ${error.message}`, 'danger');
	}
	load();
	if (selected.add) openForm(null);
}

start();
