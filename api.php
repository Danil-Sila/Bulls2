<?php

require __DIR__ . '/setup.php';

ini_set('display_errors', '0');

header('Content-Type: application/json; charset=utf-8');

function respond(array $data, int $status = 200): void {
	http_response_code($status);
	echo json_encode($data, JSON_UNESCAPED_UNICODE);
	exit;
}

$registered_fnc = [];

require_once APP_ROOT . 'api_fnc/stockSummary.php';
require_once APP_ROOT . 'api_fnc/packagingsList.php';
require_once APP_ROOT . 'api_fnc/packagingSave.php';
require_once APP_ROOT . 'api_fnc/buyerSave.php';
require_once APP_ROOT . 'api_fnc/buyersList.php';
require_once APP_ROOT . 'api_fnc/contractorsList.php';
require_once APP_ROOT . 'api_fnc/contractorSave.php';
require_once APP_ROOT . 'api_fnc/bullsFilters.php';
require_once APP_ROOT . 'api_fnc/bullsList.php';
require_once APP_ROOT . 'api_fnc/bullForm.php';
require_once APP_ROOT . 'api_fnc/bullSave.php';
require_once APP_ROOT . 'api_fnc/bullSearch.php';

//защита от CSRF
$contentType = $_SERVER['CONTENT_TYPE'] ?? '';
if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !str_starts_with($contentType, 'application/json')) {
	respond(fail('Ожидается POST-запрос с JSON'), 400);
}

$post = json_decode(file_get_contents('php://input'), true);
if(!is_array($post)) {
	respond(fail('Некорректный JSON'), 400);
}

$sender = $post['sender'] ?? '';
if (!in_array($sender, $registered_fnc, true)) {
	respond(fail('Неизвестное действие'), 404);
}

try {
	respond($sender($post));
} catch(Throwable $e) {
	log_error($e);
	respond(fail('Ошибка на сервере. Подробности записаны в logs/api.log'), 500);
}

?>
