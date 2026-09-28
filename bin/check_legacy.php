<?php
// Сверяет старую и новую базу: число записей, дозы по годам, остатки. Запуск: php bin/check_legacy.php
// Все строки должны быть OK; код выхода 1, если есть расхождения.

require __DIR__ . '/../setup.php';

if (PHP_SAPI !== 'cli') {
	exit('Только из командной строки');
}

$old = '`' . s('legacy_db') . '`';

function value(string $sql) {
	return db()->query($sql)->fetchColumn();
}

$checks = [];
$tables = [
	'Быков'        => ['bulls_list', 'bulls'],
	'Покупателей'  => ['buyers', 'buyers'],
	'Контрагентов' => ['contr', 'contractors'],
	'Пород'        => ['breeds', 'breeds'],
	'Категорий'    => ['kodkb', 'categories'],
	'Упаковок'     => ['semen_pack', 'packagings'],
	'Поступлений'  => ['storage_journal', 'receipts'],
	'Продаж'       => ['journal1', 'sales'],
];
foreach ($tables as $label => [$from, $to]) {
	$checks[$label] = [value("SELECT COUNT(*) FROM $old.$from"), value("SELECT COUNT(*) FROM $to")];
}
$checks['Складов'] = [
	value("SELECT COUNT(*) FROM $old.storage_list WHERE storage_id <> 0"),
	value('SELECT COUNT(*) FROM storages'),
];

$years = db()->query("SELECT DISTINCT YEAR(date_finish) FROM $old.journal1 ORDER BY 1")->fetchAll(PDO::FETCH_COLUMN);
foreach ($years as $year) {
	$checks["Продано доз в $year"] = [
		value("SELECT SUM(dozes) FROM $old.journal1 WHERE YEAR(date_finish) = $year"),
		value("SELECT SUM(doses) FROM sales WHERE YEAR(sold_on) = $year"),
	];
}
$years = db()->query("SELECT DISTINCT YEAR(date_finish) FROM $old.storage_journal ORDER BY 1")->fetchAll(PDO::FETCH_COLUMN);
foreach ($years as $year) {
	$checks["Поступило доз в $year"] = [
		value("SELECT SUM(dozes) FROM $old.storage_journal WHERE YEAR(date_finish) = $year"),
		value("SELECT SUM(doses) FROM receipts WHERE YEAR(received_on) = $year"),
	];
}

// storage_cnt — остаток, который вело старое приложение; новый считается представлением stock_balance.
$checks['Позиций остатка'] = [
	value("SELECT COUNT(*) FROM $old.storage_cnt WHERE dozes <> 0"),
	value('SELECT COUNT(*) FROM stock_balance'),
];
$checks['Остаток, доз'] = [
	value("SELECT SUM(dozes) FROM $old.storage_cnt WHERE dozes > 0"),
	value('SELECT SUM(doses) FROM stock_balance WHERE doses > 0'),
];
$checks['Отрицательный остаток, доз'] = [
	value("SELECT COALESCE(SUM(dozes), 0) FROM $old.storage_cnt WHERE dozes < 0"),
	value('SELECT COALESCE(SUM(doses), 0) FROM stock_balance WHERE doses < 0'),
];

function row(string $label, string $before, string $after, string $result): string {
	return mb_str_pad($label, 28) . mb_str_pad($before, 12, ' ', STR_PAD_LEFT) . mb_str_pad($after, 12, ' ', STR_PAD_LEFT) . '   ' . $result . "\n";
}

$failed = 0;
echo row('Показатель', 'Старая БД', 'Новая БД', 'Итог');
foreach ($checks as $label => [$before, $after]) {
	$ok = (string) $before === (string) $after;
	$failed += $ok ? 0 : 1;
	echo row($label, number_format((float) $before, 0, '', ' '), number_format((float) $after, 0, '', ' '), $ok ? 'OK' : 'РАСХОЖДЕНИЕ');
}

echo $failed ? "\nРасхождений: $failed\n" : "\nВсе показатели совпадают (" . count($checks) . ")\n";
exit($failed ? 1 : 0);

?>
