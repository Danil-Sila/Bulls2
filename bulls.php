<?php
require __DIR__ . '/setup.php';

$title = 'Быки';
$page = 'bulls.php';
$scripts = ['bulls.js'];
require APP_ROOT . 'views/header.php';
?>

<div class="d-flex flex-wrap align-items-center justify-content-between gap-2 mb-3">
	<h1 class="h3 mb-0">Быки</h1>
	<button type="button" id="addBull" class="btn btn-success">+ Добавить быка</button>
</div>

<form id="filters" class="card card-body mb-3">
	<div class="row g-2 align-items-end">
		<div class="col-12 col-lg-3">
			<label for="search" class="form-label small mb-1">Номер или кличка</label>
			<input type="search" id="search" class="form-control" autocomplete="off">
		</div>
		<div class="col-6 col-md-4 col-lg-2">
			<label for="breed" class="form-label small mb-1">Порода</label>
			<select id="breed" class="form-select"><option value="">Все породы</option></select>
		</div>
		<div class="col-6 col-md-4 col-lg-3">
			<label for="vendor" class="form-label small mb-1">Производитель</label>
			<select id="vendor" class="form-select"><option value="">Все производители</option></select>
		</div>
		<div class="col-6 col-md-4 col-lg-2">
			<label for="archive" class="form-label small mb-1">Статус</label>
			<select id="archive" class="form-select">
				<option value="active">Активные</option>
				<option value="archived">В архиве</option>
				<option value="all">Все</option>
			</select>
		</div>
		<div class="col-6 col-lg-2">
			<div class="form-check mb-2">
				<input type="checkbox" id="inStock" class="form-check-input">
				<label for="inStock" class="form-check-label">Только с остатком</label>
			</div>
		</div>
	</div>
</form>

<div id="error" class="alert alert-danger" role="alert" hidden></div>
<div id="summary" class="text-body-secondary mb-2" aria-live="polite"></div>

<div class="card">
	<div class="table-responsive">
		<table class="table table-cards table-hover table-sm align-middle mb-0">
			<thead class="table-light">
				<tr>
					<th scope="col">Номер</th>
					<th scope="col">Кличка</th>
					<th scope="col">Порода</th>
					<th scope="col">Категория</th>
					<th scope="col">Производитель</th>
					<th scope="col">Поставщик</th>
					<th scope="col" class="text-end">Остаток, доз</th>
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

<div class="modal fade" id="bullModal" tabindex="-1" aria-labelledby="bullModalTitle" aria-hidden="true">
	<div class="modal-dialog modal-lg modal-fullscreen-sm-down">
		<form id="bullForm" class="modal-content">
			<div class="modal-header">
				<h2 class="modal-title h5" id="bullModalTitle"></h2>
				<button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Закрыть"></button>
			</div>
			<div class="modal-body">
				<div id="bullFormError" class="alert alert-danger" role="alert" hidden></div>
				<div class="row g-3">
					<div class="col-12 col-md-4">
						<label for="bullNum" class="form-label">Номер</label>
						<input type="text" id="bullNum" name="num" class="form-control" maxlength="50" autocomplete="off">
						<div class="invalid-feedback"></div>
					</div>
					<div class="col-12 col-md-8">
						<label for="bullName" class="form-label">Кличка</label>
						<input type="text" id="bullName" name="name" class="form-control" maxlength="100" autocomplete="off">
						<div class="invalid-feedback"></div>
					</div>
					<div class="col-12">
						<label for="bullBreed" class="form-label">Порода</label>
						<select id="bullBreed" name="breed_id" class="form-select"></select>
						<div class="invalid-feedback"></div>
					</div>
					<div class="col-12 col-md-6">
						<label for="bullVendor" class="form-label">Производитель</label>
						<select id="bullVendor" name="vendor_id" class="form-select"></select>
						<div class="invalid-feedback"></div>
					</div>
					<div class="col-12 col-md-6">
						<label for="bullSupplier" class="form-label">Поставщик</label>
						<select id="bullSupplier" name="supplier_id" class="form-select"></select>
						<div class="invalid-feedback"></div>
					</div>
					<div class="col-12 col-md-6">
						<label for="bullCategory" class="form-label">Категория</label>
						<select id="bullCategory" name="category_id" class="form-select"></select>
						<div class="invalid-feedback"></div>
					</div>
					<div class="col-12 col-md-6">
						<label for="bullYear" class="form-label">Год присвоения категории</label>
						<input type="text" id="bullYear" name="category_year" class="form-control" inputmode="numeric" maxlength="4" autocomplete="off">
						<div class="invalid-feedback"></div>
					</div>
					<div id="bullActiveBox" class="col-12" hidden>
						<div class="form-check">
							<input type="checkbox" id="bullActive" name="is_active" class="form-check-input">
							<label for="bullActive" class="form-check-label">Активен</label>
							<div class="form-text">Снимите флажок, чтобы убрать быка в архив: он пропадёт из выпадающих списков, но останется в отчётах.</div>
						</div>
					</div>
				</div>
			</div>
			<div class="modal-footer">
				<button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Отмена</button>
				<button type="submit" id="bullSubmit" class="btn btn-primary"></button>
			</div>
		</form>
	</div>
</div>

<?php require APP_ROOT . 'views/footer.php'; ?>
