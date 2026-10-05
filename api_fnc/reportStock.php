<?php

function reportStock(array $post): array {
	$rows = db()->query(
		"SELECT bl.name AS bull, bl.num, v.name AS vendor, p.short_name AS packaging,
			CAST(sb.doses AS SIGNED) AS doses
		 FROM stock_balance sb
		 JOIN bulls bl ON bl.id = sb.bull_id
		 LEFT JOIN contractors v ON v.id = bl.vendor_id
		 JOIN packagings p ON p.id = sb.packaging_id
		 ORDER BY bl.name, bl.num, bl.id, p.id"
	)->fetchAll();

	// Плюсы и минусы считаются отдельно: продажа сверх остатка (П3) не должна уменьшать чужой запас
	$sums = db()->query(
		"SELECT p.short_name AS packaging,
			CAST(SUM(IF(sb.doses > 0, sb.doses, 0)) AS SIGNED) AS plus,
			CAST(SUM(IF(sb.doses < 0, sb.doses, 0)) AS SIGNED) AS minus
		 FROM stock_balance sb
		 JOIN packagings p ON p.id = sb.packaging_id
		 GROUP BY p.id
		 ORDER BY p.id"
	)->fetchAll();

	$totals = [];
	$kinds = ['plus' => 'Итого доз на складе (без отрицательных остатков)', 'minus' => 'Итого отрицательных остатков'];
	foreach ($kinds as $key => $label) {
		$parts = [];
		foreach ($sums as $sum) {
			if ($sum[$key] !== 0) {
				$parts[] = ['label' => $sum['packaging'], 'value' => $sum[$key]];
			}
		}
		$totals[] = ['label' => $label, 'value' => array_sum(array_column($sums, $key)), 'parts' => $parts];
	}
	$totals[] = ['label' => 'Строк', 'value' => count($rows)];

	return [
		'success' => true,
		'columns' => [
			['key' => 'bull',      'title' => 'Кличка',        'type' => 'text'],
			['key' => 'num',       'title' => 'Номер',         'type' => 'text'],
			['key' => 'vendor',    'title' => 'Производитель', 'type' => 'text'],
			['key' => 'packaging', 'title' => 'Упаковка',      'type' => 'text'],
			['key' => 'doses',     'title' => 'Доз',           'type' => 'number'],
		],
		'rows'    => $rows,
		'totals'  => $totals,
	];
}

$registered_fnc[] = 'reportStock';

?>
