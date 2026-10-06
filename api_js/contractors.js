dictionarySection({
	list: 'contractorsList',
	search: 'Поиск по названию, месту или адресу',
	editor: counterpartyForm({
		save: 'contractorSave',
		newTitle: 'Новый контрагент',
		added: 'Контрагент добавлен',
		saved: 'Контрагент сохранён',
	}),
	columns: [
		{ name: 'name', label: 'Название', wrap: true, searchable: true },
		{ name: 'location', label: 'Место', wrap: true, searchable: true },
		{ name: 'address', label: 'Адрес', wrap: true, searchable: true },
		{ name: 'bulls', label: 'Быков', format: formatNumber },
		{ name: 'last_sale', label: 'Посл. продажа', format: formatDate },
		{ name: 'is_active', label: 'Активен', type: 'checkbox' },
	],
});
