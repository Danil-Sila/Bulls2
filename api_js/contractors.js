dictionarySection({
	list: 'contractorsList',
	save: 'contractorSave',
	search: 'Поиск по названию или месту',
	columns: [
		{ name: 'name', label: 'Название', maxLength: 150, wrap: true },
		{ name: 'location', label: 'Место', maxLength: 255, wrap: true },
		{ name: 'bulls', label: 'Быков', readonly: true, format: formatNumber },
		{ name: 'last_sale', label: 'Посл. продажа', readonly: true, format: formatDate },
		{ name: 'is_active', label: 'Активен', type: 'checkbox' },
	],
	added: 'Контрагент добавлен',
	saved: 'Контрагент сохранён',
});
