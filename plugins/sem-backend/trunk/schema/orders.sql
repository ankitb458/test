/*
 * Orders
 */
CREATE TABLE orders (
	id				bigserial PRIMARY KEY,
	uuid			uuid NOT NULL DEFAULT uuid() UNIQUE,
	status			status_billable NOT NULL DEFAULT 'draft',
	name			varchar(255) NOT NULL DEFAULT '',
	order_date		timestamp(0) with time zone,
	user_id			bigint REFERENCES users(id),
	aff_id			bigint REFERENCES users(id),
	campaign_id		bigint REFERENCES campaigns(id),
	memo			text NOT NULL DEFAULT '',
	CONSTRAINT valid_order_flow
		CHECK ( NOT ( order_date IS NULL AND status > 'draft' ) )
);

SELECT timestampable('orders'), searchable('orders');

CREATE INDEX orders_sort ON orders(order_date DESC);
CREATE INDEX orders_user_id ON orders(user_id);
CREATE INDEX orders_aff_id ON orders(aff_id);
CREATE INDEX orders_campaign_id ON orders(campaign_id);

COMMENT ON TABLE orders IS E'Stores orders.

- user_id gets billed; order_lines.user_id gets shipped.
- aff_id gets the commission and is tied to the campaign_id. It gets stored
  for reference, in case a campaign''s owner changes.
- coupon_id, when present, is typically the same as the campaign_id. A system-
  wide promo on a product may make the two different, however.';

/*
 * Order lines
 */
CREATE TABLE order_lines (
	id				bigserial PRIMARY KEY,
	uuid			uuid NOT NULL DEFAULT uuid() UNIQUE,
	status			status_billable NOT NULL DEFAULT 'draft',
	name			varchar(255) NOT NULL DEFAULT '',
	order_id		bigint NOT NULL REFERENCES orders(id),
	user_id			bigint REFERENCES users(id),
	product_id		bigint REFERENCES products(id),
	coupon_id		bigint REFERENCES campaigns(id),
	quantity		smallint NOT NULL DEFAULT 1,
	init_price		numeric(8,2) NOT NULL,
	init_comm		numeric(8,2) NOT NULL,
	init_discount	numeric(8,2) NOT NULL,
	rec_price		numeric(8,2) NOT NULL,
	rec_comm		numeric(8,2) NOT NULL,
	rec_discount	numeric(8,2) NOT NULL,
	rec_interval	interval,
	rec_count		smallint,
	CONSTRAINT valid_price
		CHECK ( init_price >= 0 AND init_comm >= 0 AND init_discount >= 0 AND
				init_price >= init_comm + init_discount AND
				rec_price >= 0 AND rec_comm >= 0 AND rec_discount >= 0 AND
				rec_price >= rec_comm + rec_discount ),
	CONSTRAINT valid_interval
		CHECK ( rec_interval IS NULL AND rec_count IS NULL OR
			rec_interval >= '0' AND ( rec_count IS NULL OR rec_count >= 0 ) )
);

CREATE INDEX order_lines_order_id ON order_lines(order_id);
CREATE INDEX order_lines_user_id ON order_lines(user_id);
CREATE INDEX order_lines_product_id ON order_lines(product_id);
CREATE INDEX order_lines_coupon_id ON order_lines(coupon_id);

COMMENT ON TABLE orders IS E'Stores order lines.

- user_id gets shipped; orders.user_id gets billed.
- init/rec price/comm/discount are auto-filled if not provided.
- rec_count gets decremented on cleared payments.';


/**
 * Clean an order before it gets stored.
 */
CREATE OR REPLACE FUNCTION orders_clean()
	RETURNS trigger
AS $$
BEGIN
	NEW.name := trim(NEW.name);
	
	IF COALESCE(NEW.name, '') = ''
	THEN
		IF NEW.user_id IS NOT NULL
		THEN
			SELECT	name
			INTO	NEW.name
			FROM	users
			WHERE	id = NEW.user_id;
		END IF;
		
		IF NEW.name = ''
		THEN
			NEW.name := 'Anonymous User';
		END IF;
	END IF;
	
	IF NEW.order_date IS NULL AND NEW.status > 'draft'
	THEN
		NEW.order_date := NOW();
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
	a_id		bigint;
