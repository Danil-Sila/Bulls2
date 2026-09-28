<?php
require __DIR__ . '/../setup.php';

$title = 'Главная';
$page = 'index.php';
$scripts = ['home.js'];

require APP_ROOT . 'views/header.php';
?>

<h1 class="h3 mb-3">Главная</h1>

<div id="error" class="alert alert-danger" role="alert" hidden></div>

<div class="row g-3">
	<div class="col-12 col-md-6 col-lg-4">
		<div class="card card-body">
			<div class="small text-body-secondary">На складе</div>
			<div id="stockDoses" class="fs-3 fw-semibold num">…</div>
			<div id="stockPositions" class="small text-body-secondary"></div>
		</div>
	</div>
	<div id="negative" class="col-12 col-md-6 col-lg-4" hidden>
		<div class="card card-body border-danger">
			<div class="small text-danger">Отрицательный остаток</div>
			<div id="negativeDoses" class="fs-3 fw-semibold num text-danger"></div>
			<div id="negativePositions" class="small text-body-secondary"></div>
		</div>
	</div>
</div>

<?php require APP_ROOT . 'views/footer.php'; ?>
