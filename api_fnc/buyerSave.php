<?php

function buyerSave(array $post): array {
	$id = optional_id($post['id'] ?? null);
	$name = str_field($post, 'name');
	$location = parse_location($post);
	$address = str_field($post, 'address');
	$isActive = filter_var($post['is_active'] ?? true, FILTER_VALIDATE_BOOLEAN) ? 1 : 0;

	$errors = [];
	if ($name === '') {
		$errors['name'] = 'Укажите название';
	} elseif (mb_strlen($name) > 150) {
		$errors['name'] = 'Не длиннее 150 символов';
	}
	if (is_string($location)) {
		$errors['location_id'] = $location;
	}
	if (mb_strlen($address) > 255) {
		$errors['address'] = 'Не длиннее 255 символов';
	}
	if ($errors) {
		return fail('Проверьте поля', $errors);
	}

	$params = ['name' => $name, 'location_id' => $location, 'address' => $address === '' ? null : $address, 'is_active' => $isActive];

	return transaction(function () use ($id, $params) {
		$same = db()->prepare('SELECT COUNT(*) FROM buyers WHERE name = :name AND id <> :id');
		$same->execute(['name' => $params['name'], 'id' => $id ?? 0]);
		if ($same->fetchColumn() > 0) {
			return fail('Проверьте поля', ['name' => 'Такой покупатель уже есть']);
		}

		if ($id === null) {
			db()->prepare('INSERT INTO buyers (name, location_id, address, is_active) VALUES (:name, :location_id, :address, :is_active)')
				->execute($params);
			return ['success' => true, 'id' => (int) db()->lastInsertId()];
		}

		$found = db()->prepare('SELECT COUNT(*) FROM buyers WHERE id = :id');
		$found->execute(['id' => $id]);
		if ($found->fetchColumn() === 0) {
			return fail('Покупатель не найден. Обновите страницу');
		}
		db()->prepare('UPDATE buyers SET name = :name, location_id = :location_id, address = :address, is_active = :is_active WHERE id = :id')
			->execute($params + ['id' => $id]);
		return ['success' => true, 'id' => $id];
	});
}

$registered_fnc[] = 'buyerSave';

?>
