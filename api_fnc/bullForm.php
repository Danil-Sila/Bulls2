<?php

function bullForm(array $post): array {
	return [
		'success'     => true,
		'breeds'      => db()->query('SELECT id, name, is_active FROM breeds ORDER BY name')->fetchAll(),
		'contractors' => db()->query('SELECT id, name, is_active FROM contractors ORDER BY name')->fetchAll(),
		'categories'  => db()->query('SELECT id, code FROM categories ORDER BY code')->fetchAll(),
	];
}

$registered_fnc[] = 'bullForm';

?>
