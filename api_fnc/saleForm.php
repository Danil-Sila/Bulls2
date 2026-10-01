<?php

function saleForm(array $post): array {
	return [
		'success'     => true,
		'buyers'      => db()->query('SELECT id, name, is_active FROM buyers ORDER BY name, id')->fetchAll(),
		'bulls'       => db()->query('SELECT id, num, name, is_active FROM bulls ORDER BY name, num, id')->fetchAll(),
		'contractors' => db()->query('SELECT id, name, is_active FROM contractors ORDER BY name, id')->fetchAll(),
		'packagings'  => db()->query('SELECT id, name FROM packagings ORDER BY id')->fetchAll(),
	];
}

$registered_fnc[] = 'saleForm';

?>
