<?php

function salesFilters(array $post): array {
	// Только те, у кого есть продажи: это фильтр журнала, а не список для выбора в форме,
	// поэтому архивные тоже нужны — иначе их историю нельзя отфильтровать
	return [
		'success' => true,
		'buyers'  => db()->query(
			'SELECT b.id, b.name, b.is_active
			 FROM buyers b
			 WHERE EXISTS (SELECT 1 FROM sales s WHERE s.buyer_id = b.id)
			 ORDER BY b.name, b.id'
		)->fetchAll(),
		'bulls'   => db()->query(
			'SELECT b.id, b.num, b.name, b.is_active
			 FROM bulls b
			 WHERE EXISTS (SELECT 1 FROM sales s WHERE s.bull_id = b.id)
			 ORDER BY b.name, b.num, b.id'
		)->fetchAll(),
	];
}

$registered_fnc[] = 'salesFilters';

?>
