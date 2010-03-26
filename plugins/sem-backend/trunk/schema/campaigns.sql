/**
 * Campaigns/Coupons
 */
CREATE TABLE campaigns (
	id				bigserial PRIMARY KEY,
	uuid			uuid NOT NULL DEFAULT uuid() UNIQUE,
	ukey			varchar(255) UNIQUE,
	status			status_activatable NOT NULL DEFAULT 'draft',
	name			varchar(255) NOT NULL DEFAULT '',
	aff_id			bigint REFERENCES users(id) ON UPDATE CASCADE,
	promo_id		bigint REFERENCES products(id) ON UPDATE CASCADE ON DELETE CASCADE DEFERRABLE UNIQUE,
	product_id		bigint REFERENCES products(id) ON UPDATE CASCADE DEFERRABLE,
	init_discount	numeric(8,2) NOT NULL DEFAULT 0,
	rec_discount	numeric(8,2) NOT NULL DEFAULT 0,
	min_date		timestamp(0) with time zone,
	max_date		timestamp(0) with time zone,
	max_orders		int,
	firesale		boolean NOT NULL DEFAULT FALSE,
	memo			text NOT NULL DEFAULT '',
	CONSTRAINT valid_promo
		CHECK ( promo_id IS NULL OR
			promo_id IS NOT DISTINCT FROM product_id AND aff_id IS NULL ),
	CONSTRAINT valid_amount
		CHECK ( init_discount >= 0 AND rec_discount >= 0 ),
	CONSTRAINT valid_flow
		CHECK ( ( max_orders IS NULL OR max_orders >= 0 ) AND
			( min_date IS NULL OR max_date IS NULL OR
			max_date IS NOT NULL AND min_date <= max_date ) ),
	CONSTRAINT valid_firesale
		CHECK ( NOT firesale OR max_orders IS NOT NULL OR max_date IS NOT NULL )
);

SELECT	activatable('campaigns'),
		sluggable('campaigns'),
		timestampable('campaigns'),
		searchable('campaigns'),
		trashable('campaigns');

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
WHERE	promo_id IS NOT NULL;

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
DECLARE
	p		record;
BEGIN
	-- Trim fields
	NEW.name := trim(NEW.name);
	
	-- Default name
	IF	COALESCE(NEW.name, '') = ''
	THEN
		NEW.name := 'Campaign';
	END IF;
	
	-- Handle inherit status
	IF	NEW.status = 'inherit' AND NEW.promo_id IS NULL
	THEN
		NEW.status = 'trash';
	ELSEIF NEW.status = 'trash' AND NEW.promo_id IS NOT NULL
	THEN
		NEW.status = 'inherit';
	END IF;
	
	IF	NEW.product_id IS NOT NULL
	THEN
		-- Validate product and sanitize status
		SELECT	uuid,
				status,
				init_price,
				init_comm,
				rec_price,
				rec_comm
		INTO	p
		FROM	products
		WHERE	id = NEW.product_id;
		
		IF	NEW.product_id = NEW.promo_id
		THEN
			NEW.status := CASE
				WHEN p.status <= 'inherit'
				THEN 'inherit'
				WHEN p.status = 'draft'
				THEN 'draft'
				WHEN p.status = 'pending'
				THEN 'pending'
				WHEN p.status < 'future'
				THEN 'inactive'
				ELSE NEW.status
				END::status_activatable;
		ELSE
			IF p.status < 'future'
			THEN
				NEW.product_id := NULL;
			ELSEIF NEW.status = 'inherit' -- allowed for promos only
			THEN
				NEW.status := 'trash';
			END IF;
		END IF;
	END IF;
	
	IF	NEW.product_id IS NULL
	THEN
		-- Reset all coupon fields
		IF	NEW.status > 'inherit'
		THEN
			NEW.status := 'active';
		END IF;
		NEW.init_discount := 0;
		NEW.rec_discount := 0;
		NEW.min_date := NULL;
		NEW.max_date := NULL;
		NEW.max_orders := NULL;
		NEW.firesale := FALSE;
	ELSE
		-- Sanitize discount
		IF NEW.aff_id IS NOT NULL
		THEN
			NEW.init_discount := LEAST(NEW.init_discount, p.init_comm);
			NEW.rec_discount := LEAST(NEW.rec_discount, p.rec_comm);
		ELSE
			NEW.init_discount := LEAST(NEW.init_discount, p.init_price - p.init_comm);
			NEW.rec_discount := LEAST(NEW.rec_discount, p.rec_price - p.rec_comm);
		END IF;
		
		-- Require a discount
		IF	NEW.status >= 'future' AND NEW.init_discount = 0 AND NEW.rec_discount = 0
		THEN
			NEW.status = 'inactive';
		END IF;
		
		-- Firesales require either or both of max_date and max_orders
		IF	NEW.firesale AND NEW.max_date IS NULL AND NEW.max_orders IS NULL
		THEN
			NEW.firesale := FALSE;
		END IF;
	END IF;
	
	RETURN NEW;
END;
$$ LANGUAGE plpgsql;

CREATE TRIGGER campaigns_3_clean
	BEFORE INSERT OR UPDATE ON campaigns
FOR EACH ROW EXECUTE PROCEDURE campaigns_clean();