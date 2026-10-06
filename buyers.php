<?php
require __DIR__ . '/setup.php';

$title = 'Покупатели';
$page = 'buyers.php';
$scripts = ['picker.js', 'counterpartyForm.js', 'dictionaries.js', 'buyers.js'];
require APP_ROOT . 'views/header.php';
?>

<h1 class="h3 mb-3">Покупатели</h1>
<div id="dictionaries"></div>

<?php require APP_ROOT . 'views/counterparty_form.php'; ?>

<?php require APP_ROOT . 'views/footer.php'; ?>
