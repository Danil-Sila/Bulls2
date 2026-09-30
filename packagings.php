<?php
require __DIR__ . '/setup.php';

$title = 'Упаковки';
$page = 'packagings.php';
$scripts = ['dictionaries.js', 'packagings.js'];
require APP_ROOT . 'views/header.php';
?>

<h1 class="h3 mb-3">Упаковки</h1>
<div class="row">
	<div id="dictionaries" class="col-lg-8"></div>
</div>

<?php require APP_ROOT . 'views/footer.php'; ?>
