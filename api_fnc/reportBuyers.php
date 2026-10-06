<?php

function reportBuyers(array $post): array {
	$period = parse_period($post);
	if (is_string($period)) {
		return fail($period);
	}
	[$from, $to] = $period;

	$stmt = db()->prepare(
		"SELECT b.name AS buyer, ln.name AS location,
			CAST(SUM(s.doses) AS SIGNED) AS doses, COUNT(*) AS purchases
		 FROM sales s
		 JOIN buyers b ON b.id = s.buyer_id
		 LEFT JOIN location_names ln ON ln.id = b.location_id
		 WHERE s.sold_on BETWEEN :date_from AND :date_to
		 GROUP BY b.id, ln.name
		 ORDER BY b.name, b.id"
	);
	$stmt->execute(['date_from' => $from, 'date_to' => $to]);
	$rows = $stmt->fetchAll();

	return [
		'success' => true,
		'columns' => [
			['key' => 'buyer',     'title' => 'Покупатель', 'type' => 'text'],
			['key' => 'location',  'title' => 'Место',      'type' => 'text', 'wrap' => true],
			['key' => 'doses',     'title' => 'Доз',        'type' => 'number'],
			['key' => 'purchases', 'title' => 'Покупок',    'type' => 'number'],
		],
		'rows'    => $rows,
		'totals'  => report_totals($rows),
	];
}

$registered_fnc[] = 'reportBuyers';

?>
