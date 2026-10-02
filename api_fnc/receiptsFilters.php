<?php

function receiptsFilters(array $post): array {
	// Только быки с поступлениями, архивные тоже: иначе их историю нельзя отфильтровать
	return [
		'success' => true,
		'bulls'   => db()->query(
			'SELECT b.id, b.num, b.name, b.is_active
			 FROM bulls b
			 WHERE EXISTS (SELECT 1 FROM receipts r WHERE r.bull_id = b.id)
			 ORDER BY b.name, b.num, b.id'
		)->fetchAll(),
	];
}

$registered_fnc[] = 'receiptsFilters';

?>
