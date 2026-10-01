<?php

function saleDelete(array $post): array {
	$id = optional_id($post['id'] ?? null);
	if ($id === null) {
		return fail('Не указана продажа');
	}

	return transaction(function () use ($id) {
		// Одним запросом и проверка, и перенос в корзину: между ними никто не успеет вклиниться
		$moved = db()->prepare('UPDATE sales SET deleted_at = NOW() WHERE id = :id AND deleted_at IS NULL');
		$moved->execute(['id' => $id]);
		if ($moved->rowCount() === 0) {
			return fail('Продажа не найдена или уже удалена. Обновите страницу');
		}
		return ['success' => true, 'id' => $id];
	});
}

$registered_fnc[] = 'saleDelete';

?>
