/**
 * Campaigns/Coupons table.
 */
CREATE TABLE campaigns (
	id				bigserial PRIMARY KEY,
	uuid			uuid NOT NULL DEFAULT uuid() UNIQUE,
	ukey			varchar(255) UNIQUE,
	status			status_activatable NOT NULL DEFAULT 'draft',
	name			varchar(255) NOT NULL DEFAULT '',
	aff_id			bigint REFERENCES users(id),
	product_id		bigint REFERENCES products(id),
	init_discount	numeric(8,2) NOT NULL DEFAULT 0,
	rec_discount	numeric(8,2) NOT NULL DEFAULT 0,
	min_date		timestamp(0) with time zone,
	max_date		timestamp(0) with time zone,
	max_orders		int,
	firesale		boolean NOT NULL DEFAULT FALSE,
	memo			text NOT NULL DEFAULT '',
	CONSTRAINT valid_discount
		CHECK ( init_discount >= 0 AND rec_discount >= 0 ),
	CONSTRAINT order_flow
		CHECK ( ( max_orders IS NULL OR max_orders > 0 ) AND
			( min_date IS NULL OR max_date IS NULL OR
			max_date IS NOT NULL AND min_date <= max_date ) )
);

SELECT sluggable('campaigns'), timestampable('campaigns'), searchable('campaigns');

CREATE INDEX campaigns_sort ON campaigns (name);

/**
 * Clean a campaign before it gets stored.
 */
CREATE OR REPLACE FUNCTION campaigns_clean()
	RETURNS trigger
AS $$
BEGIN
	NEW.name := trim(NEW.name);
	
	IF NEW.product_id IS NOT NULL
	THEN
		-- Ensure discounts and prices are consistent
		SELECT	CASE
				WHEN NEW.aff_id IS NULL
				THEN MIN(product.init_price, NEW.init_discount)
				ELSE MIN(product.init_comm, NEW.init_discount)
				END,
				CASE
				WHEN NEW.aff_ID IS NULL
				THEN MIN(product.rec_price, NEW.rec_discount)
				ELSE MIN(product.rec_comm, NEW.rec_discount)
				END
		INTO	NEW.init_discount,
				NEW.rec_discount
		FROM	products
		WHERE	product_id = NEW.product_id;
		
		-- Turn non-promos into campaigns
		IF NEW.status >= 'future' AND NEW.init_discount = 0 AND NEW.rec_discount = 0
		AND NOT EXISTS (
			SELECT	1
			FROM	products
			WHERE	uuid = NEW.uuid
			)
		THEN
			NEW.product_id := NULL;
		END IF;
	END IF;
	
	IF NEW.product_id IS NULL
	THEN
		-- Dump all coupon fields
		NEW.status := 'active';
		NEW.init_discount := 0;
		NEW.rec_discount := 0;
		NEW.min_date := NULL;
		NEW.max_date := NULL;
		NEW.max_orders := NULL;
		NEW.firesale := FALSE;
	ELSEIF NEW.status >= 'future'
	THEN
		-- Require a min_date
		IF NEW.min_date IS NULL
		THEN
			NEW.min_date := NOW();
		END IF;
		
		-- Reset min_date on coupon changes
		IF TG_OP = 'UPDATE'
		THEN
			IF ( NEW.status <> OLD.status OR
				NEW.init_discount <> OLD.init_discount OR
				NEW.rec_discount <> OLD.rec_discount )
			THEN
				IF NEW.min_date <= NOW() - interval '1 hour'
				THEN
					NEW.min_date := NOW();
				END IF;
			END IF;
		END IF;
		
		-- Make sure that max_date is after min_date
		IF NEW.max_date IS NOT NULL AND NEW.min_date > NEW.max_date
		THEN
			NEW.max_date := NULL;
		END IF;
		
		-- Firesales require either or both of max_date and max_orders
		IF NEW.max_date IS NULL AND NEW.max_orders IS NULL
		THEN
			NEW.firesale := FALSE;
		END IF;
	END IF;
	
	RETURN NEW;
END $$ LANGUAGE plpgsql;

CREATE TRIGGER campaigns_0_clean
	BEFORE INSERT OR UPDATE ON campaigns
FOR EACH ROW EXECUTE PROCEDURE campaigns_clean();