<?php

function saleSave(array $post): array {
	$id = optional_id($post['id'] ?? null);
	$soldOn = parse_date($post['sold_on'] ?? null);
	$buyerId = optional_id($post['buyer_id'] ?? null);
	$contractorId = optional_id($post['contractor_id'] ?? null);
	$bullId = optional_id($post['bull_id'] ?? null);
	$packagingId = optional_id($post['packaging_id'] ?? null);
	$doses = filter_var(str_field($post, 'doses'), FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);

	$errors = [];
	if ($soldOn === null) {
		$errors['sold_on'] = 'Укажите дату';
	}
	if ($buyerId === null) {
		$errors['buyer_id'] = 'Выберите покупателя';
	}
	if ($contractorId === null) {
		$errors['contractor_id'] = 'Выберите поставщика';
	}
	if ($bullId === null) {
		$errors['bull_id'] = 'Выберите быка';
	}
	if ($packagingId === null) {
		$errors['packaging_id'] = 'Выберите упаковку';
	}
	if ($doses === false) {
		$errors['doses'] = 'Введите целое число доз';
	}
	if ($errors) {
		return fail('Проверьте поля', $errors);
	}

	$params = [
		'sold_on'       => $soldOn,
		'buyer_id'      => $buyerId,
		'contractor_id' => $contractorId,
		'bull_id'       => $bullId,
		'packaging_id'  => $packagingId,
		'doses'         => $doses,
	];

	return transaction(function () use ($id, $params) {
		if ($id === null) {
			db()->prepare(
				'INSERT INTO sales (sold_on, buyer_id, contractor_id, bull_id, storage_id, packaging_id, doses)
				 VALUES (:sold_on, :buyer_id, :contractor_id, :bull_id, :storage_id, :packaging_id, :doses)'
			)->execute($params + ['storage_id' => default_storage_id()]);
			return ['success' => true, 'id' => (int) db()->lastInsertId()];
		}

		// Продажу из корзины править нельзя: сначала её восстанавливают
		$found = db()->prepare('SELECT COUNT(*) FROM sales WHERE id = :id AND deleted_at IS NULL');
		$found->execute(['id' => $id]);
		if ($found->fetchColumn() === 0) {
			return fail('Продажа не найдена. Обновите страницу');
		}
		db()->prepare(
			'UPDATE sales SET sold_on = :sold_on, buyer_id = :buyer_id, contractor_id = :contractor_id,
				bull_id = :bull_id, packaging_id = :packaging_id, doses = :doses
			 WHERE id = :id'
		)->execute($params + ['id' => $id]);
		return ['success' => true, 'id' => $id];
	});
}

$registered_fnc[] = 'saleSave';

?>
