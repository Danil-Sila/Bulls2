<?php

function reportBulls(array $post): array {
	$period = parse_period($post);
	if (is_string($period)) {
		return fail($period);
	}
	[$from, $to] = $period;

	// LEFT JOIN у производителя и категории: они необязательны, бык без них не должен выпасть из отчёта
	$stmt = db()->prepare(
		"SELECT bl.name AS bull, bl.num, br.name AS breed, v.name AS vendor, sp.name AS supplier,
			bl.category_year, c.code AS category,
			CAST(SUM(s.doses) AS SIGNED) AS doses, COUNT(*) AS purchases
		 FROM sales s
		 JOIN bulls bl ON bl.id = s.bull_id
		 JOIN breeds br ON br.id = bl.breed_id
		 LEFT JOIN contractors v ON v.id = bl.vendor_id
		 JOIN contractors sp ON sp.id = bl.supplier_id
		 LEFT JOIN categories c ON c.id = bl.category_id
		 WHERE s.sold_on BETWEEN :date_from AND :date_to
		 GROUP BY bl.id
		 ORDER BY bl.name, bl.num, bl.id"
	);
	$stmt->execute(['date_from' => $from, 'date_to' => $to]);
	$rows = $stmt->fetchAll();

	return [
		'success' => true,
		'columns' => [
			['key' => 'bull',          'title' => 'Кличка',        'type' => 'text'],
			['key' => 'num',           'title' => 'Номер',         'type' => 'text'],
			['key' => 'breed',         'title' => 'Порода',        'type' => 'text'],
			['key' => 'vendor',        'title' => 'Производитель', 'type' => 'text', 'wrap' => true],
			['key' => 'supplier',      'title' => 'Поставщик',     'type' => 'text', 'wrap' => true],
			['key' => 'category_year', 'title' => 'Год оценки',    'type' => 'text'],
			['key' => 'category',      'title' => 'Категория',     'type' => 'text'],
			['key' => 'doses',         'title' => 'Доз',           'type' => 'number'],
			['key' => 'purchases',     'title' => 'Покупок',       'type' => 'number'],
		],
		'rows'    => $rows,
		'totals'  => report_totals($rows),
	];
}

$registered_fnc[] = 'reportBulls';

?>
