<?php
// Переносит используемые данные из старой базы в новую одной транзакцией.
// Можно запускать повторно: новые таблицы очищаются и заполняются заново.
// Запуск: php bin/import_legacy.php

require __DIR__ . '/../setup.php';

if (PHP_SAPI !== 'cli') {
	exit('Только из командной строки');
}

$old = '`' . s('legacy_db') . '`';

$steps = [
	'packagings' => "INSERT INTO packagings (id, name, short_name)
		SELECT semen_pack_id, TRIM(semen_pack_name), TRIM(semen_pack_sname) FROM $old.semen_pack",

	// Склад «Без учёта» (номер 0) не переносится: по нему нет операций, а операция на нём остановит импорт на внешнем ключе.
	'storages' => "INSERT INTO storages (id, name)
		SELECT storage_id, TRIM(name) FROM $old.storage_list WHERE storage_id <> 0",

	'categories' => "INSERT INTO categories (id, code)
		SELECT cat_id, TRIM(IM) FROM $old.kodkb",

	'breeds' => "INSERT INTO breeds (id, name)
		SELECT breed_id, TRIM(breed_name) FROM $old.breeds",

	'contractors' => "INSERT INTO contractors (id, name, location)
		SELECT c.contr_id, TRIM(c.name), NULLIF(CONCAT_WS(', ', l1.Name, l2.Name, l3.Name), '')
		FROM $old.contr c
		LEFT JOIN $old.llocations l1 ON l1.ID = c.loc_id
		LEFT JOIN $old.llocations l2 ON l2.ID = l1.IDT2
		LEFT JOIN $old.llocations l3 ON l3.ID = l1.IDT1",

	'buyers' => "INSERT INTO buyers (id, name, location)
		SELECT b.buyer_id, TRIM(b.name), NULLIF(CONCAT_WS(', ', l1.Name, l2.Name, l3.Name), '')
		FROM $old.buyers b
		LEFT JOIN $old.llocations l1 ON l1.ID = b.loc_id
		LEFT JOIN $old.llocations l2 ON l2.ID = l1.IDParent
		LEFT JOIN $old.llocations l3 ON l3.ID = l2.IDParent",

	'bulls' => "INSERT INTO bulls (id, num, name, breed_id, vendor_id, supplier_id, category_id, category_year)
		SELECT bull_id, TRIM(num), TRIM(name), breed_id, vendor_id, supplier_id, cur_cat_id, NULLIF(cat_year, 0)
		FROM $old.bulls_list",

	// Дата ввода поступлений раньше не хранилась, поэтому created_at = дата поступления.
	'receipts' => "INSERT INTO receipts (id, received_on, storage_id, bull_id, packaging_id, doses, created_at, updated_at)
		SELECT n_rec, DATE(date_finish), storage_id, bull_id, semen_pack_id, dozes, date_finish, date_finish
		FROM $old.storage_journal",

	'sales' => "INSERT INTO sales (id, sold_on, buyer_id, contractor_id, bull_id, storage_id, packaging_id, doses, created_at, updated_at)
		SELECT n_rec, DATE(date_finish), buyer_id, contr_id, bull_id, storage_id, semen_pack_id, dozes, date_add, date_add
		FROM $old.journal1",
];

$pdo = db();
$pdo->beginTransaction();
try {
	// DELETE, а не TRUNCATE: TRUNCATE завершает транзакцию, и откат при ошибке стал бы невозможен.
	foreach (array_reverse(array_keys($steps)) as $table) {
		$pdo->exec("DELETE FROM $table");
	}
	foreach ($steps as $table => $sql) {
		$rows = $pdo->exec($sql);
		echo str_pad($table, 12) . str_pad((string) $rows, 7, ' ', STR_PAD_LEFT) . " строк\n";
	}
	$pdo->commit();
	echo "Перенос завершён. Проверьте результат: php bin/check_legacy.php\n";
} catch (Throwable $e) {
	$pdo->rollBack();
	fwrite(STDERR, "Перенос отменён, новая база не изменилась.\nОшибка: " . $e->getMessage() . "\n");
	exit(1);
}

?>
