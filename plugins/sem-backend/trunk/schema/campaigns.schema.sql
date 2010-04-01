/**
 * Campaigns/Coupons
 */
CREATE TABLE campaigns (
	id				bigserial PRIMARY KEY,
	uuid			uuid NOT NULL DEFAULT uuid() UNIQUE,
	ukey			varchar UNIQUE,
	status			status_activatable NOT NULL DEFAULT 'draft',
	name			varchar NOT NULL,
	aff_id			bigint REFERENCES users(id) ON UPDATE CASCADE,
	promo_id		bigint REFERENCES products(id) ON UPDATE CASCADE ON DELETE CASCADE DEFERRABLE UNIQUE,
	product_id		bigint REFERENCES products(id) ON UPDATE CASCADE DEFERRABLE,
	init_discount	numeric(8,2) NOT NULL DEFAULT 0,
	rec_discount	numeric(8,2) NOT NULL DEFAULT 0,
	starts			datetime,
	expires			datetime,
	stock			int,
	firesale		boolean NOT NULL DEFAULT FALSE,
	created			datetime NOT NULL DEFAULT NOW(),
	modified		datetime NOT NULL DEFAULT NOW(),
	memo			text NOT NULL DEFAULT '',
	tsv				tsvector NOT NULL,
	CONSTRAINT valid_ukey
		CHECK ( ukey ~ '^[a-z0-9](?:[a-z0-9._-]*[a-z0-9])?$' AND ukey !~ '^[0-9]+$' ),
	CONSTRAINT valid_name
		CHECK ( name <> '' ),
	CONSTRAINT valid_campaign
		CHECK ( ukey IS NULL AND promo_id IS NOT NULL OR ukey IS NOT NULL AND promo_id IS NULL ),
	CONSTRAINT valid_discounts
		CHECK ( init_discount >= 0 AND rec_discount >= 0 ),
	CONSTRAINT valid_coupon
		CHECK ( promo_id IS NOT NULL OR
			product_id IS NULL AND init_discount = 0 AND rec_discount = 0 OR
			product_id IS NOT NULL AND ( status < 'future' OR init_discount > 0 OR rec_discount > 0 ) ),
	CONSTRAINT valid_promo
		CHECK ( promo_id IS NULL OR
			promo_id IS NOT DISTINCT FROM product_id AND ukey IS NULL AND aff_id IS NULL ),
	CONSTRAINT valid_activatable
		CHECK ( expires >= starts ),
	CONSTRAINT valid_stock
		CHECK ( stock >= 0 ),
	CONSTRAINT valid_firesale
		CHECK ( NOT firesale OR stock IS NOT NULL OR expires IS NOT NULL ),
	CONSTRAINT undefined_behavior
		CHECK ( NOT ( status = 'inherit' AND promo_id IS NULL ) AND
			NOT ( status = 'trash' AND promo_id IS NOT NULL ) )
);

SELECT	activatable('campaigns'),
		depletable('campaigns', 'stock'),
		sluggable('campaigns'),
		timestampable('campaigns'),
		searchable('campaigns'),
		trashable('campaigns');

CREATE INDEX campaigns_sort ON campaigns(name);
CREATE INDEX campaigns_aff_id ON campaigns(aff_id);
CREATE INDEX campaigns_product_id ON campaigns(product_id);

COMMENT ON TABLE products IS E'Stores campaigns, coupons, and promos.

Promos are tied to products through their uuid; every product has one.

- stock gets decreased as new orders are *cleared*. In other words,
  it is only loosely enforced.
- An active firesale requires either or both of expires and stock.
  A firesale applies dynamic discount to orders.';

/*
 * Active campaigns
 */
CREATE OR REPLACE VIEW active_campaigns
AS
SELECT	campaigns.*
FROM	campaigns
WHERE	status = 'active';

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
AND		( stock IS NULL OR stock > 0 )
AND		( expires IS NULL OR expires >= NOW()::datetime );

COMMENT ON VIEW active_coupons IS E'Active Coupons

- product_id is set.
- status is active.
- stock, if set, is not depleted.
- expires, if set, is not reached.';

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
AND		( stock IS NULL OR stock > 0 )
AND		( expires IS NULL OR expires >= NOW()::datetime );

COMMENT ON VIEW active_promos IS E'Active Promos

- A product is tied to the campaign through the uuid.
- status is active.
- stock, if set, is not depleted.
- expires, if set, is not reached.';

/**
 * Clean a campaign before it gets stored.
 */
CREATE OR REPLACE FUNCTION campaigns_clean()
	RETURNS trigger
AS $$
BEGIN
	-- Trim fields
	NEW.ukey := NULLIF(trim(NEW.ukey), '');
	NEW.name := NULLIF(trim(NEW.name), '');
	
	-- Default name and ukey
	NEW.name := COALESCE(NEW.name, NEW.ukey);
	NEW.ukey := COALESCE(NEW.ukey, to_slug(NEW.name));
	
	-- Handle inherit status
	IF	NEW.status = 'trash' AND NEW.promo_id IS NOT NULL
	THEN
		NEW.status := 'inherit';
	END IF;
	
	IF	NEW.product_id IS NULL
	THEN
		-- Reset coupon fields
		IF	NEW.status <> 'trash'
		THEN
			NEW.status := 'active';
		END IF;
		NEW.init_discount := 0;
		NEW.rec_discount := 0;
		NEW.starts := NULL;
		NEW.expires := NULL;
		NEW.stock := NULL;
		NEW.firesale := FALSE;
	ELSE
		-- Require a discount
		IF	NEW.status >= 'future' AND NEW.init_discount = 0 AND NEW.rec_discount = 0
		THEN
			NEW.status = 'inactive';
		END IF;
		
		-- Firesales require either or both of expires and stock
		IF	NEW.firesale AND NEW.expires IS NULL AND NEW.stock IS NULL
		THEN
			NEW.firesale := FALSE;
		END IF;
	END IF;
	
	RETURN NEW;
END;
$$ LANGUAGE plpgsql;

CREATE TRIGGER campaigns_05_clean
	BEFORE INSERT OR UPDATE ON campaigns
FOR EACH ROW EXECUTE PROCEDURE campaigns_clean();