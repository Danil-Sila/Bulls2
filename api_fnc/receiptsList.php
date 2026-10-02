<?php

const RECEIPTS_PER_PAGE = 50;

function receiptsList(array $post): array {
	$from = parse_date($post['date_from'] ?? null);
	$to = parse_date($post['date_to'] ?? null);
	if ($from === null || $to === null) {
		return fail('Укажите период: даты «с» и «по»');
	}
	if ($from > $to) {
		return fail('Дата «с» позже даты «по»');
	}

	$where = 'r.deleted_at IS NULL AND r.received_on BETWEEN :date_from AND :date_to';
	$params = ['date_from' => $from, 'date_to' => $to];

	$bullId = optional_id($post['bull_id'] ?? null);
	if ($bullId !== null) {
		$where .= ' AND r.bull_id = :bull_id';
		$params['bull_id'] = $bullId;
	}

	$totals = db()->prepare("SELECT COUNT(*) AS rows_count, COALESCE(SUM(r.doses), 0) AS doses FROM receipts r WHERE $where");
	$totals->execute($params);
	$total = $totals->fetch();

	$pages = max(1, (int) ceil($total['rows_count'] / RECEIPTS_PER_PAGE));
	$page = min(max(1, (int) ($post['page'] ?? 1)), $pages);

	$list = db()->prepare(
		"SELECT r.id, r.received_on, r.doses, r.bull_id, r.packaging_id,
			bl.num AS bull_num, bl.name AS bull, br.name AS breed, p.short_name AS packaging
		 FROM receipts r
		 JOIN bulls bl ON bl.id = r.bull_id
		 JOIN breeds br ON br.id = bl.breed_id
		 JOIN packagings p ON p.id = r.packaging_id
		 WHERE $where
		 ORDER BY r.received_on DESC, r.id DESC
		 LIMIT :limit OFFSET :offset"
	);
	foreach ($params as $name => $value) {
		$list->bindValue($name, $value);
	}
	$list->bindValue('limit', RECEIPTS_PER_PAGE, PDO::PARAM_INT);
	$list->bindValue('offset', ($page - 1) * RECEIPTS_PER_PAGE, PDO::PARAM_INT);
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

$registered_fnc[] = 'receiptsList';

?>
