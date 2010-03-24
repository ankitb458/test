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
	CONSTRAINT valid_discount
		CHECK ( init_discount >= 0 AND rec_discount >= 0 ),
	CONSTRAINT valid_order_flow
		CHECK ( ( max_orders IS NULL OR max_orders >= 0 ) AND
			( min_date IS NULL OR max_date IS NULL OR
			max_date IS NOT NULL AND min_date <= max_date ) )
);

SELECT sluggable('campaigns'), timestampable('campaigns'), searchable('campaigns');

CREATE INDEX campaigns_sort ON campaigns (name);

COMMENT ON TABLE products IS E'Stores campaigns, coupons, and promos.

Promos are tied to products through their uuid; every product has one.

- max_orders gets decreased as new orders are *cleared*. In other words,
  it is only loosely enforced.
- An active firesale requires either or both of max_date and max_orders.
  A firesale applies dynamic discount to orders.';

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
		IF NEW.status >= 'future' AND NEW.init_discount = 0 AND NEW.rec_discount = 0
		AND NOT EXISTS (
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
			IF ( ROW(NEW.status, NEW.init_discount, NEW.rec_discount, NEW.firesale) <>
				ROW(OLD.status, OLD.init_discount, OLD.rec_discount, OLD.firesale) OR
				NEW.firesale AND ROW(NEW.max_date, NEW.max_orders) IS DISTINCT FROM
				ROW(OLD.max_date, OLD.max_orders) )
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

/**
 * Auto-creates a promo for new products.
 */
CREATE OR REPLACE FUNCTION products_autocreate_promo()
	RETURNS trigger
AS $$
BEGIN
	INSERT INTO campaigns (
		uuid,
		status,
		name,
		product_id
		)
	SELECT	NEW.uuid,
			CASE
			WHEN NEW.status = 'draft'
			THEN 'draft'
			ELSE 'inactive'
			END::status_activatable,
			'Promo on ' || NEW.name,
			NEW.id;
	
	RETURN NEW;
END $$ LANGUAGE plpgsql;

CREATE TRIGGER products_10_autocreate_promo
	AFTER INSERT ON products
FOR EACH ROW EXECUTE PROCEDURE products_autocreate_promo();

/**
 * Refreshes coupon discounts on product updates.
 */
CREATE OR REPLACE FUNCTION products_refresh_coupons()
	RETURNS trigger
AS $$
BEGIN
	IF NEW.init_price = OLD.init_price AND NEW.rec_price = OLD.rec_price
	THEN
		RETURN NEW;
	END IF;
	
	UPDATE	campaigns
	SET		status = CASE
			WHEN aff_id IS NOT NULL AND status IN ('active', 'future')
			THEN 'pending'
			ELSE status
			END,
			init_discount = CASE
			-- Zero in, when possible
			WHEN init_discount = 0 OR NEW.init_price = 0 OR aff_id IS NOT NULL AND NEW.init_comm = 0
			THEN 0
			-- Keep common comm ratios
			WHEN init_discount = round(OLD.init_comm / 2, 2)
			THEN round(NEW.init_comm / 2, 2)
			-- Keep affiliate comm ratios for affiliate coupons
			WHEN aff_id IS NOT NULL
			THEN round(init_discount * NEW.init_comm / OLD.init_comm, 2)
			-- Keep discount ratios for site coupons
			ELSE round(init_discount * NEW.init_price / OLD.init_price, 2)
			END,
			rec_discount = CASE
			-- Zero in, when possible
			WHEN rec_discount = 0 OR NEW.rec_price = 0 OR aff_id IS NOT NULL AND NEW.rec_comm = 0
			THEN 0
			-- Keep common comm ratios
			WHEN rec_discount = round(OLD.rec_comm / 2, 2)
			THEN round(NEW.rec_comm / 2, 2)
			-- Keep affiliate comm ratios for affiliate coupons
			WHEN aff_id IS NOT NULL
			THEN round(rec_discount * NEW.rec_comm / OLD.rec_comm, 2)
			-- Keep discount ratios for site coupons
			ELSE round(rec_discount * NEW.rec_price / OLD.rec_price, 2)
			END
	WHERE	product_id = NEW.id
	AND		( -- Always update on price changes
			NEW.init_price <> OLD.init_price OR NEW.rec_price <> OLD.rec_price
			-- Conditionally update on commission changes
			OR aff_id IS NOT NULL
			AND ( NEW.init_comm <> OLD.init_comm OR NEW.rec_comm <> OLD.rec_comm ) );
	
	RETURN NEW;
END $$ LANGUAGE plpgsql;

CREATE TRIGGER products_10_refresh_coupons
	AFTER UPDATE ON products
FOR EACH ROW EXECUTE PROCEDURE products_refresh_coupons();