/*
 * Orders table.
 */
CREATE TABLE orders (
	id				bigserial PRIMARY KEY,
	uuid			uuid NOT NULL DEFAULT uuid() UNIQUE,
	status			status_billable NOT NULL DEFAULT 'draft',
	name			varchar(255) NOT NULL DEFAULT '',
	order_date		timestamp(0) with time zone NOT NULL DEFAULT NOW(),
	user_id			bigint REFERENCES users(id),
	billing_id		bigint REFERENCES users(id),
	aff_id			bigint REFERENCES users(id),
	campaign_id		bigint REFERENCES campaigns(id),
	coupon_id		bigint REFERENCES campaigns(id),
	memo			text NOT NULL DEFAULT '',
	CONSTRAINT valid_coupon_flow
		CHECK ( NOT( campaign_id IS NULL AND coupon_id IS NOT NULL ) )
);

SELECT timestampable('orders'), searchable('orders');

CREATE INDEX orders_sort ON orders(order_date DESC);

/**
 * Order lines.
 */
CREATE TABLE order_lines (
	line_id			bigserial PRIMARY KEY,
	order_id		bigint NOT NULL REFERENCES orders(id) ON DELETE CASCADE,
	name			varchar(255) NOT NULL DEFAULT '',
	product_id		bigint REFERENCES products(id) ON DELETE SET NULL,
	quantity		smallint NOT NULL DEFAULT 1,
	init_price		numeric(8,2) NOT NULL DEFAULT 0,
	init_comm		numeric(8,2) NOT NULL DEFAULT 0,
	init_discount	numeric(8,2) NOT NULL DEFAULT 0,
	rec_price		numeric(8,2) NOT NULL DEFAULT 0,
	rec_comm		numeric(8,2) NOT NULL DEFAULT 0,
	rec_discount	numeric(8,2) NOT NULL DEFAULT 0,
	rec_interval	interval,
	rec_count		smallint,
	UNIQUE (order_id, product_id),
	CONSTRAINT valid_price
		CHECK ( init_price >= 0 AND init_comm >= 0 AND init_discount >= 0 AND
				init_price >= init_comm AND init_price >= init_discount AND
				rec_price >= 0 AND rec_comm >= 0 AND rec_discount >= 0 AND
				rec_price >= rec_comm AND rec_price >= rec_discount ),
	CONSTRAINT valid_interval
		CHECK ( rec_interval IS NULL AND rec_count IS NULL OR
			rec_interval >= '0' AND ( rec_count IS NULL OR rec_count >= 0 ) )
);

/**
 * Clean an order before it gets stored.
 */
CREATE OR REPLACE FUNCTION orders_clean()
	RETURNS trigger
AS $$
DECLARE
	c		campaigns;
BEGIN
	NEW.name := trim(NEW.name);
	
	IF TG_OP = 'INSERT'
	THEN
		SELECT	campaigns.*
		INTO	c
		FROM	campaigns
		WHERE	uuid = NEW.uuid;
		
		-- If the product features an active promo, use it
		IF NEW.coupon_id IS NULL AND
		c.status = 'active' AND
		( c.init_discount <> 0 OR c.rec_discount <> 0 ) AND
		( c.max_date IS NULL OR c.max_date <= NOW() ) AND
		( c.max_orders IS NULL OR c.max_orders > 0 )
		THEN
			NEW.coupon_id := c.id;
		END IF;
	END IF;
	
	RETURN NEW;
END $$ LANGUAGE plpgsql;

CREATE TRIGGER orders_0_clean
	BEFORE INSERT OR UPDATE ON orders
FOR EACH ROW EXECUTE PROCEDURE orders_clean();

/**
 * Clean an order line before it gets stored.
 */
CREATE OR REPLACE FUNCTION order_lines_clean()
	RETURNS trigger
AS $$
DECLARE
	o		orders;
	p		products;
	c		campaigns;
BEGIN
	NEW.name := trim(NEW.name);
	
	IF NEW.rec_interval IS NULL AND NEW.rec_count IS NOT NULL
	THEN
		NEW.rec_count := NULL;
	END IF;
	
	IF TG_OP = 'INSERT'
	THEN
		SELECT	orders.*
		INTO	o
		FROM	orders
		WHERE	id = NEW.order_id;
		
		IF NEW.product_id
		THEN
			SELECT	products.*
			INTO	p
			FROM	products
			WHERE	product_id = NEW.product_id;
		END IF;
		
		SELECT	campaigns.*
		INTO	c
		FROM	campaigns
		WHERE	id = o.coupon_id
		AND		campaigns.product_id = NEW.product_id;
		
		-- Enforce price/comm/discount consistency
		NEW.init_price := COALESCE(p.init_price, NEW.init_price);
		NEW.rec_price := COALESCE(p.rec_price, NEW.rec_price);
		NEW.init_comm := LEAST(COALESCE(p.init_comm, NEW.init_comm), NEW.init_comm);
		NEW.rec_comm := LEAST(COALESCE(p.rec_comm, NEW.rec_comm), NEW.rec_comm);
		IF c.aff_id IS NULL
		THEN
			NEW.init_discount := LEAST(NEW.init_price, NEW.init_discount);
			NEW.rec_discount := LEAST(NEW.rec_price, NEW.rec_discount);
		ELSE
			NEW.init_discount := LEAST(NEW.init_comm, NEW.init_discount);
			NEW.rec_discount := LEAST(NEW.rec_comm, NEW.rec_discount);
		END IF;
		
		-- Process firesales
		IF c.firesale
		THEN
			NULL;
		END IF;
	END IF;
	
	RETURN NEW;
END $$ LANGUAGE plpgsql;

CREATE TRIGGER order_lines_0_clean
	BEFORE INSERT OR UPDATE ON order_lines
FOR EACH ROW EXECUTE PROCEDURE order_lines_clean();