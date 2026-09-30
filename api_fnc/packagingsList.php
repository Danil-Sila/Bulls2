<?php

function packagingsList(array $post): array {
	return [
		'success' => true,
		'rows'	  => db()->query('SELECT id, name, short_name FROM packagings ORDER BY id')->fetchAll(),
	];
}

$registered_fnc[] = 'packagingsList';

?>
