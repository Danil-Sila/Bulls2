const form = {
	filters: document.getElementById('filters'),
	dateFrom: document.getElementById('dateFrom'),
	dateTo: document.getElementById('dateTo'),
	bull: document.getElementById('bull'),
};
const view = {
	error: document.getElementById('error'),
	summary: document.getElementById('summary'),
	rows: document.getElementById('rows'),
	prev: document.getElementById('prev'),
	next: document.getElementById('next'),
	pageInfo: document.getElementById('pageInfo'),
	addReceipt: document.getElementById('addReceipt'),
};
const fields = {
	received_on: document.getElementById('receiptDate'),
	bull_id: document.getElementById('receiptBull'),
	packaging_id: document.getElementById('receiptPackaging'),
	doses: document.getElementById('receiptDoses'),
};
const editor = {
	root: document.getElementById('receiptModal'),
	form: document.getElementById('receiptForm'),
	title: document.getElementById('receiptModalTitle'),
	error: document.getElementById('receiptFormError'),
	submit: document.getElementById('receiptSubmit'),
};
const receiptModal = bootstrap.Modal.getOrCreateInstance(editor.root);

let page = 1;
let lastRequest = 0;
let formLists = null;
let editingId = null;

function renderRow(receipt) {
	const bull = el('td');
	bull.append(el('span', 'fw-medium', receipt.bull), el('span', 'text-body-secondary ms-1', receipt.bull_num));

	const edit = el('button', 'btn btn-sm btn-outline-secondary', 'Изменить');
	edit.type = 'button';
	edit.addEventListener('click', () => openForm(receipt));

	const remove = el('button', 'btn btn-sm btn-outline-danger ms-1', 'Удалить');
	remove.type = 'button';
	remove.addEventListener('click', () => deleteReceipt(receipt));

	const actions = el('td', 'text-end text-nowrap');
	actions.append(edit, remove);

	const tr = el('tr');
	tr.append(
		el('td', 'num', formatDate(receipt.received_on)),
		bull,
		el('td', '', receipt.breed),
		el('td', 'text-end num fw-semibold', formatNumber(receipt.doses)),
		el('td', '', receipt.packaging),
		el('td', 'text-end num text-body-secondary', receipt.id),
		actions,
	);
	return tr;
}

function render(data) {
	const n = data.total_rows;
	view.summary.textContent = `${formatNumber(n)} ${plural(n, 'поступление', 'поступления', 'поступлений')}, ${formatDoses(data.total_doses)} за период`;
	if (n === 0) {
		const td = el('td', 'text-center text-body-secondary py-4', 'За выбранный период поступлений нет — измените период или фильтры');
		td.colSpan = 7;
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
		const data = await callApi('receiptsList', {
			date_from: form.dateFrom.value,
			date_to: form.dateTo.value,
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
	// 90 дней, а не 30: поступлений около ста в год, за месяц журнал часто пуст
	const period = defaultPeriod(90);
	form.dateFrom.value = q.get('from') || period.from;
	form.dateTo.value = q.get('to') || period.to;
	page = Number(q.get('page')) || 1;
	return { bull: q.get('bull') || '', add: q.get('add') === '1' };
}

function writeUrl() {
	const q = new URLSearchParams({ from: form.dateFrom.value, to: form.dateTo.value });
	if (form.bull.value) q.set('bull', form.bull.value);
	if (page > 1) q.set('page', page);
	history.replaceState(null, '', `?${q}`);
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
	formLists ??= await callApi('receiptForm');
	return formLists;
}

// receipt === null — добавление, иначе правка строки журнала
async function openForm(receipt) {
	let lists;
	try {
		lists = await loadFormLists();
	} catch (error) {
		toast(`Не удалось загрузить списки для формы: ${error.message}`, 'danger');
		return;
	}
	editingId = receipt ? receipt.id : null;
	clearFormErrors(fields, editor.error);
	editor.title.textContent = receipt ? `Поступление № ${receipt.id}` : 'Новое поступление';
	editor.submit.textContent = receipt ? 'Сохранить' : 'Добавить';
	fields.received_on.value = receipt ? receipt.received_on : isoDate(new Date());
	fields.doses.value = receipt ? receipt.doses : '';
	fillOptions(fields.bull_id, lists.bulls, '— выберите —', receipt?.bull_id, b => `${b.name} · ${b.num}`);
	fillOptions(fields.packaging_id, lists.packagings, '— выберите —', receipt?.packaging_id);
	receiptModal.show();
}

function readFormValues() {
	const values = { id: editingId };
	for (const [key, input] of Object.entries(fields)) {
		values[key] = input.value;
	}
	return values;
}

async function saveReceipt(event) {
	event.preventDefault();
	editor.submit.disabled = true;
	try {
		await callApi('receiptSave', readFormValues());
	} catch (error) {
		const errors = error.fields || {};
		showFormErrors(fields, errors);
		showError(editor.error, Object.keys(errors).length > 0 ? '' : error.message);
		return;
	} finally {
		editor.submit.disabled = false;
	}
	receiptModal.hide();
	toast(editingId === null ? 'Поступление добавлено' : 'Поступление сохранено');
	load();
}

async function deleteReceipt(receipt) {
	const text = `Удалить поступление от ${formatDate(receipt.received_on)}: ${receipt.bull} · ${receipt.bull_num}, ${formatDoses(receipt.doses)}? Это действие нельзя отменить.`;
	if (!await confirmDialog(text)) return;
	try {
		await callApi('receiptDelete', { id: receipt.id });
	} catch (error) {
		toast(`Не удалось удалить: ${error.message}`, 'danger');
		return;
	}
	toast('Поступление удалено');
	load();
}

form.filters.addEventListener('submit', event => event.preventDefault());
for (const field of [form.dateFrom, form.dateTo]) {
	field.addEventListener('change', debounce(reload));
}
form.bull.addEventListener('change', reload);
view.prev.addEventListener('click', () => { page--; load(); });
view.next.addEventListener('click', () => { page++; load(); });
view.addReceipt.addEventListener('click', () => openForm(null));
editor.root.addEventListener('shown.bs.modal', () => fields.bull_id.focus());
editor.form.addEventListener('submit', saveReceipt);
editor.form.addEventListener('input', event => clearFieldError(event.target));

async function start() {
	const selected = readUrl();
	try {
		const filters = await callApi('receiptsFilters');
		fillSelect(form.bull, filters.bulls, b => `${b.name} · ${b.num}`);
		setSelect(form.bull, selected.bull);
	} catch (error) {
		toast(`Не удалось загрузить список быков: ${error.message}`, 'danger');
	}
	load();
	if (selected.add) openForm(null);
}

start();
