dictionarySection({
	list: 'buyersList',
	search: 'Поиск по названию, месту или адресу',
	editor: counterpartyForm({
		save: 'buyerSave',
		newTitle: 'Новый покупатель',
		added: 'Покупатель добавлен',
		saved: 'Покупатель сохранён',
	}),
	columns: [
		{ name: 'name', label: 'Название', wrap: true, searchable: true },
		{ name: 'location', label: 'Место', wrap: true, searchable: true },
		{ name: 'address', label: 'Адрес', wrap: true, searchable: true },
		{ name: 'last_sale', label: 'Посл. продажа', format: formatDate },
		{ name: 'is_active', label: 'Активен', type: 'checkbox' },
	],
});
