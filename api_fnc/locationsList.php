<?php

function locationsList(array $post): array {
	return [
		'success'   => true,
		'regions'   => db()->query('SELECT id, name FROM locations WHERE level = 2 ORDER BY name, id')->fetchAll(),
		'districts' => db()->query('SELECT id, parent_id, name FROM locations WHERE level = 3 ORDER BY name, id')->fetchAll(),
	];
}

$registered_fnc[] = 'locationsList';

?>
