<?php

function buyersList(array $post): array {
	$rows = db()->query(
		'SELECT b.id, b.name, b.location_id, ln.name AS location, b.address, b.is_active, MAX(s.sold_on) AS last_sale
		 FROM buyers b
		 LEFT JOIN location_names ln ON ln.id = b.location_id
		 LEFT JOIN sales s ON s.buyer_id = b.id
		 GROUP BY b.id, ln.name
		 ORDER BY b.is_active DESC, b.name'
	)->fetchAll();

	return ['success' => true, 'rows' => $rows];
}

$registered_fnc[] = 'buyersList';

?>