BEGIN
	NEW.name := trim(NEW.name);
	
	IF COALESCE(NEW.name, '') = ''
	THEN
		IF NEW.product_id IS NOT NULL
		THEN
			SELECT	name
			INTO	NEW.name
			FROM	products
			WHERE	id = NEW.product_id;
		END IF;
		
		IF NEW.name = ''
		THEN
			NEW.name := 'Unknown Product';
		END IF;
	END IF;
	
	IF NEW.rec_interval IS NULL AND NEW.rec_count IS NOT NULL
	THEN
		NEW.rec_count := NULL;
	END IF;
	
	IF NEW.order_id IS NULL AND NEW.coupon_id IS NULL AND NEW.product_id IS NOT NULL
	THEN
		-- Auto-fetch coupon
		SELECT	campaigns.id
		INTO	NEW.coupon_id
		FROM	campaigns
		JOIN	products
		ON		products.uuid = campaigns.uuid
		WHERE	campaigns.product_id = NEW.product_id
		AND		( campaigns.max_date IS NULL OR campaigns.max_date >= NOW() )
		AND		( campaigns.max_orders IS NULL OR campaigns.max_orders > 0 );
	END IF;
	
	IF NEW.order_id IS NULL
	THEN
		SELECT	aff_id
		INTO	a_id
		FROM	campaigns
		WHERE	id = NEW.coupon_id;
		INSERT INTO orders (user_id, aff_id, campaign_id)
		VALUES	(NEW.user_id, a_id, NEW.coupon_id)
		RETURNING id INTO NEW.order_id;
	END IF;
	
	RETURN NEW;
END $$ LANGUAGE plpgsql;

CREATE TRIGGER order_lines_0_clean
	BEFORE INSERT OR UPDATE ON order_lines
FOR EACH ROW EXECUTE PROCEDURE order_lines_clean();

/**
 * Autofills product/comm/discount when inserting new order lines
 */
CREATE OR REPLACE FUNCTION order_lines_autofill()
	RETURNS trigger
AS $$
DECLARE
	o			orders;
	p			products;
	c			campaigns;
	t_ratio		float := 1;
	o_ratio		float := 1;
BEGIN
	IF NEW.init_price IS NULL OR NEW.init_comm IS NULL OR
	   NEW.rec_price IS NULL OR NEW.rec_comm IS NULL
	THEN
		IF NEW.product_id IS NOT NULL
		THEN
			SELECT	products.*
			INTO	p
			FROM	products
			WHERE	id = NEW.product_id;
		END IF;	
	
		-- Fetch price/comm
		NEW.init_price := COALESCE(NEW.init_price, p.init_price, 0);
		NEW.rec_price := COALESCE(NEW.rec_price, p.rec_price, 0);
		NEW.init_comm := COALESCE(NEW.init_comm, p.init_comm, 0);
		NEW.rec_comm := COALESCE(NEW.rec_comm, p.rec_comm, 0);
	END IF;
	
	IF NEW.init_discount IS NULL OR NEW.rec_discount IS NULL
	THEN
		SELECT	orders.*
		INTO	o
		FROM	orders
		WHERE	id = NEW.order_id;
		
		IF NEW.coupon_id IS NOT NULL
		THEN
			SELECT	campaigns.*
			INTO	c
			FROM	campaigns
			WHERE	id = NEW.coupon_id;
		END IF;
		
		-- Fetch discount
		IF NEW.coupon_id IS NULL OR c.max_orders = 0 OR c.max_date > NOW()
		THEN
			NEW.init_discount := COALESCE(NEW.init_discount, 0);
			NEW.rec_discount := COALESCE(NEW.rec_discount, 0);
		ELSE
			-- Process firesale if applicable
			IF c.firesale
			THEN
				IF c.max_date IS NOT NULL AND c.max_date <= now()
				THEN
					t_ratio := EXTRACT(EPOCH FROM NOW() - NEW.min_date) /
						EXTRACT(EPOCH FROM NEW.max_date - NEW.min_date);
					RAISE NOTICE '%', t_ratio;
					NULL;
				END IF;
			END IF;

			-- Strip commission from discount if applicable
			IF o.campaign_id = NEW.coupon_id AND o.aff_id IS NOT NULL
			THEN
				NEW.init_comm := GREATEST(NEW.init_comm - c.init_discount, 0);
				NEW.rec_comm := GREATEST(NEW.rec_comm - c.rec_discount, 0);
			END IF;

			-- Apply discount
			NEW.init_discount := COALESCE(NEW.init_discount, c.init_discount, 0);
			NEW.rec_discount := COALESCE(NEW.rec_discount, c.rec_discount, 0);
		END IF;
	END IF;
	
	RETURN NEW;
END $$ LANGUAGE plpgsql;

CREATE TRIGGER order_lines_1_autofill
	BEFORE INSERT ON order_lines
FOR EACH ROW EXECUTE PROCEDURE order_lines_autofill();