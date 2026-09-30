const form = {
	filters: document.getElementById('filters'),
	search: document.getElementById('search'),
	breed: document.getElementById('breed'),
	vendor: document.getElementById('vendor'),
	archive: document.getElementById('archive'),
	inStock: document.getElementById('inStock'),
};
const view = {
	error: document.getElementById('error'),
	summary: document.getElementById('summary'),
	rows: document.getElementById('rows'),
	prev: document.getElementById('prev'),
	next: document.getElementById('next'),
	pageInfo: document.getElementById('pageInfo'),
	addBull: document.getElementById('addBull'),
};
const fields = {
	num: document.getElementById('bullNum'),
	name: document.getElementById('bullName'),
	breed_id: document.getElementById('bullBreed'),
	vendor_id: document.getElementById('bullVendor'),
	supplier_id: document.getElementById('bullSupplier'),
	category_id: document.getElementById('bullCategory'),
	category_year: document.getElementById('bullYear'),
};
const editor = {
	root: document.getElementById('bullModal'),
	form: document.getElementById('bullForm'),
	title: document.getElementById('bullModalTitle'),
	error: document.getElementById('bullFormError'),
	activeBox: document.getElementById('bullActiveBox'),
	active: document.getElementById('bullActive'),
	submit: document.getElementById('bullSubmit'),
};
const bullModal = bootstrap.Modal.getOrCreateInstance(editor.root);

let page = 1;
let lastRequest = 0;
let formLists = null;
let editingId = null;

function readUrl() {
	const q = new URLSearchParams(location.search);
	form.search.value = q.get('search') || '';
	setSelect(form.archive, q.get('archive') ?? '', 'active');
	form.inStock.checked = q.get('stock') === '1';
	page = Number(q.get('page')) || 1;
	return { breed: q.get('breed') || '', vendor: q.get('vendor') || '' };
}

function writeUrl() {
	const q = new URLSearchParams();
	const search = form.search.value.trim();
	if (search) q.set('search', search);
	if (form.breed.value) q.set('breed', form.breed.value);
	if (form.vendor.value) q.set('vendor', form.vendor.value);
	if (form.archive.value !== 'active') q.set('archive', form.archive.value);
	if (form.inStock.checked) q.set('stock', '1');
	if (page > 1) q.set('page', page);
	const query = q.toString();
	history.replaceState(null, '', query ? `?${query}` : location.pathname);
}

function setSelect(select, value, fallback) {
	select.value = value;
	if (select.selectedIndex === -1) select.value = fallback;
}

function fillSelect(select, items) {
	for (const item of items) {
		const option = el('option', '', item.name);
		option.value = item.id;
		select.append(option);
	}
}

async function load() {
	// Ответы на быстрый набор могут прийти не по порядку — рисуем только последний запрос
	const request = ++lastRequest;
	try {
		const data = await callApi('bullsList', {
			search: form.search.value,
			breed_id: form.breed.value,
			vendor_id: form.vendor.value,
			archive: form.archive.value,
			in_stock: form.inStock.checked,
			page,
		});
		if (request !== lastRequest) return;
		showError(view.error, '');
		page = data.page;
		writeUrl();
		render(data);
	} catch (error) {
		if (request !== lastRequest) return;
		showError(view.error, `Не удалось загрузить список: ${error.message}`);
		view.summary.textContent = '';
		view.rows.replaceChildren();
		view.pageInfo.textContent = '';
		view.prev.disabled = view.next.disabled = true;
	}
}

function categoryText(bull) {
	if (!bull.category) return '';
	return bull.category_year ? `${bull.category} (${bull.category_year})` : bull.category;
}

