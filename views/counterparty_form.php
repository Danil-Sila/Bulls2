<div class="modal fade" id="cpModal" tabindex="-1" aria-labelledby="cpModalTitle" aria-hidden="true">
	<div class="modal-dialog modal-lg modal-fullscreen-sm-down">
		<form id="cpForm" class="modal-content">
			<div class="modal-header">
				<h2 class="modal-title h5" id="cpModalTitle"></h2>
				<button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Закрыть"></button>
			</div>
			<div class="modal-body">
				<div id="cpFormError" class="alert alert-danger" role="alert" hidden></div>
				<div class="row g-3">
					<div class="col-12">
						<label for="cpName" class="form-label">Название</label>
						<input type="text" id="cpName" name="name" class="form-control" maxlength="150" autocomplete="off">
						<div class="invalid-feedback"></div>
					</div>
					<div class="col-12 col-md-6">
						<label for="cpRegion" class="form-label">Регион</label>
						<select id="cpRegion" name="location_id" class="form-select"></select>
						<div class="invalid-feedback"></div>
					</div>
					<div class="col-12 col-md-6">
						<label for="cpDistrict" class="form-label">Район или город</label>
						<select id="cpDistrict" name="district_id" class="form-select"></select>
						<div class="invalid-feedback"></div>
					</div>
					<div class="col-12">
						<label for="cpAddress" class="form-label">Адрес</label>
						<input type="text" id="cpAddress" name="address" class="form-control" maxlength="255" placeholder="Село, улица, дом" autocomplete="off">
						<div class="invalid-feedback"></div>
					</div>
					<div id="cpActiveBox" class="col-12" hidden>
						<div class="form-check">
							<input type="checkbox" id="cpActive" name="is_active" class="form-check-input">
							<label for="cpActive" class="form-check-label">Активен</label>
							<div class="form-text">Снимите флажок, чтобы убрать запись в архив: она пропадёт из выпадающих списков, но останется в отчётах.</div>
						</div>
					</div>
				</div>
			</div>
			<div class="modal-footer">
				<button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Отмена</button>
				<button type="submit" id="cpSubmit" class="btn btn-primary"></button>
			</div>
		</form>
	</div>
</div>
