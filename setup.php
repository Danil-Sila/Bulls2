<?php

define('APP_ROOT', __DIR__ . '/');
date_default_timezone_set('Europe/Moscow');

function s(string $key) {
	static $config = null;
	$config ??= require APP_ROOT . 'config.php';
	return $config[$key];
}

function db(): PDO {
	static $pdo = null;
	$pdo ??= new PDO(
		'mysql:host=' . s('host') . ';port=' . s('port') . ';dbname=' . s('db') . ';charset=utf8mb4',
		s('user'),
		s('pass'),
		[
			PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
			PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
			PDO::ATTR_EMULATE_PREPARES   => false
		]
	);
	return $pdo;
}

function log_error(Throwable $e): void {
	$line = date('Y-m-d H:i:s') . ' ' . get_class($e) . ': ' . $e->getMessage() . ' in ' . $e->getFile() . ':' . $e->getLine() . PHP_EOL;
	file_put_contents(APP_ROOT . 'logs/api.log', $line, FILE_APPEND | LOCK_EX);
}

function fail(string $message): array {
	return ['success' => false, 'error' => $message];
}

function parse_date($value): ?string {
	if (!is_string($value)) {
		return null;
	}
	$date = DateTime::createFromFormat('!Y-m-d', $value);
	return $date && $date->format('Y-m-d') === $value ? $value : null;
}

function optional_id($value): ?int {
	if ($value === null || $value === '') {
		return null;
	}
	$id = filter_var($value, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
	return $id === false ? null : $id;
}

?>
