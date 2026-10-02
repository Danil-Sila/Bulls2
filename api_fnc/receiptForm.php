<?php

function receiptForm(array $post): array {
	return [
		'success'    => true,
		'bulls'      => db()->query('SELECT id, num, name, is_active FROM bulls ORDER BY name, num, id')->fetchAll(),
		'packagings' => db()->query('SELECT id, name FROM packagings ORDER BY id')->fetchAll(),
	];
}

$registered_fnc[] = 'receiptForm';

?>