function renderRow(bull) {
	const name = el('td', 'fw-medium', bull.name);
	if (!bull.is_active) {
		name.append(' ', el('span', 'badge text-bg-secondary', 'архив'));
	}

	const stock = el('td', 'text-end num', bull.stock === null ? '' : formatNumber(bull.stock));
	if (bull.stock < 0) {
		stock.classList.add('text-danger', 'fw-semibold');
	}

	const edit = el('button', 'btn btn-sm btn-outline-secondary', 'Изменить');
	edit.type = 'button';
	edit.addEventListener('click', () => openForm(bull));

	const actions = el('td', 'text-end text-nowrap');
	actions.append(edit);

	const tr = el('tr');
	tr.append(
		el('td', 'num', bull.num),
		name,
		el('td', '', bull.breed),
		el('td', '', categoryText(bull)),
		el('td', 'wrap', bull.vendor ?? ''),
		el('td', 'wrap', bull.supplier),
		stock,
		actions,
	);
	return tr;
}

function render(data) {
	const n = data.total_rows;
	view.summary.textContent = `Найдено ${formatNumber(n)} ${plural(n, 'бык', 'быка', 'быков')}`;
	if (n === 0) {
		const td = el('td', 'text-center text-body-secondary py-4', 'Ничего не найдено — измените поиск или фильтры');
		td.colSpan = 8;
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

function reload() {
	page = 1;
	load();
}

async function loadFormLists() {
	formLists ??= await callApi('bullForm');
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
		input.classList.remove('is-invalid');
		input.nextElementSibling.textContent = '';
	}
}

// bull === null — добавление, иначе правка строки списка
async function openForm(bull) {
	let lists;
	try {
		lists = await loadFormLists();
	} catch (error) {
		toast(`Не удалось загрузить списки для формы: ${error.message}`, 'danger');
		return;
	}
	editingId = bull ? bull.id : null;
	clearFormErrors();
	editor.title.textContent = bull ? `Бык ${bull.name}, № ${bull.num}` : 'Новый бык';
	editor.submit.textContent = bull ? 'Сохранить' : 'Добавить';
	fields.num.value = bull?.num ?? '';
	fields.name.value = bull?.name ?? '';
	fields.category_year.value = bull?.category_year ?? '';
	fillOptions(fields.breed_id, lists.breeds, '— выберите —', bull?.breed_id);
	fillOptions(fields.vendor_id, lists.contractors, '— не указан —', bull?.vendor_id);
	fillOptions(fields.supplier_id, lists.contractors, '— выберите —', bull?.supplier_id);
	fillOptions(fields.category_id, lists.categories, '— не указана —', bull?.category_id, c => c.code);
	editor.activeBox.hidden = !bull;
	editor.active.checked = bull ? Boolean(bull.is_active) : true;
	bullModal.show();
}

function readFormValues() {
	const values = { id: editingId };
	for (const [key, input] of Object.entries(fields)) {
		values[key] = input.value;
	}
	if (editingId !== null) {
		values.is_active = editor.active.checked;
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

async function saveBull(event) {
	event.preventDefault();
	editor.submit.disabled = true;
	try {
		await callApi('bullSave', readFormValues());
	} catch (error) {
		const errors = error.fields || {};
		showFormErrors(errors);
		showError(editor.error, Object.keys(errors).length > 0 ? '' : error.message);
		return;
	} finally {
		editor.submit.disabled = false;
	}
	bullModal.hide();
	toast(editingId === null ? 'Бык добавлен' : 'Бык сохранён');
	load();
}

form.filters.addEventListener('submit', event => event.preventDefault());
form.search.addEventListener('input', debounce(reload));
for (const field of [form.breed, form.vendor, form.archive, form.inStock]) {
	field.addEventListener('change', reload);
}
view.prev.addEventListener('click', () => { page--; load(); });
view.next.addEventListener('click', () => { page++; load(); });

view.addBull.addEventListener('click', () => openForm(null));
editor.root.addEventListener('shown.bs.modal', () => fields.num.focus());
editor.form.addEventListener('submit', saveBull);

async function start() {
	const selected = readUrl();
	try {
		const filters = await callApi('bullsFilters');
		fillSelect(form.breed, filters.breeds);
		fillSelect(form.vendor, filters.vendors);
		setSelect(form.breed, selected.breed, '');
		setSelect(form.vendor, selected.vendor, '');
	} catch (error) {
		toast(`Не удалось загрузить породы и производителей: ${error.message}`, 'danger');
	}
	load();
}

start();
