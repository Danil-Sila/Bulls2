<?php

function bullsFilters(array $post): array {
	return [
		'success' => true,
		'breeds'  => db()->query('SELECT id, name FROM breeds ORDER BY name')->fetchAll(),
		'vendors' => db()->query(
			'SELECT c.id, c.name
			 FROM contractors c
			 WHERE EXISTS (SELECT 1 FROM bulls b WHERE b.vendor_id = c.id)
			 ORDER BY c.name'
		)->fetchAll(),
	];
}

$registered_fnc[] = 'bullsFilters';

?>
