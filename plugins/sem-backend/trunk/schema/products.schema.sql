/*
 * Products
 */
CREATE TABLE products (
	id				bigserial PRIMARY KEY,
	uuid			uuid NOT NULL DEFAULT uuid() UNIQUE,
	ukey			slug UNIQUE,
	status			status_activatable NOT NULL DEFAULT 'draft',
	name			varchar NOT NULL,
	sku				varchar UNIQUE,
	init_price		numeric(6,2) NOT NULL DEFAULT 0,
	init_comm		numeric(6,2) NOT NULL DEFAULT 0,
	rec_price		numeric(6,2) NOT NULL DEFAULT 0,
	rec_comm		numeric(6,2) NOT NULL DEFAULT 0,
	rec_interval	interval,
	rec_count		int,
	currency		currency_code NOT NULL DEFAULT 'USD',
	launch_date		datetime,
	expire_date		datetime,
	stock			int,
	created			datetime NOT NULL DEFAULT NOW(),
	modified		datetime NOT NULL DEFAULT NOW(),
	memo			text NOT NULL DEFAULT '',
	tsv				tsvector NOT NULL,
	CONSTRAINT valid_name
		CHECK ( name <> '' ),
	CONSTRAINT valid_amounts
		CHECK ( init_price >= init_comm AND init_comm >= 0 AND
				rec_price >= rec_comm AND rec_comm >= 0 ),
	CONSTRAINT valid_interval
		CHECK ( rec_interval IS NULL AND rec_count IS NULL OR
			rec_interval >= '0' AND ( rec_count IS NULL OR rec_count >= 0 ) ),
	CONSTRAINT valid_activatable
		CHECK ( expire_date >= launch_date ),
	CONSTRAINT valid_stock
		CHECK ( stock >= 0 )
);

SELECT	activatable('products'),
		repeatable('products'),
		depletable('products', 'stock'),
		sluggable('products'),
		timestampable('products'),
		searchable('products'),
		trashable('products');

CREATE INDEX products_sort ON products(name);

COMMENT ON TABLE products IS E'Products

- rec_count corresponds to the number of installments, when applicable.
- stock gets decreased as new orders are *cleared*. In other words,
  it is only loosely enforced.';

/**
 * Active products
 */
CREATE OR REPLACE VIEW active_products
AS
SELECT	products.*
FROM	products
WHERE	status = 'active'
AND		( stock IS NULL OR stock > 0 )
AND		( expire_date IS NULL OR expire_date >= NOW()::datetime );

COMMENT ON VIEW active_products IS E'Active Products

- status is active.
- stock, if set, is not depleted.
- expire_date, if set, is not reached.';

/**
 * Clean a product before it gets stored.
 */
CREATE OR REPLACE FUNCTION products_clean()
	RETURNS trigger
AS $$
BEGIN
	-- Trim fields
	NEW.ukey := NULLIF(trim(NEW.ukey), '');
	NEW.name := NULLIF(trim(NEW.name), '');
	NEW.sku := NULLIF(trim(NEW.sku), '');
	
	-- Default name and ukey
	NEW.name := COALESCE(NEW.name, NEW.ukey, NEW.sku, 'Product');
	
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