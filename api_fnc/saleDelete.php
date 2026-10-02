<?php

function saleDelete(array $post): array {
	$id = optional_id($post['id'] ?? null);
	if ($id === null) {
		return fail('Не указана продажа');
	}

	return transaction(function () use ($id) {
		// Одним запросом и проверка, и удаление: между ними никто не успеет вклиниться
		$deleted = db()->prepare('DELETE FROM sales WHERE id = :id');
		$deleted->execute(['id' => $id]);
		if ($deleted->rowCount() === 0) {
			return fail('Продажа не найдена или уже удалена. Обновите страницу');
		}
		return ['success' => true, 'id' => $id];
	});
}

$registered_fnc[] = 'saleDelete';

?>
