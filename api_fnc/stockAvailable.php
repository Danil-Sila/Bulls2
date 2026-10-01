<?php

function stockAvailable(array $post): array {
	$bullId = optional_id($post['bull_id'] ?? null);
	$packagingId = optional_id($post['packaging_id'] ?? null);
	if ($bullId === null || $packagingId === null) {
		return fail('Укажите быка и упаковку');
	}
	$saleId = optional_id($post['sale_id'] ?? null);

	$stock = db()->prepare('SELECT COALESCE(SUM(doses), 0) FROM stock_balance WHERE bull_id = :bull_id AND packaging_id = :packaging_id');
	$stock->execute(['bull_id' => $bullId, 'packaging_id' => $packagingId]);
	$doses = (int) $stock->fetchColumn();

	// При правке продажи её собственные дозы возвращаются в доступные, но только если позиция не сменилась
	if ($saleId !== null) {
		$own = db()->prepare(
			'SELECT COALESCE(SUM(doses), 0) FROM sales
			 WHERE id = :id AND bull_id = :bull_id AND packaging_id = :packaging_id AND deleted_at IS NULL'
		);
		$own->execute(['id' => $saleId, 'bull_id' => $bullId, 'packaging_id' => $packagingId]);
		$doses += (int) $own->fetchColumn();
	}

	return ['success' => true, 'doses' => $doses];
}

$registered_fnc[] = 'stockAvailable';

?>
