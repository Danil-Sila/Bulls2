const view = {
	error: document.getElementById('error'),
	stockDoses: document.getElementById('stockDoses'),
	stockPositions: document.getElementById('stockPositions'),
	negative: document.getElementById('negative'),
	negativeDoses: document.getElementById('negativeDoses'),
	negativePositions: document.getElementById('negativePositions'),
};

function doses(n) {
	return `${formatNumber(n)} ${plural(Math.abs(n), 'доза', 'дозы', 'доз')}`;
}

function positions(n) {
	return `${formatNumber(n)} ${plural(n, 'позиция', 'позиции', 'позиций')}`;
}

async function loadSummary() {
	try {
		const data = await callApi('stockSummary');
		view.stockDoses.textContent = doses(data.doses);
		view.stockPositions.textContent = positions(data.positions - data.negative_positions);
		view.negativeDoses.textContent = doses(data.negative_doses);
		view.negativePositions.textContent = positions(data.negative_positions);
		view.negative.hidden = data.negative_positions === 0;
	} catch (error) {
		view.stockDoses.textContent = '—';
		showError(view.error, `Не удалось загрузить остатки: ${error.message}`);
	}
}

loadSummary();
