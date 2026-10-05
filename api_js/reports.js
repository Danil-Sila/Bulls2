// sender — серверная функция отчёта, title — название в списке и в заголовке
const reports = {
	bulls: { sender: 'reportBulls', title: 'По быкам' },
	vendors: { sender: 'reportVendors', title: 'По производителям' },
	buyers: { sender: 'reportBuyers', title: 'По покупателям' },
	stock: { sender: 'reportStock', title: 'Остатки на складе', kind: 'none' },
	movement: { sender: 'reportMovement', title: 'Движение семени', kind: 'month' },
	bullMonths: { sender: 'reportBullMonths', title: 'Быки × месяцы по покупателю', buyer: true },
	bullsFarms: { sender: 'reportBullsFarms', title: 'Быки по хозяйствам' },
};
const monthNames = ['январь', 'февраль', 'март', 'апрель', 'май', 'июнь', 'июль', 'август', 'сентябрь', 'октябрь', 'ноябрь', 'декабрь'];
const form = {
	filters: document.getElementById('filters'),
	report: document.getElementById('report'),
	dateFrom: document.getElementById('dateFrom'),
	dateTo: document.getElementById('dateTo'),
	month: document.getElementById('month'),
	year: document.getElementById('year'),
	kindFields: document.querySelectorAll('[data-kind]'),
	buyer: document.getElementById('buyer'),
	buyerField: document.querySelector('[data-buyer]'),
};
const view = {
	error: document.getElementById('error'),
	result: document.getElementById('result'),
	title: document.getElementById('reportTitle'),
	head: document.getElementById('head'),
	rows: document.getElementById('rows'),
	totals: document.getElementById('totals'),
	print: document.getElementById('printReport'),
	csv: document.getElementById('exportCsv'),
};

let lastRequest = 0;
let current = null;

function fillReports() {
	for (const [key, report] of Object.entries(reports)) {
		const option = el('option', '', report.title);
		option.value = key;
		form.report.append(option);
	}
}

// Какие поля нужны отчёту: period (по умолчанию) — даты «с» и «по», month — месяц и год, none — никаких
function kindOf(report) {
	return report.kind ?? 'period';
}

function toggleFields(report) {
	for (const field of form.kindFields) {
		field.hidden = field.dataset.kind !== kindOf(report);
	}
	form.buyerField.hidden = !report.buyer;
}

// Покупатели, у которых есть продажи (как в фильтре журнала); архивные тоже, с пометкой
async function fillBuyers() {
	try {
		const data = await callApi('salesFilters');
		const empty = el('option', '', 'Выберите покупателя');
		empty.value = '';
		form.buyer.replaceChildren(empty);
		for (const buyer of data.buyers) {
			const option = el('option', '', buyer.is_active ? buyer.name : `${buyer.name} (архив)`);
			option.value = buyer.id;
			form.buyer.append(option);
		}
	} catch (error) {
		showError(view.error, `Не удалось загрузить список покупателей: ${error.message}`);
	}
}

function fillMonths() {
	monthNames.forEach((name, index) => {
		const option = el('option', '', name);
		option.value = index + 1;
		form.month.append(option);
	});
}

// setDate(1) до setMonth: иначе 31 октября «минус месяц» превратилось бы в 1 октября (в сентябре нет 31-го)
function lastMonth() {
	const date = new Date();
	date.setDate(1);
	date.setMonth(date.getMonth() - 1);
	return { month: date.getMonth() + 1, year: date.getFullYear() };
}

function readUrl() {
	const q = new URLSearchParams(location.search);
	const period = defaultPeriod(30);
	const last = lastMonth();
	form.report.value = Object.hasOwn(reports, q.get('report')) ? q.get('report') : Object.keys(reports)[0];
	form.dateFrom.value = q.get('from') || period.from;
	form.dateTo.value = q.get('to') || period.to;
	setSelect(form.month, q.get('month') || last.month, last.month);
	form.year.value = q.get('year') || last.year;
	setSelect(form.buyer, q.get('buyer') || '');
}

function readParams(report) {
	switch (kindOf(report)) {
		case 'none':
			return {};
		case 'month':
			return { year: Number(form.year.value), month: Number(form.month.value) };
		default: {
			const params = { date_from: form.dateFrom.value, date_to: form.dateTo.value };
			if (report.buyer) params.buyer_id = form.buyer.value;
			return params;
		}
	}
}

function writeUrl(report) {
	const q = new URLSearchParams({ report: form.report.value });
	const kind = kindOf(report);
	if (kind === 'period') {
		q.set('from', form.dateFrom.value);
		q.set('to', form.dateTo.value);
	} else if (kind === 'month') {
		q.set('month', form.month.value);
		q.set('year', form.year.value);
	}

	if (report.buyer) q.set('buyer', form.buyer.value);

	history.replaceState(null, '', `?${q}`);
}

