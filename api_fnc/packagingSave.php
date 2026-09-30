<?php

function packagingSave(array $post): array {
	$id = optional_id($post['id'] ?? null);
	$name = str_field($post, 'name');
	$shortName = str_field($post, 'short_name');

	$errors = [];
	if ($name === '') {
		$errors['name'] = 'Укажите название';
	} else if (mb_strlen($name) > 100) {
		$errors['name'] = 'Не длиннее 100 символов';
	}
	if ($shortName === '') {
		$errors['short_name'] = 'Укажите сокращение';
	} else if (mb_strlen($shortName) > 10) {
		$errors['short_name'] = 'Не длиннее 10 символов';
	}
	if ($errors) {
		return fail('Проверьте поля', $errors);
	}

	return transaction(function () use ($id, $name, $shortName) {
		$same = db()->prepare('SELECT COUNT(*) FROM packagings WHERE name = :name AND id <> :id');
		$same->execute(['name' => $name, 'id' => $id ?? 0]);
		if ($same->fetchColumn() > 0) {
			return fail('Проверьте поля', ['name' => 'Такая упаковка уже есть']);
		}
		if ($id === null) {
			db()->prepare('INSERT INTO packagings (name, short_name) VALUES (:name, :short_name)')
			->execute(['name' => $name, 'short_name' => $shortName]);
			return ['success' => true, 'id' => (int) db()->lastInsertId()];
		}

		$found = db()->prepare('SELECT COUNT(*) FROM packagings WHERE id = :id');
		$found->execute(['id' => $id]);
		if ($found->fetchColumn() === 0) {
			return fail('Упаковка не найдена. Обновите страницу.');
		}
		db()->prepare('UPDATE packagings SET name = :name, short_name = :short_name WHERE id = :id')
		->execute(['name' => $name, 'short_name' => $shortName, 'id' => $id]);
		return ['success' => true, 'id' => $id];
	});
}

$registered_fnc[] = 'packagingSave';

?>
