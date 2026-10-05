// Заменяет длинный <select> полем с поиском. Настоящий select остаётся скрытым хранителем значения,
// поэтому код форм по-прежнему читает select.value и слушает его change
function searchSelect(select, placeholder = 'Начните вводить…', limit = 6) {
	const wrap = el('div', 'position-relative');
	const input = el('input', 'form-control');
	input.type = 'text';
	input.id = `${select.id}Search`;
	input.autocomplete = 'off';
	input.spellcheck = false;
	input.placeholder = placeholder;
	const list = el('div', 'list-group position-absolute w-100 shadow mt-1 z-3');
	list.hidden = true;
	wrap.append(input, list);

	select.before(wrap);
	select.hidden = true;
	// По этому полю api.js рисует красную рамку ошибки: у скрытого select её не видно
	select.pickerInput = input;
	document.querySelector(`label[for="${select.id}"]`).htmlFor = input.id;

	const normalize = text => text.toLowerCase().replaceAll('ё', 'е');

	function find(text) {
		const words = normalize(text).split(/\s+/).filter(Boolean);
		const found = [];
		for (const option of select.options) {
			if (option.value === '') continue;
			const label = normalize(option.text);
			if (!words.every(word => label.includes(word))) continue;
			// Совпадения с начала слова ставим выше совпадений внутри слова: «род» → «Родина» раньше «Пригородного»
			const wordStart = words.every(word => label.startsWith(word) || label.includes(` ${word}`));
			found.push({ option, wordStart });
		}
		return found.sort((a, b) => b.wordStart - a.wordStart).map(item => item.option);
	}

	function choose(option) {
		select.value = option.value;
		input.value = option.text;
		list.hidden = true;
		clearFieldError(select);
		select.dispatchEvent(new Event('change', { bubbles: true }));
	}

	function showList() {
		if (input.value.trim() === '') {
			list.hidden = true;
			return;
		}
		const found = find(input.value);
		const items = found.slice(0, limit).map(option => {
			const item = el('button', 'list-group-item list-group-item-action', option.text);
			item.type = 'button';
			item.tabIndex = -1;
			item.addEventListener('click', () => choose(option));
			return item;
		});
		if (found.length === 0) items.push(el('div', 'list-group-item text-body-secondary', 'Ничего не найдено'));
		if (found.length > limit) items.push(el('div', 'list-group-item small text-body-secondary', `Ещё ${found.length - limit} — уточните запрос`));
		list.replaceChildren(...items);
		list.hidden = false;
	}

	// Вызывать после того, как значение select поставили из кода (например, при открытии формы на правку)
	function sync() {
		const option = select.selectedOptions[0];
		input.value = option && option.value !== '' ? option.text : '';
		list.hidden = true;
	}
		input.addEventListener('input', () => {
		if (select.value !== '') {
			select.value = '';
			select.dispatchEvent(new Event('change', { bubbles: true }));
		}
		clearFieldError(select);
		showList();
	});
	input.addEventListener('focus', () => {
		input.select();
		if (select.value === '') showList();
	});
	// Enter в текстовом поле иначе отправил бы форму
	input.addEventListener('keydown', event => {
		if (event.key === 'Enter') event.preventDefault();
	});

	// Ушли из поля, не выбрав подсказку: недопечатанный текст убираем, чтобы поле не выглядело выбранным
	function closeOutside(event) {
		if (wrap.contains(event.target)) return;
		list.hidden = true;
		if (select.value === '') input.value = '';
	}
	document.addEventListener('click', closeOutside);
	document.addEventListener('focusin', closeOutside);

	return { sync, focus: () => input.focus() };
}
