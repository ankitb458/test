/**
 * Campaigns/Coupons
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
	CONSTRAINT valid_amount
		CHECK ( init_discount >= 0 AND rec_discount >= 0 ),
	CONSTRAINT valid_flow
		CHECK ( ( max_orders IS NULL OR max_orders >= 0 ) AND
			( min_date IS NULL OR max_date IS NULL OR
			max_date IS NOT NULL AND min_date <= max_date ) )
);

SELECT sluggable('campaigns'), timestampable('campaigns'), searchable('campaigns'), trashable('campaigns');

CREATE INDEX campaigns_sort ON campaigns(name);
CREATE INDEX campaigns_aff_id ON campaigns(aff_id);
CREATE INDEX campaigns_product_id ON campaigns(product_id);

COMMENT ON TABLE products IS E'Stores campaigns, coupons, and promos.

Promos are tied to products through their uuid; every product has one.

- max_orders gets decreased as new orders are *cleared*. In other words,
  it is only loosely enforced.
- An active firesale requires either or both of max_date and max_orders.
  A firesale applies dynamic discount to orders.';

/*
 * Active campaigns
 */
CREATE OR REPLACE VIEW active_campaigns
AS
	SELECT	campaigns.*
	FROM	campaigns
	WHERE	status >= 'pending';

COMMENT ON VIEW active_campaigns IS E'Active Campaigns

- status is pending or greater, i.e. it''s trackable.';

/**
 * Coupons
 */
CREATE OR REPLACE VIEW coupons
AS
	SELECT	campaigns.*
	FROM	campaigns
	WHERE	product_id IS NOT NULL;

COMMENT ON VIEW coupons IS E'Coupons

- product_id is set.';

/**
 * Active coupons
 */
CREATE OR REPLACE VIEW active_coupons
AS
	SELECT	coupons.*
	FROM	coupons
	WHERE	status = 'active'
	AND		( max_orders IS NULL OR max_orders > 0 )
	AND		( max_date IS NULL OR max_date >= NOW()::timestamp(0) with time zone );

COMMENT ON VIEW active_coupons IS E'Active Coupons

- product_id is set.
- status is active.
- max_orders, if set, is not depleted.
- max_date, if set, is not reached.';

/**
 * Promos
 */
CREATE OR REPLACE VIEW promos
AS
	SELECT	campaigns.*
	FROM	campaigns
	JOIN	products
	ON		products.uuid = campaigns.uuid;

COMMENT ON VIEW promos IS E'Promos

- A product is tied to the campaign through the uuid.';

/**
 * Active promos
 */
CREATE OR REPLACE VIEW active_promos
AS
	SELECT	promos.*
	FROM	promos
	WHERE	status = 'active'
	AND		( max_orders IS NULL OR max_orders > 0 )
	AND		( max_date IS NULL OR max_date >= NOW()::timestamp(0) with time zone );

COMMENT ON VIEW active_promos IS E'Active Promos

- A product is tied to the campaign through the uuid.
- status is active.
- max_orders, if set, is not depleted.
- max_date, if set, is not reached.';

/**
 * Clean a campaign before it gets stored.
 */
CREATE OR REPLACE FUNCTION campaigns_clean()
	RETURNS trigger
AS $$
BEGIN
	NEW.name := trim(NEW.name);
	
	IF COALESCE(NEW.name, '') = ''
	THEN
		NEW.name := 'Campaign';
	END IF;
	
	IF NEW.product_id IS NOT NULL
	THEN
		-- Enforce price/comm/discount consistency
		SELECT	CASE
				WHEN NEW.aff_id IS NOT NULL
				THEN LEAST(products.init_comm, NEW.init_discount)
				ELSE LEAST(products.init_price - products.init_comm, NEW.init_discount)
				END,
				CASE
				WHEN NEW.aff_ID IS NOT NULL
				THEN LEAST(products.rec_comm, NEW.rec_discount)
				ELSE LEAST(products.rec_price - products.rec_comm, NEW.rec_discount)
				END
		INTO	NEW.init_discount,
				NEW.rec_discount
		FROM	products
		WHERE	id = NEW.product_id;
		
		-- Force non-promos into campaigns
		IF	NEW.status >= 'future' AND NEW.init_discount = 0 AND NEW.rec_discount = 0 AND
			NOT EXISTS (
			SELECT	1
			FROM	products
			WHERE	uuid = NEW.uuid )
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
			IF	ROW(NEW.status, NEW.init_discount, NEW.rec_discount, NEW.firesale) <>
				ROW(OLD.status, OLD.init_discount, OLD.rec_discount, OLD.firesale) OR
				NEW.firesale AND ROW(NEW.max_date, NEW.max_orders) IS DISTINCT FROM ROW(OLD.max_date, OLD.max_orders)
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
