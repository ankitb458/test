/*
 * Order lines
 */
CREATE TABLE order_lines (
	id				bigserial PRIMARY KEY,
	uuid			uuid NOT NULL DEFAULT uuid() UNIQUE,
	status			status_billable NOT NULL DEFAULT 'draft',
	name			varchar(255) NOT NULL,
	order_id		bigint NOT NULL REFERENCES orders(id) ON UPDATE CASCADE,
	user_id			bigint REFERENCES users(id) ON UPDATE CASCADE,
	product_id		bigint REFERENCES products(id) ON UPDATE CASCADE,
	coupon_id		bigint REFERENCES campaigns(id) ON UPDATE CASCADE,
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
				init_price >= init_comm AND init_price >= init_discount AND
				rec_price >= 0 AND rec_comm >= 0 AND rec_discount >= 0 AND
				rec_price >= rec_comm AND rec_price >= rec_discount )
);

SELECT	timestampable('order_lines'),
		repeatable('order_lines'),
		depletable('order_lines', 'max_orders'),
		searchable('order_lines'),
		trashable('order_lines');

CREATE INDEX order_lines_order_id ON order_lines(order_id);
CREATE INDEX order_lines_user_id ON order_lines(user_id);
CREATE INDEX order_lines_product_id ON order_lines(product_id);
CREATE INDEX order_lines_coupon_id ON order_lines(coupon_id);

COMMENT ON TABLE orders IS E'Order lines

- user_id gets shipped; orders.user_id gets billed.
- init/rec price/comm/discount are auto-filled if not provided.
- rec_count gets decremented on cleared payments.';

/**
 * Clean an order line before it gets stored.
 */
CREATE OR REPLACE FUNCTION order_lines_clean()
	RETURNS trigger
AS $$
DECLARE
	c			campaigns;
BEGIN
	NEW.name := NULLIF(trim(NEW.name, ''), '');
	
	IF	COALESCE(NEW.name, '') = ''
	THEN
		IF	NEW.product_id IS NOT NULL
		THEN
			SELECT	name
			INTO	NEW.name
			FROM	products
			WHERE	id = NEW.product_id;
		END IF;
		
		IF	NEW.name = ''
		THEN
			NEW.name := 'Unknown Product';
		END IF;
	END IF;
	
	IF	NEW.rec_interval IS NULL AND NEW.rec_count IS NOT NULL
	THEN
		NEW.rec_count := NULL;
	END IF;
	
	RETURN NEW;
END;
$$ LANGUAGE plpgsql;

CREATE TRIGGER order_lines_03_clean
	BEFORE INSERT OR UPDATE ON order_lines
FOR EACH ROW EXECUTE PROCEDURE order_lines_clean();

/**
 * Autofills product/comm/discount when inserting new order lines
 */
CREATE OR REPLACE FUNCTION order_lines_autofill()
	RETURNS trigger
AS $$
DECLARE
	o			record;
	p			record;
	c			record;
	t_ratio		numeric := 1;
	cur_orders	float8;
	o_ratio		numeric := 1;
BEGIN
	IF	NEW.init_price IS NULL OR NEW.init_comm IS NULL OR
		NEW.rec_price IS NULL OR NEW.rec_comm IS NULL
	THEN
		IF	NEW.product_id IS NOT NULL
		THEN
			SELECT	init_price,
					init_comm,
					rec_price,
					rec_comm
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
	
	IF	NEW.init_discount IS NULL OR NEW.rec_discount IS NULL
	THEN
		SELECT	aff_id,
				campaign_id
		INTO	o
		FROM	orders
		WHERE	id = NEW.order_id;
		
		IF	NEW.coupon_id IS NOT NULL
		THEN
			-- Validate coupon
			SELECT	id,
					aff_id,
					init_discount,
					rec_discount,
					firesale,
					min_date,
					max_date,
					max_orders
			INTO	c
			FROM	active_coupons
			WHERE	id = NEW.coupon_id
			AND		product_id = NEW.product_id;
			
			IF	NOT FOUND
			THEN
				NEW.coupon_id := NULL;
			ELSEIF c.aff_id IS NOT NULL AND o.aff_id IS DISTINCT FROM c.aff_id -- inconsistent sponsor
			THEN
				NEW.coupon_id := NULL;
			END IF;
		ELSE
			-- Autofetch coupon
			SELECT	id,
					aff_id,
					init_discount,
					rec_discount,
					firesale,
					min_date,
					max_date,
					max_orders
			INTO	c
			FROM	active_promos;
			
			IF	FOUND
			THEN
				NEW.coupon_id := c.id;
			END IF;
		END IF;
		
		-- Fetch discount
		IF	c.id IS NULL
		THEN
			NEW.init_discount := COALESCE(NEW.init_discount, 0);
			NEW.rec_discount := COALESCE(NEW.rec_discount, 0);
		ELSE
			-- Process firesale if applicable
			IF	c.firesale
			THEN
				IF	c.max_date IS NOT NULL -- max_date < NOW() is guaranteed by active_promos
				THEN
					t_ratio := EXTRACT(EPOCH FROM c.max_date - NOW()::datetime) /
						EXTRACT(EPOCH FROM c.max_date - c.min_date);
				END IF;
				
				IF	c.max_orders IS NOT NULL -- max_orders > 0 is guaranteed by active_promos
				THEN
					SELECT	SUM(order_lines.quantity)
					INTO	cur_orders
					FROM	order_lines
					JOIN	orders
					ON		orders.id = order_lines.order_id
					WHERE	order_lines.order_id <> NEW.order_id
					AND		order_lines.coupon_id = NEW.coupon_id
					AND		order_lines.status > 'pending'
					AND		orders.order_date >= c.min_date;
					
					o_ratio := c.max_orders / ( COALESCE(cur_orders, 0) + c.max_orders );
				END IF;
				
				c.init_discount := round(c.init_discount * t_ratio * o_ratio, 2);
				c.rec_discount := round(c.rec_discount * t_ratio * o_ratio, 2);
			END IF;
			
			-- Strip discount from commission where applicable
			IF	o.campaign_id = c.id AND o.aff_id IS NOT NULL
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
END;
$$ LANGUAGE plpgsql;

CREATE TRIGGER order_lines_05_autofill
	BEFORE INSERT ON order_lines
FOR EACH ROW EXECUTE PROCEDURE order_lines_autofill();