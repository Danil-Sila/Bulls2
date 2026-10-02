<?php

function receiptDelete(array $post): array {
	$id = optional_id($post['id'] ?? null);
	if ($id === null) {
		return fail('Не указано поступление');
	}

	return transaction(function () use ($id) {
		// Одним запросом и проверка, и перенос в корзину: между ними никто не успеет вклиниться
		$moved = db()->prepare('UPDATE receipts SET deleted_at = NOW() WHERE id = :id AND deleted_at IS NULL');
		$moved->execute(['id' => $id]);
		if ($moved->rowCount() === 0) {
			return fail('Поступление не найдено или уже удалено. Обновите страницу');
		}
		return ['success' => true, 'id' => $id];
	});
}

$registered_fnc[] = 'receiptDelete';

?>
