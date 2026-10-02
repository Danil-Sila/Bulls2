<?php

function receiptSave(array $post): array {
	$id = optional_id($post['id'] ?? null);
	$receivedOn = parse_date($post['received_on'] ?? null);
	$bullId = optional_id($post['bull_id'] ?? null);
	$packagingId = optional_id($post['packaging_id'] ?? null);
	$doses = filter_var(str_field($post, 'doses'), FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);

	$errors = [];
	if ($receivedOn === null) {
		$errors['received_on'] = 'Укажите дату';
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
		'received_on'  => $receivedOn,
		'bull_id'      => $bullId,
		'packaging_id' => $packagingId,
		'doses'        => $doses,
	];

	return transaction(function () use ($id, $params) {
		if ($id === null) {
			db()->prepare(
				'INSERT INTO receipts (received_on, bull_id, storage_id, packaging_id, doses)
				 VALUES (:received_on, :bull_id, :storage_id, :packaging_id, :doses)'
			)->execute($params + ['storage_id' => default_storage_id()]);
			return ['success' => true, 'id' => (int) db()->lastInsertId()];
		}

		// Пока форма была открыта, поступление могли удалить в другой вкладке
		$found = db()->prepare('SELECT COUNT(*) FROM receipts WHERE id = :id');
		$found->execute(['id' => $id]);
		if ($found->fetchColumn() === 0) {
			return fail('Поступление не найдено. Обновите страницу');
		}
		db()->prepare(
			'UPDATE receipts SET received_on = :received_on, bull_id = :bull_id, packaging_id = :packaging_id, doses = :doses
			 WHERE id = :id'
		)->execute($params + ['id' => $id]);
		return ['success' => true, 'id' => $id];
	});
}

$registered_fnc[] = 'receiptSave';

?>
