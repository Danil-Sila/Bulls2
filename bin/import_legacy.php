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

	// В старых названиях мест часть букв набрана латиницей (H вместо Н, p вместо р) — поиск по «южный» не находил «ЮЖHЫЙ».
	// Других латинских букв и названий целиком на латинице в справочнике нет.
	// Родители вставляются раньше детей (ORDER BY TIER): внешний ключ parent_id проверяется построчно.
	'locations' => "INSERT INTO locations (id, parent_id, level, name)
		SELECT ID, IDParent, TIER, TRIM(REPLACE(REPLACE(Name, 'H', 'Н'), 'p', 'р'))
		FROM $old.llocations
		ORDER BY TIER, ID",

	'contractors' => "INSERT INTO contractors (id, name, location_id)
		SELECT contr_id, TRIM(name), loc_id FROM $old.contr",

	'buyers' => "INSERT INTO buyers (id, name, location_id)
		SELECT buyer_id, TRIM(name), loc_id FROM $old.buyers",

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
		// locations ссылается сама на себя: сначала удаляем нижние уровни, иначе внешний ключ не даст удалить родителя.
		$pdo->exec("DELETE FROM $table" . ($table === 'locations' ? ' ORDER BY level DESC' : ''));
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
