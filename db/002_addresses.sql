-- МЕСТА: страна → федеральный округ → регион → район или город --
CREATE TABLE locations (
	id        INT UNSIGNED     NOT NULL AUTO_INCREMENT PRIMARY KEY,
	parent_id INT UNSIGNED     NULL,
	level     TINYINT UNSIGNED NOT NULL COMMENT '0 страна, 1 округ, 2 регион, 3 район или город',
	name      VARCHAR(100)     NOT NULL,
	KEY ix_locations_parent (parent_id),
	CONSTRAINT fk_locations_parent FOREIGN KEY (parent_id) REFERENCES locations (id) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Место теперь выбирается из справочника, текст строится представлением location_names --
ALTER TABLE buyers
	DROP COLUMN location,
	ADD COLUMN location_id INT UNSIGNED NULL AFTER name,
	ADD COLUMN address     VARCHAR(255) NULL AFTER location_id,
	ADD CONSTRAINT fk_buyers_location FOREIGN KEY (location_id) REFERENCES locations (id) ON DELETE RESTRICT;

ALTER TABLE contractors
	DROP COLUMN location,
	ADD COLUMN location_id INT UNSIGNED NULL AFTER name,
	ADD COLUMN address     VARCHAR(255) NULL AFTER location_id,
	ADD CONSTRAINT fk_contractors_location FOREIGN KEY (location_id) REFERENCES locations (id) ON DELETE RESTRICT;

-- НАЗВАНИЕ МЕСТА: «район, регион» для района, иначе своё имя --
CREATE VIEW location_names AS
SELECT l.id, IF(l.level = 3, CONCAT(l.name, ', ', p.name), l.name) AS name
FROM locations l
LEFT JOIN locations p ON p.id = l.parent_id;
