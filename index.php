<?php
require __DIR__ . '/setup.php';

$title = 'Главная';
$page = 'index.php';
$scripts = ['home.js'];

require APP_ROOT . 'views/header.php';
?>

<h1 class="h3 mb-3">Главная</h1>

<form action="bulls.php" method="get" role="search" class="position-relative mb-4">
	<input type="search" id="bullSearch" name="search" class="form-control form-control-lg" placeholder="Номер или кличка быка" autocomplete="off" aria-label="Поиск быка">
	<div id="bullSuggest" class="list-group position-absolute w-100 shadow mt-1 z-3" hidden></div>
</form>

<div class="d-grid d-sm-flex gap-2 mb-4">
	<a href="sales.php?add=1" class="btn btn-success btn-lg">+ Продажа</a>
	<a href="receipts.php?add=1" class="btn btn-success btn-lg">+ Поступление</a>
</div>

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
