-- УПАКОВКА --
CREATE TABLE packagings (
	id         INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
	name       VARCHAR(100) NOT NULL,
	short_name VARCHAR(10)  NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ХРАНИЛИЩЕ --
CREATE TABLE storages (
	id   INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
	name VARCHAR(100) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- КАТЕГОРИИ --
CREATE TABLE categories (
	id   INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
	code VARCHAR(10)  NOT NULL,
	UNIQUE KEY uq_categories_code (code)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ПОРОДЫ --
CREATE TABLE breeds (
	id        INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
	name      VARCHAR(100) NOT NULL,
	is_active TINYINT(1)   NOT NULL DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ПОСТАВЩИКИ --
CREATE TABLE contractors (
	id         INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
	name       VARCHAR(150) NOT NULL,
	location   VARCHAR(255) NULL,
	is_active  TINYINT(1)   NOT NULL DEFAULT 1,
	created_at DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
	updated_at DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ПОКУПАТЕЛИ --
CREATE TABLE buyers (
	id         INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
	name       VARCHAR(150) NOT NULL,
	location   VARCHAR(255) NULL,
	is_active  TINYINT(1)   NOT NULL DEFAULT 1,
	created_at DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
	updated_at DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
	KEY ix_buyers_name (name)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- БЫЧКИ --
CREATE TABLE bulls (
	id            INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
	num           VARCHAR(50)  NOT NULL,
	name          VARCHAR(100) NOT NULL,
	breed_id      INT UNSIGNED NOT NULL,
	vendor_id     INT UNSIGNED NULL,
	supplier_id   INT UNSIGNED NOT NULL,
	category_id   INT UNSIGNED NULL,
	category_year YEAR         NULL,
	is_active     TINYINT(1)   NOT NULL DEFAULT 1,
	created_at    DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
	updated_at    DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
	KEY ix_bulls_name (name),
	KEY ix_bulls_num (num),
	CONSTRAINT fk_bulls_breed    FOREIGN KEY (breed_id)    REFERENCES breeds (id)      ON DELETE RESTRICT,
	CONSTRAINT fk_bulls_vendor   FOREIGN KEY (vendor_id)   REFERENCES contractors (id) ON DELETE RESTRICT,
	CONSTRAINT fk_bulls_supplier FOREIGN KEY (supplier_id) REFERENCES contractors (id) ON DELETE RESTRICT,
	CONSTRAINT fk_bulls_category FOREIGN KEY (category_id) REFERENCES categories (id)  ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ПОСТУПЛЕНИЯ --
CREATE TABLE receipts (
	id           INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
	received_on  DATE         NOT NULL,
	storage_id   INT UNSIGNED NOT NULL,
	bull_id      INT UNSIGNED NOT NULL,
	packaging_id INT UNSIGNED NOT NULL,
	doses        INT UNSIGNED NOT NULL,
	created_at   DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
	updated_at   DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
	deleted_at   DATETIME     NULL,
	KEY ix_receipts_received_on (received_on),
	CONSTRAINT chk_receipts_doses    CHECK (doses > 0),
	CONSTRAINT fk_receipts_storage   FOREIGN KEY (storage_id)   REFERENCES storages (id)   ON DELETE RESTRICT,
	CONSTRAINT fk_receipts_bull      FOREIGN KEY (bull_id)      REFERENCES bulls (id)      ON DELETE RESTRICT,
	CONSTRAINT fk_receipts_packaging FOREIGN KEY (packaging_id) REFERENCES packagings (id) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ПРОДАЖИ --
CREATE TABLE sales (
	id            INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
	sold_on       DATE         NOT NULL,
	buyer_id      INT UNSIGNED NOT NULL,
	contractor_id INT UNSIGNED NOT NULL,
	bull_id       INT UNSIGNED NOT NULL,
	storage_id    INT UNSIGNED NOT NULL,
	packaging_id  INT UNSIGNED NOT NULL,
	doses         INT UNSIGNED NOT NULL,
	created_at    DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
	updated_at    DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
	deleted_at    DATETIME     NULL,
	KEY ix_sales_sold_on (sold_on),
	CONSTRAINT chk_sales_doses     CHECK (doses > 0),
	CONSTRAINT fk_sales_buyer      FOREIGN KEY (buyer_id)      REFERENCES buyers (id)      ON DELETE RESTRICT,
	CONSTRAINT fk_sales_contractor FOREIGN KEY (contractor_id) REFERENCES contractors (id) ON DELETE RESTRICT,
	CONSTRAINT fk_sales_bull       FOREIGN KEY (bull_id)       REFERENCES bulls (id)       ON DELETE RESTRICT,
	CONSTRAINT fk_sales_storage    FOREIGN KEY (storage_id)    REFERENCES storages (id)    ON DELETE RESTRICT,
	CONSTRAINT fk_sales_packaging  FOREIGN KEY (packaging_id)  REFERENCES packagings (id)  ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ОСТАТОК: поступления минус продажи, без удалённых в корзину --
CREATE VIEW stock_balance AS
SELECT m.storage_id, m.bull_id, m.packaging_id, SUM(m.doses) AS doses
FROM (
	SELECT storage_id, bull_id, packaging_id, CAST(doses AS SIGNED) AS doses FROM receipts WHERE deleted_at IS NULL
	UNION ALL
	SELECT storage_id, bull_id, packaging_id, -CAST(doses AS SIGNED) FROM sales WHERE deleted_at IS NULL
) AS m
GROUP BY m.storage_id, m.bull_id, m.packaging_id
HAVING SUM(m.doses) <> 0;
