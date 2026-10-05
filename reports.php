<?php
require __DIR__ . '/setup.php';

$title = 'Отчёты';
$page = 'reports.php';
$wide = true;
$scripts = ['reports.js'];
require APP_ROOT . 'views/header.php';
?>

<div class="d-flex flex-wrap align-items-center justify-content-between gap-2 mb-3 d-print-none">
	<h1 class="h3 mb-0">Отчёты</h1>
	<div class="d-flex gap-2">
		<button type="button" id="printReport" class="btn btn-outline-secondary" disabled>Печать</button>
		<button type="button" id="exportCsv" class="btn btn-outline-secondary" disabled>CSV</button>
	</div>
</div>

<form id="filters" class="card card-body mb-3 d-print-none">
	<div class="row g-2 align-items-end">
		<div class="col-12 col-md-6 col-lg-4">
			<label for="report" class="form-label small mb-1">Отчёт</label>
			<select id="report" class="form-select"></select>
		</div>
		<div class="col-6 col-lg-2" data-kind="period">
			<label for="dateFrom" class="form-label small mb-1">Период с</label>
			<input type="date" id="dateFrom" class="form-control">
		</div>
		<div class="col-6 col-lg-2" data-kind="period">
			<label for="dateTo" class="form-label small mb-1">по</label>
			<input type="date" id="dateTo" class="form-control">
		</div>
		<div class="col-6 col-lg-2" data-kind="month">
			<label for="month" class="form-label small mb-1">Месяц</label>
			<select id="month" class="form-select"></select>
		</div>
		<div class="col-6 col-lg-2" data-kind="month">
			<label for="year" class="form-label small mb-1">Год</label>
			<input type="number" id="year" class="form-control" min="2000" max="2100">
		</div>
		<div class="col-12 col-md-6 col-lg-4" data-buyer>
			<label for="buyer" class="form-label small mb-1">Покупатель</label>
			<select id="buyer" class="form-select"></select>
		</div>
	</div>
</form>

<div id="error" class="alert alert-danger d-print-none" role="alert" hidden></div>

<div id="result" hidden>
	<h2 id="reportTitle" class="h5 mb-2"></h2>
	<div class="card">
		<div class="table-responsive">
			<table class="table table-hover table-sm align-middle mb-0">
				<thead class="table-light">
					<tr id="head"></tr>
				</thead>
				<tbody id="rows"></tbody>
			</table>
		</div>
	</div>
	<div id="totals" class="mt-2"></div>
</div>

<?php require APP_ROOT . 'views/footer.php'; ?>
