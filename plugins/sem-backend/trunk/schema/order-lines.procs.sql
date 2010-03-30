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

CREATE TRIGGER order_lines_03_autofill
	BEFORE INSERT ON order_lines
FOR EACH ROW EXECUTE PROCEDURE order_lines_autofill();