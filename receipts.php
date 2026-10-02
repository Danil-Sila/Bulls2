<?php
require __DIR__ . '/setup.php';

$title = 'Поступления';
$page = 'receipts.php';
$scripts = ['receipts.js'];
require APP_ROOT . 'views/header.php';
?>

<div class="d-flex flex-wrap align-items-center justify-content-between gap-2 mb-3">
	<h1 class="h3 mb-0">Поступления</h1>
	<button type="button" id="addReceipt" class="btn btn-success">+ Добавить поступление</button>
</div>

<form id="filters" class="card card-body mb-3">
	<div class="row g-2 align-items-end">
		<div class="col-6 col-lg-3">
			<label for="dateFrom" class="form-label small mb-1">Период с</label>
			<input type="date" id="dateFrom" class="form-control">
		</div>
		<div class="col-6 col-lg-3">
			<label for="dateTo" class="form-label small mb-1">по</label>
			<input type="date" id="dateTo" class="form-control">
		</div>
		<div class="col-12 col-lg-6">
			<label for="bull" class="form-label small mb-1">Бык</label>
			<select id="bull" class="form-select"><option value="">Все быки</option></select>
		</div>
	</div>
</form>

<div id="error" class="alert alert-danger" role="alert" hidden></div>
<div id="summary" class="text-body-secondary mb-2" aria-live="polite"></div>

<div class="card">
	<div class="table-responsive">
		<table class="table table-hover table-sm align-middle mb-0">
			<thead class="table-light">
				<tr>
					<th scope="col">Дата</th>
					<th scope="col">Бык</th>
					<th scope="col">Порода</th>
					<th scope="col" class="text-end">Доз</th>
					<th scope="col">Упаковка</th>
					<th scope="col" class="text-end">№</th>
					<th scope="col"></th>
				</tr>
			</thead>
			<tbody id="rows"></tbody>
		</table>
	</div>
</div>

<nav class="d-flex align-items-center justify-content-center gap-3 mt-3" aria-label="Страницы">
	<button type="button" id="prev" class="btn btn-outline-primary" disabled>← Назад</button>
	<span id="pageInfo" class="text-body-secondary"></span>
	<button type="button" id="next" class="btn btn-outline-primary" disabled>Вперёд →</button>
</nav>

<div class="modal fade" id="receiptModal" tabindex="-1" aria-labelledby="receiptModalTitle" aria-hidden="true">
	<div class="modal-dialog modal-fullscreen-sm-down">
		<form id="receiptForm" class="modal-content">
			<div class="modal-header">
				<h2 class="modal-title h5" id="receiptModalTitle"></h2>
				<button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Закрыть"></button>
			</div>
			<div class="modal-body">
				<div id="receiptFormError" class="alert alert-danger" role="alert" hidden></div>
				<div class="row g-3">
					<div class="col-12">
						<label for="receiptDate" class="form-label">Дата</label>
						<input type="date" id="receiptDate" name="received_on" class="form-control">
						<div class="invalid-feedback"></div>
					</div>
					<div class="col-12">
						<label for="receiptBull" class="form-label">Бык</label>
						<select id="receiptBull" name="bull_id" class="form-select"></select>
						<div class="invalid-feedback"></div>
					</div>
					<div class="col-12 col-md-6">
						<label for="receiptPackaging" class="form-label">Упаковка</label>
						<select id="receiptPackaging" name="packaging_id" class="form-select"></select>
						<div class="invalid-feedback"></div>
					</div>
					<div class="col-12 col-md-6">
						<label for="receiptDoses" class="form-label">Доз</label>
						<input type="text" id="receiptDoses" name="doses" class="form-control" inputmode="numeric" maxlength="7" autocomplete="off">
						<div class="invalid-feedback"></div>
					</div>
				</div>
			</div>
			<div class="modal-footer">
				<button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Отмена</button>
				<button type="submit" id="receiptSubmit" class="btn btn-primary"></button>
			</div>
		</form>
	</div>
</div>

<?php require APP_ROOT . 'views/footer.php'; ?>
