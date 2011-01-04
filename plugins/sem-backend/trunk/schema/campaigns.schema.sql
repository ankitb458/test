/**
 * Campaigns/Coupons
 */
CREATE TABLE campaigns (
	id				bigserial PRIMARY KEY,
	uuid			uuid NOT NULL DEFAULT uuid() UNIQUE,
	ukey			slug UNIQUE,
	status			status_activatable NOT NULL DEFAULT 'draft',
	name			varchar NOT NULL,
	aff_id			bigint REFERENCES users(id),
	promo_id		bigint REFERENCES products(id) ON DELETE CASCADE DEFERRABLE UNIQUE,
	product_id		bigint REFERENCES products(id) DEFERRABLE,
	init_discount	numeric(8,2) NOT NULL DEFAULT 0,
	rec_discount	numeric(8,2) NOT NULL DEFAULT 0,
	launch_date		datetime,
	expire_date		datetime,
	stock			int,
	firesale		boolean NOT NULL DEFAULT FALSE,
	created_date	datetime NOT NULL DEFAULT NOW(),
	modified_date	datetime NOT NULL DEFAULT NOW(),
	memo			text NOT NULL DEFAULT '',
--	tsv				tsvector NOT NULL,
	CONSTRAINT valid_name
		CHECK ( name <> '' AND name = trim(name) ),
	CONSTRAINT valid_campaign
		CHECK ( ukey IS NULL AND promo_id IS NOT NULL OR ukey IS NOT NULL AND promo_id IS NULL ),
	CONSTRAINT valid_amounts
		CHECK ( init_discount >= 0 AND rec_discount >= 0 ),
	CONSTRAINT valid_coupon
		CHECK ( promo_id IS NOT NULL OR
			product_id IS NULL AND init_discount = 0 AND rec_discount = 0 OR
			product_id IS NOT NULL AND ( status < 'future' OR init_discount > 0 OR rec_discount > 0 ) ),
	CONSTRAINT valid_promo
		CHECK ( promo_id IS NULL OR
			promo_id IS NOT DISTINCT FROM product_id AND ukey IS NULL AND aff_id IS NULL ),
	CONSTRAINT valid_launch_date
		CHECK ( expire_date >= launch_date ),
	CONSTRAINT valid_stock
		CHECK ( stock >= 0 ),
	CONSTRAINT valid_firesale
		CHECK ( NOT firesale OR stock IS NOT NULL OR expire_date IS NOT NULL )
);

SELECT	activatable('campaigns', 'launch_date'),
		depletable('campaigns', 'stock'),
		sluggable('campaigns'),
		timestampable('campaigns'),
--		searchable('campaigns'),
		trashable('campaigns');

CREATE INDEX campaigns_sort ON campaigns(name);
CREATE INDEX campaigns_aff_id ON campaigns(aff_id);
CREATE INDEX campaigns_product_id ON campaigns(product_id);

COMMENT ON TABLE campaigns IS E'Campaigns, coupons, and promos.

Promos are tied to products through their uuid; every product has one.

- ukey cannot be null unless the campaign is a promo.
- stock gets decreased as new orders are *cleared*. In other words,
  it is only loosely enforced.
- An active firesale requires either or both of expire_date and stock.
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
AND		( expire_date IS NULL OR expire_date >= NOW()::datetime );

COMMENT ON VIEW active_coupons IS E'Active Coupons

- product_id is set.
- status is active.
- stock, if set, is not depleted.
- expire_date, if set, is not reached.';

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
AND		( expire_date IS NULL OR expire_date >= NOW()::datetime );

COMMENT ON VIEW active_promos IS E'Active Promos

- A product is tied to the campaign through the uuid.
- status is active.
- stock, if set, is not depleted.
- expire_date, if set, is not reached.';

/**
 * Process read-only fields
 */
CREATE OR REPLACE FUNCTION campaigns_readonly()
	RETURNS trigger
AS $$
BEGIN
	IF	ROW(NEW.id, NEW.promo_id) IS DISTINCT FROM ROW(OLD.id, OLD.promo_id)
	THEN
		RAISE EXCEPTION 'Can''t edit readonly field in campaigns.id = %', NEW.id;
	END IF;
	
	RETURN NEW;
END $$ LANGUAGE plpgsql;

CREATE CONSTRAINT TRIGGER campaigns_01_readonly
	AFTER UPDATE ON campaigns
FOR EACH ROW EXECUTE PROCEDURE campaigns_readonly();