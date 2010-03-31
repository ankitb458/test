/*
 * Products
 */
CREATE TABLE products (
	id				bigserial PRIMARY KEY,
	uuid			uuid NOT NULL DEFAULT uuid() UNIQUE,
	ukey			varchar(255) UNIQUE,
	status			status_activatable NOT NULL DEFAULT 'draft',
	name			varchar(255) NOT NULL,
	sku				varchar(255) UNIQUE,
	init_price		numeric(8,2) NOT NULL DEFAULT 0,
	init_comm		numeric(8,2) NOT NULL DEFAULT 0,
	rec_price		numeric(8,2) NOT NULL DEFAULT 0,
	rec_comm		numeric(8,2) NOT NULL DEFAULT 0,
	rec_interval	interval,
	rec_count		smallint,
	currency		currency_code NOT NULL DEFAULT 'USD',
	weight			numeric(7,3),
	volume			numeric(7,3)[3],
	min_date		datetime,
	max_date		datetime,
	max_orders		int,
	memo			text NOT NULL DEFAULT '',
	CONSTRAINT valid_ukey
		CHECK ( ukey ~ '^[a-z0-9](?:[a-z0-9._-]*[a-z0-9])?$' AND ukey !~ '^[0-9]+$' ),
	CONSTRAINT valid_amounts
		CHECK ( init_price >= init_comm AND init_comm >= 0 AND
				rec_price >= rec_comm AND rec_comm >= 0 ),
	CONSTRAINT valid_interval
		CHECK ( rec_interval IS NULL AND rec_count IS NULL OR
			rec_interval >= '0' AND ( rec_count IS NULL OR rec_count >= 0 ) ),
	CONSTRAINT valid_min_max_date
		CHECK ( min_date IS NULL OR max_date IS NULL OR min_date <= max_date ),
	CONSTRAINT valid_weight
		CHECK ( weight >= 0 ),
	CONSTRAINT valid_volume
		CHECK ( volume > ARRAY[0,0,0]::numeric(7,3)[3] ),
	CONSTRAINT undefined_behavior
		CHECK ( status <> 'inherit' AND rec_count IS NULL AND weight IS NULL AND volume IS NULL )
);

SELECT	activatable('products'),
		repeatable('products'),
		depletable('products', 'max_orders'),
		sluggable('products'),
		timestampable('products'),
		searchable('products'),
		trashable('products');

CREATE INDEX products_sort ON products(name);

COMMENT ON TABLE products IS E'Products

- rec_count corresponds to the number of installments, when applicable.
- max_orders gets decreased as new orders are *cleared*. In other words,
  it is only loosely enforced.';

/**
 * Active products
 */
CREATE OR REPLACE VIEW active_products
AS
SELECT	products.*
FROM	products
WHERE	status = 'active'
AND		( max_orders IS NULL OR max_orders > 0 )
AND		( max_date IS NULL OR max_date >= NOW()::datetime );

COMMENT ON VIEW active_products IS E'Active Products

- status is active.
- max_orders, if set, is not depleted.
- max_date, if set, is not reached.';

/**
 * Clean a product before it gets stored.
 */
CREATE OR REPLACE FUNCTION products_clean()
	RETURNS trigger
AS $$
BEGIN
	-- Trim fields
	NEW.name := NULLIF(trim(NEW.name, ''), '');
	NEW.sku := NULLIF(trim(NEW.sku, ''), '');
	
	-- Default name
	IF	NEW.name IS NULL
	THEN
		NEW.name := 'Product';
	END IF;
	
	-- Fix commissions if needed
	NEW.init_comm := LEAST(NEW.init_comm, NEW.init_price);
	NEW.rec_comm := LEAST(NEW.rec_comm, NEW.rec_price);
	
	RETURN NEW;
END;
$$ LANGUAGE plpgsql;

CREATE TRIGGER products_05_clean
	BEFORE INSERT OR UPDATE ON products
FOR EACH ROW EXECUTE PROCEDURE products_clean();

/**
 * Add SKU to the tsv
 */
CREATE OR REPLACE FUNCTION products_tsv()
	RETURNS trigger
AS $$
BEGIN
	IF	NEW.sku IS NULL
	THEN
		RETURN NEW;
	ELSEIF TG_OP = 'UPDATE'
	THEN
		IF	NEW.tsv IS NOT DISTINCT FROM OLD.tsv AND
			NEW.sku IS NOT DISTINCT FROM OLD.sku
		THEN
			RETURN NEW;
		END IF;
	END IF;
	
	NEW.tsv := NEW.tsv || setweight(to_tsvector(NEW.tsv), 'A');
	
	RETURN NEW;
END $$ LANGUAGE plpgsql;

CREATE TRIGGER products_20_tsv
	BEFORE INSERT OR UPDATE ON products
FOR EACH ROW EXECUTE PROCEDURE products_tsv();