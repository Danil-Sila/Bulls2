<?php

function buyersList(array $post): array {
	$rows = db()->query(
		'SELECT b.id, b.name, b.location, b.is_active, MAX(s.sold_on) AS last_sale
		 FROM buyers b
		 LEFT JOIN sales s ON s.buyer_id = b.id AND s.deleted_at IS NULL
		 GROUP BY b.id
		 ORDER BY b.is_active DESC, b.name'
	)->fetchAll();

	return ['success' => true, 'rows' => $rows];
}

$registered_fnc[] = 'buyersList';

?>
