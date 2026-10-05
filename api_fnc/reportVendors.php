<?php

function reportVendors(array $post): array {
	$period = parse_period($post);
	if (is_string($period)) {
		return fail($period);
	}
	[$from, $to] = $period;

	// Производитель необязателен: быки без него идут одной строкой «—», в конце списка
	$stmt = db()->prepare(
		"SELECT COALESCE(v.name, '—') AS vendor,
			GROUP_CONCAT(DISTINCT sp.name ORDER BY sp.name SEPARATOR ', ') AS suppliers,
			CAST(SUM(s.doses) AS SIGNED) AS doses, COUNT(*) AS purchases
		 FROM sales s
		 JOIN bulls bl ON bl.id = s.bull_id
		 LEFT JOIN contractors v ON v.id = bl.vendor_id
		 JOIN contractors sp ON sp.id = bl.supplier_id
		 WHERE s.sold_on BETWEEN :date_from AND :date_to
		 GROUP BY bl.vendor_id
		 ORDER BY bl.vendor_id IS NULL, v.name"
	);
	$stmt->execute(['date_from' => $from, 'date_to' => $to]);
	$rows = $stmt->fetchAll();

	return [
		'success' => true,
		'columns' => [
			['key' => 'vendor',    'title' => 'Производитель', 'type' => 'text'],
			['key' => 'suppliers', 'title' => 'Поставщики',    'type' => 'text'],
			['key' => 'doses',     'title' => 'Доз',           'type' => 'number'],
			['key' => 'purchases', 'title' => 'Покупок',       'type' => 'number'],
		],
		'rows'    => $rows,
		'totals'  => report_totals($rows),
	];
}

$registered_fnc[] = 'reportVendors';

?>
