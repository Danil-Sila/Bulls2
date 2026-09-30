<?php

function contractorSave(array $post): array {
	$id = optional_id($post['id'] ?? null);
	$name = str_field($post, 'name');
	$location = str_field($post, 'location');
	$isActive = filter_var($post['is_active'] ?? true, FILTER_VALIDATE_BOOLEAN) ? 1 : 0;

	$errors = [];
	if ($name === '') {
		$errors['name'] = 'Укажите название';
	} elseif (mb_strlen($name) > 150) {
		$errors['name'] = 'Не длиннее 150 символов';
	}
	if (mb_strlen($location) > 255) {
		$errors['location'] = 'Не длиннее 255 символов';
	}
	if ($errors) {
		return fail('Проверьте поля', $errors);
	}

	$params = ['name' => $name, 'location' => $location === '' ? null : $location, 'is_active' => $isActive];

	return transaction(function () use ($id, $params) {
		$same = db()->prepare('SELECT COUNT(*) FROM contractors WHERE name = :name AND id <> :id');
		$same->execute(['name' => $params['name'], 'id' => $id ?? 0]);
		if ($same->fetchColumn() > 0) {
			return fail('Проверьте поля', ['name' => 'Такой контрагент уже есть']);
		}

		if ($id === null) {
			db()->prepare('INSERT INTO contractors (name, location, is_active) VALUES (:name, :location, :is_active)')
				->execute($params);
			return ['success' => true, 'id' => (int) db()->lastInsertId()];
		}

		$found = db()->prepare('SELECT COUNT(*) FROM contractors WHERE id = :id');
		$found->execute(['id' => $id]);
		if ($found->fetchColumn() === 0) {
			return fail('Контрагент не найден. Обновите страницу');
		}
		db()->prepare('UPDATE contractors SET name = :name, location = :location, is_active = :is_active WHERE id = :id')
			->execute($params + ['id' => $id]);
		return ['success' => true, 'id' => $id];
	});
}

$registered_fnc[] = 'contractorSave';

?>
