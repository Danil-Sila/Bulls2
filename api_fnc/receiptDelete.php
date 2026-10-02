<?php

function receiptDelete(array $post): array {
	$id = optional_id($post['id'] ?? null);
	if ($id === null) {
		return fail('Не указано поступление');
	}

	return transaction(function () use ($id) {
		// Одним запросом и проверка, и удаление: между ними никто не успеет вклиниться
		$deleted = db()->prepare('DELETE FROM receipts WHERE id = :id');
		$deleted->execute(['id' => $id]);
		if ($deleted->rowCount() === 0) {
			return fail('Поступление не найдено или уже удалено. Обновите страницу');
		}
		return ['success' => true, 'id' => $id];
	});
}

$registered_fnc[] = 'receiptDelete';

?>
