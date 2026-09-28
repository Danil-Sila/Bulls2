<?php

function stockSummary(array $post): array {
	$row = db()->query(
		'SELECT COUNT(*) AS positions,
				COALESCE(SUM(GREATEST(doses, 0)), 0) AS doses,
				COUNT(CASE WHEN doses < 0 THEN 1 END) AS negative_positions,
				COALESCE(SUM(LEAST(doses, 0)), 0) AS negative_doses
		FROM stock_balance'
	) ->fetch();

	return [
		'success'			 => true,
		'positions'			 => (int) $row['positions'],
		'doses'				 => (int) $row['doses'],
		'negative_positions' => (int) $row['negative_positions'],
		'negative_doses'	 => (int) $row['negative_doses'],
	];
}

$registered_fnc[] = 'stockSummary';

?>
