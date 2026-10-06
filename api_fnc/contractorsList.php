<?php

function contractorsList(array $post): array {
	// Подзапросы, а не два LEFT JOIN: при двух JOIN строки быков и продаж перемножаются и COUNT врёт
	$rows = db()->query(
		'SELECT c.id, c.name, c.location_id, ln.name AS location, c.address, c.is_active,
			(SELECT COUNT(*) FROM bulls b WHERE b.vendor_id = c.id OR b.supplier_id = c.id) AS bulls,
			(SELECT MAX(s.sold_on) FROM sales s WHERE s.contractor_id = c.id) AS last_sale
		 FROM contractors c
		 LEFT JOIN location_names ln ON ln.id = c.location_id
		 ORDER BY c.is_active DESC, c.name'
	)->fetchAll();

	return ['success' => true, 'rows' => $rows];
}

$registered_fnc[] = 'contractorsList';

?>
