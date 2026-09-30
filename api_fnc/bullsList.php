<?php

const BULLS_PER_PAGE = 50;

function bullsList(array $post): array {
	$where = match ($post['archive'] ?? 'active') {
		'archived'	=> 'b.is_active = 0',
		'all'		=> '1 = 1',
		default		=> 'b.is_active = 1',
	};
	$params = [];

	$search = trim((string) ($post['search'] ?? ''));
	if ($search !== '') {
		// Экранируем % и _, иначе они сработают в LIKE как шаблоны, а не как символы
		$like = '%' . addcslashes($search, '%_\\') . '%';
		$where .= ' AND (b.num LIKE :search_num OR b.name LIKE :search_name)';
		$params['search_num'] = $like;
		$params['search_name'] = $like;
	}

	$breedId = optional_id($post['breed_id'] ?? null);
	if ($breedId !== null) {
		$where .= ' AND b.breed_id = :breed_id';
		$params['breed_id'] = $breedId;
	}
	$vendorId = optional_id($post['vendor_id'] ?? null);
	if ($vendorId !== null) {
		$where .= ' AND b.vendor_id = :vendor_id';
		$params['vendor_id'] = $vendorId;
	}
	if (filter_var($post['in_stock'] ?? false, FILTER_VALIDATE_BOOLEAN)) {
		$where .= ' AND st.bull_id IS NOT NULL';
	}

	$from = 'bulls b
		LEFT JOIN (SELECT bull_id, CAST(SUM(doses) AS SIGNED) AS doses FROM stock_balance GROUP BY bull_id) st ON st.bull_id = b.id';

	$count = db()->prepare("SELECT COUNT(*) FROM $from WHERE $where");
	$count->execute($params);
	$total = (int) $count->fetchColumn();

	$pages = max(1, (int) ceil($total / BULLS_PER_PAGE));
	$page = min(max(1, (int) ($post['page'] ?? 1)), $pages);

	$list = db()->prepare(
		"SELECT b.id, b.num, b.name, b.category_year, b.is_active, b.breed_id, b.vendor_id, b.supplier_id, b.category_id,
			br.name AS breed, cat.code AS category, v.name AS vendor, s.name AS supplier, st.doses AS stock
		 FROM $from
		 JOIN breeds br ON br.id = b.breed_id
		 LEFT JOIN categories cat ON cat.id = b.category_id
		 LEFT JOIN contractors v ON v.id = b.vendor_id
		 JOIN contractors s ON s.id = b.supplier_id
		 WHERE $where
		 ORDER BY b.name, b.num, b.id
		 LIMIT :limit OFFSET :offset"
	);
	foreach ($params as $name => $value) {
		$list->bindValue($name, $value);
	}
	$list->bindValue('limit', BULLS_PER_PAGE, PDO::PARAM_INT);
	$list->bindValue('offset', ($page - 1) * BULLS_PER_PAGE, PDO::PARAM_INT);
	$list->execute();

	return [
		'success'    => true,
		'rows'       => $list->fetchAll(),
		'total_rows' => $total,
		'page'       => $page,
		'pages'      => $pages,
	];
}

$registered_fnc[] = 'bullsList';

?>
