<?php

const SALES_PER_PAGE = 50;

function salesList(array $post): array {
	$from = parse_date($post['date_from'] ?? null);
	$to = parse_date($post['date_to'] ?? null);
	if ($from === null || $to === null) {
		return fail('Укажите период: даты «с» и «по»');
	}
	if ($from > $to) {
		return fail('Дата «с» позже даты «по»');
	}

	$where = 's.deleted_at IS NULL AND s.sold_on BETWEEN :date_from AND :date_to';
	$params = ['date_from' => $from, 'date_to' => $to];

	$buyerId = optional_id($post['buyer_id'] ?? null);
	if ($buyerId !== null) {
		$where .= ' AND s.buyer_id = :buyer_id';
		$params['buyer_id'] = $buyerId;
	}
	$bullId = optional_id($post['bull_id'] ?? null);
	if ($bullId !== null) {
		$where .= ' AND s.bull_id = :bull_id';
		$params['bull_id'] = $bullId;
	}

	$totals = db()->prepare("SELECT COUNT(*) AS rows_count, COALESCE(SUM(s.doses), 0) AS doses FROM sales s WHERE $where");
	$totals->execute($params);
	$total = $totals->fetch();

	$pages = max(1, (int) ceil($total['rows_count'] / SALES_PER_PAGE));
	$page = min(max(1, (int) ($post['page'] ?? 1)), $pages);

	// INNER JOIN везде: во внешних ключах NOT NULL, строка справочника есть всегда
	$list = db()->prepare(
		"SELECT s.id, s.sold_on, s.doses, s.buyer_id, s.contractor_id, s.bull_id, s.packaging_id,
			b.name AS buyer, c.name AS contractor, bl.num AS bull_num, bl.name AS bull,
			br.name AS breed, p.short_name AS packaging
		 FROM sales s
		 JOIN buyers b ON b.id = s.buyer_id
		 JOIN contractors c ON c.id = s.contractor_id
		 JOIN bulls bl ON bl.id = s.bull_id
		 JOIN breeds br ON br.id = bl.breed_id
		 JOIN packagings p ON p.id = s.packaging_id
		 WHERE $where
		 ORDER BY s.sold_on DESC, s.id DESC
		 LIMIT :limit OFFSET :offset"
	);
	foreach ($params as $name => $value) {
		$list->bindValue($name, $value);
	}
	$list->bindValue('limit', SALES_PER_PAGE, PDO::PARAM_INT);
	$list->bindValue('offset', ($page - 1) * SALES_PER_PAGE, PDO::PARAM_INT);
	$list->execute();

	return [
		'success'     => true,
		'rows'        => $list->fetchAll(),
		'total_rows'  => (int) $total['rows_count'],
		'total_doses' => (int) $total['doses'],
		'page'        => $page,
		'pages'       => $pages,
	];
}

$registered_fnc[] = 'salesList';

?>