// Заголовок и имя файла CSV: в них период, месяц или (у остатков) сегодняшняя дата
function describeReport(key, report) {
	const kind = kindOf(report);
	if (kind === 'none') {
		const today = isoDate(new Date());
		return { title: `${report.title} на ${formatDate(today)}`, file: `report-${key}-${today}` };
	}
	if (kind === 'month') {
		const month = form.month.value;
		const year = form.year.value;
		return { title: `${report.title} за ${monthNames[month - 1]} ${year}`, file: `report-${key}-${year}-${month.padStart(2, '0')}` };
	}
	const from = form.dateFrom.value;
	const to = form.dateTo.value;
	const who = report.buyer ? ` «${form.buyer.selectedOptions[0].text}»` : '';
	const suffix = report.buyer ? `-${form.buyer.value}` : '';
	return { title: `${report.title}${who} за ${formatDate(from)} – ${formatDate(to)}`, file: `report-${key}${suffix}-${from}_${to}` };
}

async function load() {
	// Ответы на быстрые правки могут прийти не по порядку — рисуем только последний запрос
	const request = ++lastRequest;
	const key = form.report.value;
	const report = reports[key];
	toggleFields(report);

	try {
		const data = await callApi(report.sender, readParams(report));
		if (request !== lastRequest) return;
		showError(view.error, '');
		writeUrl(report);
		current = { ...data, ...describeReport(key, report) };
		render(current);
	} catch (error) {
		if (request !== lastRequest) return;
		showError(view.error, `Не удалось сформировать отчёт: ${error.message}`);
		view.result.hidden = true;
		view.print.disabled = view.csv.disabled = true;
		current = null;
	}
}

function renderHeadCell(column) {
	return el('th', column.type === 'number' ? 'text-end' : '', column.title);
}

function renderCell(column, value) {
	if (value === null) return el('td');
	if (column.type === 'number') {
		return el('td', value < 0 ? 'text-end num text-danger' : 'text-end num', formatNumber(value));
	}
	return el('td', column.wrap ? 'wrap' : '', String(value));
}

function renderRow(columns, row) {
	const tr = el('tr');
	tr.append(...columns.map(column => renderCell(column, row[column.key])));
	return tr;
}

function renderFooter(columns, row) {
	const tr = renderRow(columns, row);
	tr.className = 'fw-semibold table-light';
	return tr;
}

function renderTotal(total) {
	const line = el('div');
	line.append(`${total.label}: `, el('b', total.value < 0 ? 'num text-danger' : 'num', formatNumber(total.value)));
	if (total.parts?.length) {
		const parts = total.parts.map(part => `${part.label} ${formatNumber(part.value)}`);
		line.append(` (${parts.join(', ')})`);
	}
	return line;
}

function render(data) {
	view.title.textContent = data.title;
	view.head.replaceChildren(...data.columns.map(renderHeadCell));
	if (data.rows.length === 0) {
		const td = el('td', 'text-center text-body-secondary py-4', 'За выбранный период данных нет');
		td.colSpan = data.columns.length;
		const tr = el('tr');
		tr.append(td);
		view.rows.replaceChildren(tr);
	} else {
		view.rows.replaceChildren(...data.rows.map(row => renderRow(data.columns, row)));
		if (data.footer) view.rows.append(renderFooter(data.columns, data.footer));
	}
	view.totals.replaceChildren(...data.totals.map(renderTotal));
	view.result.hidden = false;
	view.print.disabled = view.csv.disabled = data.rows.length === 0;
}

// Ячейка с разделителем, кавычкой или переводом строки берётся в кавычки, кавычка внутри удваивается
function csvCell(value) {
	if (value === null) return '';
	const text = String(value);
	return /[";\r\n]/.test(text) ? `"${text.replaceAll('"', '""')}"` : text;
}

function buildCsv(data) {
	const lines = [data.columns.map(column => column.title)];
	for (const row of data.rows) {
		lines.push(data.columns.map(column => row[column.key]));
	}
	if (data.footer) lines.push(data.columns.map(column => data.footer[column.key]));
	lines.push([]);
	for (const total of data.totals) {
		const line = [total.label, total.value];
		if (total.parts?.length) line.push(total.parts.map(part => `${part.label} ${part.value}`).join(', '));
		lines.push(line);
	}
	return lines.map(cells => cells.map(csvCell).join(';')).join('\r\n');
}

function downloadCsv() {
	// BOM в начале: без него Excel откроет UTF-8 как «кракозябры»
	const blob = new Blob(['\uFEFF' + buildCsv(current)], { type: 'text/csv;charset=utf-8' });
	const url = URL.createObjectURL(blob);
	const link = el('a');
	link.href = url;
	link.download = `${current.file}.csv`;	link.click();
	setTimeout(() => URL.revokeObjectURL(url), 1000);
}

form.filters.addEventListener('change', load);
view.print.addEventListener('click', () => window.print());
view.csv.addEventListener('click', downloadCsv);

async function init() {
	fillReports();
	fillMonths();
	await fillBuyers();
	readUrl();
	load();
}

init();
