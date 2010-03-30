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
	cur_orders	float;
	o_ratio		numeric := 1;
BEGIN
	IF	NEW.init_price IS NULL OR NEW.init_comm IS NULL OR
		NEW.rec_price IS NULL OR NEW.rec_comm IS NULL
	THEN
		IF	NEW.product_id IS NOT NULL
		THEN
			SELECT	COALESCE(NEW.init_price, init_price),
					COALESCE(NEW.init_comm, init_comm),
					COALESCE(NEW.rec_price, rec_price),
					COALESCE(NEW.rec_comm, rec_comm)
			INTO	NEW.init_price,
					NEW.init_comm,
					NEW.rec_price,
					NEW.rec_comm
			FROM	products
			WHERE	id = NEW.product_id;
		ELSE
			NEW.init_price := COALESCE(NEW.init_price, 0);
			NEW.rec_price := COALESCE(NEW.rec_price, 0);
			NEW.init_comm := COALESCE(NEW.init_comm, 0);
			NEW.rec_comm := COALESCE(NEW.rec_comm, 0);
		END IF;	
	END IF;
	
	IF	NEW.init_discount IS NULL OR NEW.rec_discount IS NULL
	THEN
		IF	NEW.order_id IS NOT NULL
		THEN
			SELECT	campaign_id,
					aff_id
			INTO	o
			FROM	orders
			WHERE	id = NEW.order_id;
		ELSEIF NEW.product_id IS NOT NULL
		THEN
			-- Auto-create order using the product_id
			INSERT INTO orders(
					status,
					user_id,
					campaign_id,
					aff_id
					)
			SELECT	NEW.status,
					NEW.user_id,
					COALESCE(NEW.coupon_id, promo.id),
					campaign.aff_id
			FROM	active_promos as promo
			FULL JOIN campaigns as campaign
			ON		promo.product_id = NEW.product_id
			WHERE	campaign.id = NEW.coupon_id
			RETURNING id
			INTO	NEW.order_id;
		ELSEIF NEW.coupon_id IS NOT NULL
		THEN
			-- Auto-create order using the coupon_id
			INSERT INTO orders(
					status,
					user_id,
					campaign_id,
					aff_id
					)
			SELECT	NEW.status,
					NEW.user_id,
					campaign.aff_id,
					NEW.coupon_id
			FROM	campaigns as campaign
			WHERE	campaign.id = NEW.coupon_id
			RETURNING id
			INTO	NEW.order_id;
		ELSE
			-- Auto-create order
			INSERT INTO orders (
					status,
					user_id
					)
			VALUES (
					NEW.status,
					NEW.user_id
					)
			RETURNING id
			INTO	NEW.order_id;
		END IF;
		
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
		ELSEIF NEW.product_id IS NOT NULL
		THEN
			-- Autofetch promo
			SELECT	id,
					aff_id,
					init_discount,
					rec_discount,
					firesale,
					min_date,
					max_date,
					max_orders
			INTO	c
			FROM	active_promos
			WHERE	product_id = NEW.product_id;
			
			IF	NOT FOUND
			THEN
				NEW.coupon_id := NULL;
			END IF;
		ELSE
			NEW.coupon_id := NULL;
		END IF;
		
		-- Fetch discount
		IF	NEW.coupon_id IS NULL
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

/**
 * Forward status changes to orders
 */
CREATE OR REPLACE FUNCTION order_lines_delegate_status()
	RETURNS trigger
AS $$
BEGIN
	IF TG_TABLE_NAME <> 'order_lines' -- Trust triggers
	THEN
		RETURN NEW;
	ELSEIF TG_OP = 'UPDATE'
	THEN
		IF	ROW(NEW.status, NEW.order_id) = ROW(OLD.status, OLD.order_id)
		THEN
			RETURN NEW;
		ELSEIF NEW.order_id <> OLD.order_id
		THEN
			-- Also do this for the old order
			UPDATE	orders
			SET		status = COALESCE((
					SELECT	MAX(order_lines.status)
					FROM	order_lines
					WHERE	order_id = OLD.order_id
					), 'trash')
			WHERE	orders.id = OLD.order_id;
		END IF;
	END IF;

	UPDATE	orders
	SET		status = (
			SELECT	MAX(order_lines.status)
			FROM	order_lines
			WHERE	order_id = NEW.order_id
			)
	WHERE	orders.id = NEW.order_id;
	
	RETURN NEW;
END $$ LANGUAGE plpgsql;

CREATE TRIGGER order_lines_10_delegate_status
	AFTER INSERT OR UPDATE ON order_lines
FOR EACH ROW EXECUTE PROCEDURE order_lines_delegate_status();