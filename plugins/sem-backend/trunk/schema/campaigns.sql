/**
 * Campaigns/Coupons table.
 */
CREATE TABLE campaigns (
	id				bigserial PRIMARY KEY,
	uuid			uuid NOT NULL DEFAULT uuid() UNIQUE,
	ukey			varchar(255) UNIQUE,
	status			status_activatable NOT NULL DEFAULT 'draft',
	name			varchar(255) NOT NULL DEFAULT '',
	aff_id			bigint,
	product_id		bigint REFERENCES products (id),
	init_discount	numeric(8,2) NOT NULL DEFAULT 0,
	rec_discount	numeric(8,2) NOT NULL DEFAULT 0,
	min_date		timestamp(0) with time zone,
	max_date		timestamp(0) with time zone,
	max_orders		int,
	firesale		boolean NOT NULL DEFAULT FALSE,
	memo			text NOT NULL DEFAULT '',
	CONSTRAINT valid_discount
		CHECK ( init_discount >= 0 AND rec_discount >= 0 )
);

SELECT sluggable('campaigns'), timestampable('campaigns'), searchable('campaigns');

CREATE INDEX campaigns_status_sort ON campaigns (name)
WHERE	status = 'active';

/**
 * Clean a campaign before it gets stored.
 */
CREATE OR REPLACE FUNCTION campaigns_clean()
	RETURNS trigger
AS $$
BEGIN
	NEW.name := trim(NEW.name);
	
	IF NEW.product_id IS NULL
	THEN
		-- Dump all coupon fields and exit
		NEW.status := 'active';
		NEW.init_discount := 0;
		NEW.rec_discount := 0;
		NEW.min_date := NULL;
		NEW.max_date := NULL;
		NEW.max_orders := NULL;
		NEW.firesale := FALSE;
	ELSE
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
	END IF;
	
	RETURN NEW;
END $$ LANGUAGE plpgsql;

CREATE TRIGGER campaigns_0_clean
	BEFORE INSERT OR UPDATE ON campaigns
FOR EACH ROW EXECUTE PROCEDURE campaigns_clean();