<?php

function reportBullsFarms(array $post): array {
	$period = parse_period($post);
	if (is_string($period)) {
		return fail($period);
	}
	[$from, $to] = $period;

	$stmt = db()->prepare(
		"SELECT b.name AS buyer, bl.name AS bull, bl.num,
			CAST(SUM(s.doses) AS SIGNED) AS doses, COUNT(*) AS purchases
		 FROM sales s
		 JOIN buyers b ON b.id = s.buyer_id
		 JOIN bulls bl ON bl.id = s.bull_id
		 WHERE s.sold_on BETWEEN :date_from AND :date_to
		 GROUP BY b.id, bl.id
		 ORDER BY b.name, b.id, bl.name, bl.num, bl.id"
	);
	$stmt->execute(['date_from' => $from, 'date_to' => $to]);
	$rows = $stmt->fetchAll();

	return [
		'success' => true,
		'columns' => [
			['key' => 'buyer',     'title' => 'Организация', 'type' => 'text'],
			['key' => 'bull',      'title' => 'Кличка',      'type' => 'text'],
			['key' => 'num',       'title' => 'Номер',       'type' => 'text'],
			['key' => 'doses',     'title' => 'Доз',         'type' => 'number'],
			['key' => 'purchases', 'title' => 'Покупок',     'type' => 'number'],
		],
		'rows'    => $rows,
		'totals'  => report_totals($rows),
	];
}

$registered_fnc[] = 'reportBullsFarms';

?>
