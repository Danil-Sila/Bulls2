<?php

function reportMovement(array $post): array {
	$year = filter_var($post['year'] ?? null, FILTER_VALIDATE_INT, ['options' => ['min_range' => 2000, 'max_range' => 2100]]);
	$month = filter_var($post['month'] ?? null, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1, 'max_range' => 12]]);
	if ($year === false || $month === false) {
		return fail('Укажите год (2000–2100) и месяц');
	}

	$monthStart = sprintf('%04d-%02d-01', $year, $month);
	// Настоящий последний день месяца: 28–31, а не всегда «31», как в старом отчёте
	$monthEnd = date('Y-m-t', strtotime($monthStart));

	// Поступления и продажи сводятся в один список «дата, бык, доз» (продажа — со знаком минус).
	// Три даты передаются один раз через таблицу p: родные параметры PDO нельзя повторять в запросе.
	$stmt = db()->prepare(
		"SELECT bl.name AS bull, bl.num,
			CAST(SUM(IF(m.dt < p.year_start, m.d, 0)) AS SIGNED) AS start_balance,
			CAST(SUM(IF(m.dt >= p.year_start AND m.d > 0, m.d, 0)) AS SIGNED) AS in_ytd,
			CAST(SUM(IF(m.dt >= p.month_start AND m.d > 0, m.d, 0)) AS SIGNED) AS in_month,
			CAST(-SUM(IF(m.dt >= p.year_start AND m.d < 0, m.d, 0)) AS SIGNED) AS out_ytd,
			CAST(-SUM(IF(m.dt >= p.month_start AND m.d < 0, m.d, 0)) AS SIGNED) AS out_month
		 FROM (
			SELECT bull_id, received_on AS dt, CAST(doses AS SIGNED) AS d FROM receipts
			UNION ALL
			SELECT bull_id, sold_on, -CAST(doses AS SIGNED) FROM sales
		 ) m
		 JOIN (SELECT :year_start AS year_start, :month_start AS month_start, :month_end AS month_end) p
		 JOIN bulls bl ON bl.id = m.bull_id
		 WHERE m.dt <= p.month_end
		 GROUP BY bl.id
		 HAVING start_balance <> 0 OR in_ytd <> 0 OR out_ytd <> 0
		 ORDER BY bl.name, bl.num, bl.id"
	);
	$stmt->execute(['year_start' => "$year-01-01", 'month_start' => $monthStart, 'month_end' => $monthEnd]);
	$rows = array_map(
		fn($row) => [...$row, 'end_balance' => $row['start_balance'] + $row['in_ytd'] - $row['out_ytd']],
		$stmt->fetchAll()
	);

	return [
		'success' => true,
		'columns' => [
			['key' => 'bull',          'title' => 'Кличка',                  'type' => 'text'],
			['key' => 'num',           'title' => 'Номер',                   'type' => 'text'],
			['key' => 'start_balance', 'title' => 'Остаток на начало года',  'type' => 'number'],
			['key' => 'in_ytd',        'title' => 'Приход с начала года',    'type' => 'number'],
			['key' => 'in_month',      'title' => 'Приход за месяц',         'type' => 'number'],
			['key' => 'out_ytd',       'title' => 'Расход с начала года',    'type' => 'number'],
			['key' => 'out_month',     'title' => 'Расход за месяц',         'type' => 'number'],
			['key' => 'end_balance',   'title' => 'Остаток на конец месяца', 'type' => 'number'],
		],
		'rows'    => $rows,
		'totals'  => [
			['label' => 'Строк', 'value' => count($rows)],
		],
	];
}

$registered_fnc[] = 'reportMovement';

?>
