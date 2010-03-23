/*
 * Orders table.
 */
CREATE TABLE orders (
	id				bigserial PRIMARY KEY,
	uuid			uuid NOT NULL DEFAULT uuid() UNIQUE,
	status			status_billable NOT NULL DEFAULT 'draft',
	name			varchar(255) NOT NULL DEFAULT '',
	init_price		numeric(8,2) NOT NULL DEFAULT 0,
	init_comm		numeric(8,2) NOT NULL DEFAULT 0,
	init_discount	numeric(8,2) NOT NULL DEFAULT 0,
	rec_price		numeric(8,2) NOT NULL DEFAULT 0,
	rec_comm		numeric(8,2) NOT NULL DEFAULT 0,
	rec_discount	numeric(8,2) NOT NULL DEFAULT 0,
	rec_interval	interval,
	rec_count		smallint,
	order_date		timestamp(0) with time zone NOT NULL DEFAULT NOW(),
	user_id			bigint REFERENCES users(id),
	billing_id		bigint REFERENCES users(id),
	aff_id			bigint REFERENCES users(id),
	product_id		bigint REFERENCES products(id),
	campaign_id		bigint REFERENCES campaigns(id),
	coupon_id		bigint REFERENCES campaigns(id),
	memo			text NOT NULL DEFAULT '',
	CONSTRAINT valid_price
		CHECK ( init_price >= 0 AND init_comm >= 0 AND init_discount >= 0 AND
				init_price >= init_comm AND init_price >= init_discount AND
				rec_price >= 0 AND rec_comm >= 0 AND rec_discount >= 0 AND
				rec_price >= rec_comm AND rec_price >= rec_discount ),
	CONSTRAINT valid_interval
		CHECK ( rec_interval IS NULL AND rec_count IS NULL OR
			rec_interval >= '0' AND ( rec_count IS NULL OR rec_count >= 0 ) ),
	CONSTRAINT valid_coupon_flow
		CHECK ( NOT( campaign_id IS NULL AND coupon_id IS NOT NULL ) )
);

SELECT timestampable('orders'), searchable('orders');

CREATE INDEX orders_sort ON orders(order_date DESC);

/**
 * Clean an order before it gets stored.
 */
CREATE OR REPLACE FUNCTION orders_clean()
	RETURNS trigger
AS $$
BEGIN
	NEW.name := trim(NEW.name);
	
	IF NEW.status = 'cleared' AND NEW.rec_interval IS NULL AND NEW.rec_count IS NOT NULL
	THEN
		NEW.rec_count := NULL;
	END IF;
	
	RETURN NEW;
END $$ LANGUAGE plpgsql;

CREATE TRIGGER orders_0_clean
	BEFORE INSERT OR UPDATE ON orders
FOR EACH ROW EXECUTE PROCEDURE orders_clean();