<?php

const BULL_SEARCH_LIMIT = 8;

function bullSearch(array $post): array {
	$search = trim((string) ($post['search'] ?? ''));
	if (mb_strlen($search) < 2) {
		return ['success' => true, 'rows' => [], 'total' => 0];
	}

	// Экранируем % и _, иначе они сработают в LIKE как шаблоны, а не как символы
	$like = '%' . addcslashes($search, '%_\\') . '%';
	$params = ['search_num' => $like, 'search_name' => $like];

	$from = 'bulls b
		LEFT JOIN (SELECT bull_id, CAST(SUM(doses) AS SIGNED) AS doses FROM stock_balance GROUP BY bull_id) st ON st.bull_id = b.id';
	$where = 'b.is_active = 1 AND (b.num LIKE :search_num OR b.name LIKE :search_name)';

	$count = db()->prepare("SELECT COUNT(*) FROM $from WHERE $where");
	$count->execute($params);

	$list = db()->prepare(
		"SELECT b.id, b.num, b.name, br.name AS breed, st.doses AS stock
		 FROM $from
		 JOIN breeds br ON br.id = b.breed_id
		 WHERE $where
		 ORDER BY st.bull_id IS NULL, b.name, b.num, b.id
		 LIMIT :limit"
	);
	foreach ($params as $name => $value) {
		$list->bindValue($name, $value);
	}
	$list->bindValue('limit', BULL_SEARCH_LIMIT, PDO::PARAM_INT);
	$list->execute();

	return ['success' => true, 'rows' => $list->fetchAll(), 'total' => (int) $count->fetchColumn()];
}

$registered_fnc[] = 'bullSearch';

?>
