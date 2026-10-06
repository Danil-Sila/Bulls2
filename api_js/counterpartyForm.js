// Форма покупателя и контрагента: название, регион → район, адрес. Разметка — views/counterparty_form.php.
// Возвращает { open(item, afterSave) }: item === null — добавление, иначе правка строки списка
function counterpartyForm(config) {
	const editor = {
		root: document.getElementById('cpModal'),
		form: document.getElementById('cpForm'),
		title: document.getElementById('cpModalTitle'),
		error: document.getElementById('cpFormError'),
		activeBox: document.getElementById('cpActiveBox'),
		active: document.getElementById('cpActive'),
		submit: document.getElementById('cpSubmit'),
	};
	// Ошибки сервера приходят по имени поля location_id, поэтому с ним связан именно регион
	const fields = {
		name: document.getElementById('cpName'),
		location_id: document.getElementById('cpRegion'),
		address: document.getElementById('cpAddress'),
	};
	const district = document.getElementById('cpDistrict');
	const modal = bootstrap.Modal.getOrCreateInstance(editor.root);
	const regionPicker = searchSelect(fields.location_id, 'Начните вводить регион…');
	const districtPicker = searchSelect(district, 'Начните вводить район…');

	let lists = null;
	let editingId = null;
	let onSaved = null;

	function selectedRegion() {
		return Number(fields.location_id.value) || null;
	}

	function fillDistricts(regionId, current) {
		const items = lists.districts.filter(d => d.parent_id === regionId);
		fillOptions(district, items, '— не указан —', current);
		district.pickerInput.disabled = items.length === 0;
		if (regionId === null) {
			district.pickerInput.placeholder = 'Сначала выберите регион';
		} else {
			district.pickerInput.placeholder = items.length > 0 ? 'Начните вводить район…' : 'У региона нет районов';
		}
		districtPicker.sync();
	}

	async function open(item, afterSave) {
		try {
			lists ??= await callApi('locationsList');
		} catch (error) {
			toast(`Не удалось загрузить справочник мест: ${error.message}`, 'danger');
			return;
		}
		editingId = item ? item.id : null;
		onSaved = afterSave;
		clearFormErrors(fields, editor.error);
		editor.title.textContent = item ? item.name : config.newTitle;
		editor.submit.textContent = item ? 'Сохранить' : 'Добавить';
		fields.name.value = item?.name ?? '';
		fields.address.value = item?.address ?? '';
		// В базе хранится одно место: район или, если его нет, регион
		const current = lists.districts.find(d => d.id === item?.location_id);
		fillOptions(fields.location_id, lists.regions, '— не указан —', current ? current.parent_id : item?.location_id);
		regionPicker.sync();
		fillDistricts(selectedRegion(), current?.id ?? null);
		editor.activeBox.hidden = !item;
		editor.active.checked = item ? Boolean(item.is_active) : true;
		modal.show();
	}

	function readFormValues() {
		const values = {
			id: editingId,
			name: fields.name.value,
			location_id: district.value || fields.location_id.value,
			address: fields.address.value,
		};
		if (editingId !== null) {
			values.is_active = editor.active.checked;
		}
		return values;
	}

	async function save(event) {
		event.preventDefault();
		editor.submit.disabled = true;
		try {
			await callApi(config.save, readFormValues());
		} catch (error) {
			const errors = error.fields || {};
			showFormErrors(fields, errors);
			showError(editor.error, Object.keys(errors).length > 0 ? '' : error.message);
			return;
		} finally {
			editor.submit.disabled = false;
		}
		modal.hide();
		toast(editingId === null ? config.added : config.saved);
		onSaved();
	}

	fields.location_id.addEventListener('change', () => fillDistricts(selectedRegion(), null));
	editor.root.addEventListener('shown.bs.modal', () => fields.name.focus());
	editor.form.addEventListener('submit', save);
	editor.form.addEventListener('input', event => clearFieldError(event.target));

	return { open };
}
