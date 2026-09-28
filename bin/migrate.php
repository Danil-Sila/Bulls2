<?php

require __DIR__ . '/../setup.php';

if (PHP_SAPI !== 'cli') {
	exit('Только из командной строки');
}

$server = new PDO(
	'mysql:host=' . s('host') . ';port=' . s('port') . ';charset=utf8mb4',
	s('user'),
	s('pass'),
	[PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
);
$server->exec('CREATE DATABASE IF NOT EXISTS `' . s('db') . '` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci');

db()->exec('CREATE TABLE IF NOT EXISTS schema_migrations (
	filename   VARCHAR(190) NOT NULL PRIMARY KEY,
	applied_at DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci');

$applied = db()->query('SELECT filename FROM schema_migrations')->fetchAll(PDO::FETCH_COLUMN);
$files = glob(APP_ROOT . 'db/*.sql');
sort($files);

$count = 0;
foreach ($files as $file) {
	$name = basename($file);
	if (in_array($name, $applied, true)) {
		continue;
	}
	echo "Применяю $name ... ";
	// DDL в MariaDB не откатывается транзакцией: при ошибке исправьте файл и удалите созданные им таблицы вручную.
	foreach (preg_split('/;\s*$/m', file_get_contents($file)) as $statement) {
		if (trim($statement) !== '') {
			db()->exec($statement);
		}
	}
	db()->prepare('INSERT INTO schema_migrations (filename) VALUES (?)')->execute([$name]);
	echo "готово\n";
	$count++;
}

echo $count ? "Применено миграций: $count\n" : "Новых миграций нет\n";

?>
