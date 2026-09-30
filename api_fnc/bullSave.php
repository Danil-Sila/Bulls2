<?php

function bullSave(array $post): array {
	$id = optional_id($post['id'] ?? null);
	$num = str_field($post, 'num');
	$name = str_field($post, 'name');
	$breedId = optional_id($post['breed_id'] ?? null);
	$vendorId = optional_id($post['vendor_id'] ?? null);
	$supplierId = optional_id($post['supplier_id'] ?? null);
	$categoryId = optional_id($post['category_id'] ?? null);
	$yearText = str_field($post, 'category_year');

	// Новый бык всегда активный: в архив его переводят только при правке
	$isActive = ($id === null || filter_var($post['is_active'] ?? true, FILTER_VALIDATE_BOOLEAN)) ? 1 : 0;

	$errors = [];
	if ($num === '') {
		$errors['num'] = 'Укажите номер';
	} elseif (mb_strlen($num) > 50) {
		$errors['num'] = 'Не длиннее 50 символов';
	}
	if ($name === '') {
		$errors['name'] = 'Укажите кличку';
	} elseif (mb_strlen($name) > 100) {
		$errors['name'] = 'Не длиннее 100 символов';
	}
	if ($breedId === null) {
		$errors['breed_id'] = 'Выберите породу';
	}
	if ($supplierId === null) {
		$errors['supplier_id'] = 'Выберите поставщика';
	}
	$year = null;
	if ($yearText !== '') {
		$year = filter_var($yearText, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1980, 'max_range' => (int) date('Y')]]);
		if ($year === false) {
			$errors['category_year'] = 'Год от 1980 до ' . date('Y') . ' или пусто';
		}
	}
	if ($errors) {
		return fail('Проверьте поля', $errors);
	}

	$params = [
		'num'           => $num,
		'name'          => $name,
		'breed_id'      => $breedId,
		'vendor_id'     => $vendorId,
		'supplier_id'   => $supplierId,
		'category_id'   => $categoryId,
		'category_year' => $year,
	];

	return transaction(function () use ($id, $isActive, $params) {
		// Дубль ищем только среди активных: иначе старую пару дублей нельзя было бы ни поправить, ни убрать в архив
		if ($isActive) {
			$same = db()->prepare('SELECT COUNT(*) FROM bulls WHERE num = :num AND name = :name AND is_active = 1 AND id <> :id');
			$same->execute(['num' => $params['num'], 'name' => $params['name'], 'id' => $id ?? 0]);
			if ($same->fetchColumn() > 0) {
				return fail('Проверьте поля', ['name' => 'Активный бык с таким номером и кличкой уже есть']);
			}
		}

		if ($id === null) {
			db()->prepare(
				'INSERT INTO bulls (num, name, breed_id, vendor_id, supplier_id, category_id, category_year)
				 VALUES (:num, :name, :breed_id, :vendor_id, :supplier_id, :category_id, :category_year)'
			)->execute($params);
			return ['success' => true, 'id' => (int) db()->lastInsertId()];
		}

		$found = db()->prepare('SELECT COUNT(*) FROM bulls WHERE id = :id');
		$found->execute(['id' => $id]);
		if ($found->fetchColumn() === 0) {
			return fail('Бык не найден. Обновите страницу');
		}
		db()->prepare(
			'UPDATE bulls SET num = :num, name = :name, breed_id = :breed_id, vendor_id = :vendor_id,
				supplier_id = :supplier_id, category_id = :category_id, category_year = :category_year, is_active = :is_active
			 WHERE id = :id'
		)->execute($params + ['is_active' => $isActive, 'id' => $id]);
		return ['success' => true, 'id' => $id];
	});
}

$registered_fnc[] = 'bullSave';

?>
