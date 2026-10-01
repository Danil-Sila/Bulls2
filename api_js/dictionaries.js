function smallButton(text, className) {
	const button = el('button', `btn btn-sm ${className}`, text);
	button.type = 'button';
	return button;
}

function displayValue(column, value) {
	if (column.type === 'checkbox') {
		return value ? '✓' : 'архив';
	}
	if (value === null || value === undefined || value === '') {
		return '';
	}
	return column.format ? column.format(value) : value;
}

function fieldCell(column, value) {
	const td = el('td');
	if (column.readonly) {
		td.textContent = displayValue(column, value);
		return td;
	}
	const input = el('input', column.type === 'checkbox' ? 'form-check-input' : 'form-control form-control-sm');
	input.name = column.name;
	if (column.type === 'checkbox') {
		input.type = 'checkbox';
		input.checked = Boolean(value);
	} else {
		input.value = value ?? '';
		input.maxLength = column.maxLength;
		input.placeholder = column.label;
	}
	td.append(input, el('div', 'invalid-feedback'));
	return td;
}

function readFields(row) {
	const values = {};
	for (const input of row.querySelectorAll('input[name]')) {
		values[input.name] = input.type === 'checkbox' ? input.checked : input.value;
	}
	return values;
}

function showFieldErrors(row, fields) {
	for (const input of row.querySelectorAll('input[name]')) {
		const message = fields[input.name] || '';
		input.classList.toggle('is-invalid', message !== '');
		input.nextElementSibling.textContent = message;
	}
}

async function saveRow(sender, row, params, button) {
	button.disabled = true;
	try {
		await callApi(sender, params);
		return true;
	} catch (error) {
		showFieldErrors(row, error.fields || {});
		if (!error.fields || Object.keys(error.fields).length === 0) {
			toast(error.message, 'danger');
		}
		return false;
	} finally {
		button.disabled = false;
	}
}

// Строит карточку справочника по описанию: заголовок, функции API, колонки, тексты уведомлений.
function dictionarySection(config) {
	const error = el('div', 'alert alert-danger m-3');
	error.setAttribute('role', 'alert');
	error.hidden = true;
	const head = el('tr');
	head.append(el('th', 'text-end', '№'), ...config.columns.map(c => el('th', '', c.label)), el('th'));
	const thead = el('thead', 'table-light');
	thead.append(head);
	const rows = el('tbody');
	const newRow = el('tr');
	const tfoot = el('tfoot');
	tfoot.append(newRow);
	const table = el('table', 'table table-sm align-middle mb-0');
	table.append(thead, rows, tfoot);
	table.addEventListener('input', event => clearFieldError(event.target));
	const wrap = el('div', 'table-responsive');
	wrap.append(table);
	const card = el('section', 'card mb-4');
	if (config.title) {
		card.append(el('h2', 'card-header h6 fw-semibold mb-0', config.title));
	}
	card.append(error);

	let query = '';
	if (config.search) {
		const search = el('input', 'form-control form-control-sm');
		search.type = 'search';
		search.placeholder = config.search;
		search.addEventListener('input', () => {
			query = search.value.trim().toLowerCase();
			applyFilter();
		});
		const searchBox = el('div', 'p-2 border-bottom');
		searchBox.append(search);
		card.append(searchBox);
	}
	card.append(wrap);
	document.getElementById('dictionaries').append(card);

	function applyFilter() {
		for (const tr of rows.children) {
			tr.hidden = query !== '' && !tr.dataset.search.includes(query);
		}
	}

	function actionsCell(...buttons) {
		const td = el('td', 'text-end text-nowrap');
		td.append(...buttons);
		return td;
	}

	function renderRow(item) {
		const tr = el('tr');
		tr.dataset.search = config.columns
			.filter(c => !c.readonly && c.type !== 'checkbox')
			.map(c => item[c.name] ?? '')
			.join(' ')
			.toLowerCase();
		const edit = smallButton('Изменить', 'btn-outline-secondary');
		edit.addEventListener('click', () => editRow(tr, item));
		tr.append(
			el('td', 'text-end num text-body-secondary', item.id),
			...config.columns.map(c => el('td', c.wrap ? 'wrap' : '', displayValue(c, item[c.name]))),
			actionsCell(edit),
		);
		return tr;
	}

	function editRow(tr, item) {
		const save = smallButton('Сохранить', 'btn-primary');
		const cancel = smallButton('Отмена', 'btn-outline-secondary ms-1');
		tr.replaceChildren(
			el('td', 'text-end num text-body-secondary', item.id),
			...config.columns.map(c => fieldCell(c, item[c.name])),
			actionsCell(save, cancel),
		);
		cancel.addEventListener('click', () => tr.replaceWith(renderRow(item)));
		save.addEventListener('click', async () => {
			if (await saveRow(config.save, tr, { id: item.id, ...readFields(tr) }, save)) {
				toast(config.saved);
				load();
			}
		});
	}

	function resetNewRow() {
		newRow.replaceChildren(el('td'), ...config.columns.map(c => fieldCell(c, c.type === 'checkbox' ? true : '')), actionsCell(add));
	}

	async function load() {
		showError(error, '');
		try {
			const data = await callApi(config.list);
			rows.replaceChildren(...data.rows.map(renderRow));
			applyFilter();
		} catch (e) {
			showError(error, `Не удалось загрузить: ${e.message}`);
		}
	}

	const add = smallButton('Добавить', 'btn-success');
	add.addEventListener('click', async () => {
		if (await saveRow(config.save, newRow, readFields(newRow), add)) {
			toast(config.added);
			resetNewRow();
			load();
		}
	});

	resetNewRow();
	load();
}
