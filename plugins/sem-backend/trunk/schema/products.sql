/*
 * Products table.
 */
CREATE TABLE products (
	id				bigserial PRIMARY KEY,
	uuid			uuid NOT NULL DEFAULT uuid() UNIQUE,
	ukey			varchar(255) UNIQUE,
	status			status_activatable NOT NULL DEFAULT 'draft',
	name			varchar(255) NOT NULL DEFAULT '',
	init_price		numeric(8,2) NOT NULL DEFAULT 0,
	init_comm		numeric(8,2) NOT NULL DEFAULT 0,
	rec_price		numeric(8,2) NOT NULL DEFAULT 0,
	rec_comm		numeric(8,2) NOT NULL DEFAULT 0,
	rec_interval	interval,
	rec_count		smallint,
	min_date		timestamp(0) with time zone,
	max_date		timestamp(0) with time zone,
	max_orders		int,
	memo			text NOT NULL DEFAULT '',
	CONSTRAINT valid_price
		CHECK ( init_price >= 0 AND init_comm >= 0 AND init_price >= init_comm AND
				rec_price >= 0 AND rec_comm >= 0 AND rec_price >= rec_comm ),
	CONSTRAINT valid_interval
		CHECK ( rec_interval IS NULL AND rec_count IS NULL OR
			rec_interval >= '0' AND ( rec_count IS NULL OR rec_count >= 0 ) ),
	CONSTRAINT valid_order_flow
		CHECK ( ( max_orders IS NULL OR max_orders > 0 ) AND
			( min_date IS NULL OR max_date IS NULL OR
			max_date IS NOT NULL AND min_date <= max_date ) )
);

SELECT sluggable('products'), timestampable('products'), searchable('products');

CREATE INDEX products_sort ON products(name);

/**
 * Clean a product before it gets stored.
 */
CREATE OR REPLACE FUNCTION products_clean()
	RETURNS trigger
AS $$
BEGIN
	NEW.name := trim(NEW.name);
	
	IF COALESCE(NEW.name, '') = ''
	THEN
		NEW.name := 'Product';
	END IF;
	
	IF NEW.status >= 'future' AND NEW.rec_interval IS NULL AND NEW.rec_count IS NOT NULL
	THEN
		NEW.rec_count := NULL;
	END IF;
	
	IF NEW.status >= 'future'
	THEN
		-- Make sure that max_date is after min_date
		IF NEW.min_date IS NOT NULL AND NEW.max_date IS NOT NULL AND NEW.min_date > NEW.max_date
		THEN
			NEW.max_date := NULL;
		END IF;
	END IF;
	
	RETURN NEW;
END $$ LANGUAGE plpgsql;

CREATE TRIGGER products_0_clean
	BEFORE INSERT OR UPDATE ON products
FOR EACH ROW EXECUTE PROCEDURE products_clean();