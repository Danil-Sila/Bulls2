<?php

function reportBullMonths(array $post): array {
	$period = parse_period($post);
	if (is_string($period)) {
		return fail($period);
	}
	[$from, $to] = $period;
	$buyerId = optional_id($post['buyer_id'] ?? null);
	if ($buyerId === null) {
		return fail('Выберите покупателя');
	}

	$stmt = db()->prepare(
		"SELECT bl.id AS bull_id, bl.name AS bull, bl.num, br.name AS breed,
			YEAR(s.sold_on) AS sale_year, MONTH(s.sold_on) AS sale_month,
			CAST(SUM(s.doses) AS SIGNED) AS doses
		 FROM sales s
		 JOIN bulls bl ON bl.id = s.bull_id
		 JOIN breeds br ON br.id = bl.breed_id
		 WHERE s.buyer_id = :buyer_id AND s.sold_on BETWEEN :date_from AND :date_to
		 GROUP BY bl.id, YEAR(s.sold_on), MONTH(s.sold_on)
		 ORDER BY bl.name, bl.num, bl.id"
	);
	$stmt->execute(['buyer_id' => $buyerId, 'date_from' => $from, 'date_to' => $to]);

	// Из строк «бык, месяц, доз» собираем матрицу: колонка на каждый месяц, где у покупателя были продажи
	$months = [];
	$bulls = [];
	foreach ($stmt->fetchAll() as $r) {
		$key = sprintf('m_%04d_%02d', $r['sale_year'], $r['sale_month']);
		$months[$key] = sprintf('%02d.%04d', $r['sale_month'], $r['sale_year']);
		$bulls[$r['bull_id']] ??= ['bull' => $r['bull'], 'num' => $r['num'], 'breed' => $r['breed'], 'doses' => 0];
		$bulls[$r['bull_id']][$key] = $r['doses'];
		$bulls[$r['bull_id']]['doses'] += $r['doses'];
	}
	ksort($months);

	// У каждой строки должны быть все колонки: пустая ячейка — null, а не отсутствующий ключ
	$rows = [];
	foreach ($bulls as $row) {
		foreach ($months as $key => $title) {
			$row[$key] ??= null;
		}
		$rows[] = $row;
	}

	$columns = [
		['key' => 'bull',  'title' => 'Кличка', 'type' => 'text'],
		['key' => 'num',   'title' => 'Номер',  'type' => 'text'],
		['key' => 'breed', 'title' => 'Порода', 'type' => 'text'],
	];
	$footer = ['bull' => 'Итого', 'num' => null, 'breed' => null, 'doses' => array_sum(array_column($rows, 'doses'))];
	foreach ($months as $key => $title) {
		$columns[] = ['key' => $key, 'title' => $title, 'type' => 'number'];
		$footer[$key] = array_sum(array_column($rows, $key));
	}
	$columns[] = ['key' => 'doses', 'title' => 'Итого', 'type' => 'number'];

	return [
		'success' => true,
		'columns' => $columns,
		'rows'    => $rows,
		'footer'  => $rows ? $footer : null,
		'totals'  => report_totals($rows),
	];
}

$registered_fnc[] = 'reportBullMonths';

?>
