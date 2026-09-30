<?php
require __DIR__ . '/setup.php';

$title = 'Контрагенты';
$page = 'contractors.php';
$scripts = ['dictionaries.js', 'contractors.js'];
require APP_ROOT . 'views/header.php';
?>

<h1 class="h3 mb-3">Контрагенты</h1>
<div id="dictionaries"></div>

<?php require APP_ROOT . 'views/footer.php'; ?>
