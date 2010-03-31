/**
 * Sanitizes an order_line's shipping user.
 */
CREATE OR REPLACE FUNCTION order_lines_sanitize_user_id()
	RETURNS trigger
AS $$
DECLARE
	u_id		bigint;
BEGIN
	IF	NEW.user_id IS NULL
	THEN
		RETURN NEW;
	ELSEIF TG_OP = 'UPDATE'
	THEN
		IF	NEW.user_id IS NOT DISTINCT FROM OLD.user_id
		THEN
			RETURN NEW;
		END IF;
	END IF;
	
	IF	NOT EXISTS(
		SELECT	1
		FROM	users
		WHERE	id = NEW.user_id
		AND		status > 'pending'
		)
	THEN
		RAISE EXCEPTION 'Cannot tie inactive users.id = % to order_lines.id = %.',
			NEW.user_id, NEW.id;
	END IF;
	
	RETURN NEW;
END $$ LANGUAGE plpgsql;

CREATE TRIGGER order_lines_02_sanitize_user_id
	BEFORE INSERT OR UPDATE ON order_lines
FOR EACH ROW EXECUTE PROCEDURE order_lines_sanitize_user_id();

/**
 * Sanitizes an order line's product.
 */
CREATE OR REPLACE FUNCTION order_lines_sanitize_product()
	RETURNS trigger
AS $$
DECLARE
	p		record;
BEGIN
	IF	NEW.product_id IS NOT NULL
	THEN
		SELECT	init_price,
				init_comm,
				rec_price,
				rec_comm
		INTO	p
		FROM	products
		WHERE	status > 'draft';
		
		IF	NOT FOUND
		THEN
			RAISE EXCEPTION 'Cannot tie inactive products.id = % to order_lines.id = %',
				NEW.product_id, NEW.id;
		END IF;
		
		NEW.init_price := COALESCE(NEW.init_price, p.init_price);
		NEW.init_comm := COALESCE(NEW.init_comm, p.init_comm);
		NEW.rec_price := COALESCE(NEW.rec_price, p.rec_price);
		NEW.rec_comm := COALESCE(NEW.rec_comm, p.rec_comm);
	ELSE
		NEW.init_price := COALESCE(NEW.init_price, 0);
		NEW.rec_price := COALESCE(NEW.rec_price, 0);
		NEW.init_comm := COALESCE(NEW.init_comm, 0);
		NEW.rec_comm := COALESCE(NEW.rec_comm, 0);
	END IF;
	
	RETURN NEW;
END $$ LANGUAGE plpgsql;

CREATE TRIGGER order_lines_sanitize_02_product
	BEFORE INSERT OR UPDATE ON order_lines
FOR EACH ROW EXECUTE PROCEDURE order_lines_sanitize_product();

/**
 * Sanitizes an order line's coupon.
 */
CREATE OR REPLACE FUNCTION order_lines_sanitize_coupon()
	RETURNS trigger
AS $$
DECLARE
	o			record;
	p			record;
	c			record;
	cur_orders	float;
	t_ratio		numeric := 1;
	o_ratio		numeric := 1;
BEGIN
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
				campaign_id
				)
		SELECT	NEW.status,
				NEW.user_id,
				COALESCE(NEW.coupon_id, promo.id)
		FROM	products
		LEFT JOIN active_promos as promo
		ON		promo.product_id = products.id
		LEFT JOIN campaigns as campaign
		ON		campaign.id = NEW.coupon_id
		AND		promo.product_id = products.id
		WHERE	products.id = NEW.product_id
		RETURNING id,
				campaign_id,
				aff_id
		INTO	o;
		NEW.order_id := o.id;
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
				NEW.coupon_id,
				campaign.aff_id
		FROM	campaigns as campaign
		WHERE	campaign.id = NEW.coupon_id
		RETURNING id,
				campaign_id,
				aff_id
		INTO	o;
		NEW.order_id := o.id;
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
		RETURNING id,
				campaign_id,
				aff_id
		INTO	o;
		NEW.order_id := o.id;
	END IF;
	
	IF	TG_OP = 'INSERT' AND
		NEW.init_discount IS NULL OR NEW.rec_discount IS NULL
	THEN
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
			FROM	campaigns
			WHERE	id = NEW.coupon_id
			AND		product_id = NEW.product_id
			AND		status > 'draft';

			IF	NOT FOUND
			THEN
				NEW.coupon_id := NULL;
			ELSEIF c.aff_id <> o.aff_id -- inconsistent sponsor
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
			FROM	campaigns
			WHERE	promo_id = NEW.product_id
			AND		status > 'draft';

			IF	NOT FOUND
			THEN
				NEW.coupon_id := NULL;
			ELSE
				NEW.coupon_id := c.id;
			END IF;
		ELSE
			NEW.coupon_id := NULL;
		END IF;

		IF	NEW.coupon_id IS NULL
		THEN
			NEW.init_discount := COALESCE(NEW.init_discount, 0);
			NEW.rec_discount := COALESCE(NEW.rec_discount, 0);
			RETURN NEW;
		END IF;
		
		-- Process firesale if applicable
		IF	c.firesale
		THEN
			IF	c.max_date IS NOT NULL
			THEN
				IF	c.max_date >= NOW()
				THEN
					t_ratio := EXTRACT(EPOCH FROM c.max_date - NOW()::datetime) /
						EXTRACT(EPOCH FROM c.max_date - c.min_date);
				ELSE
					t_ratio := 0;
				END IF;
			END IF;
			
			IF	c.max_orders IS NOT NULL
			THEN
				IF	max_orders > 0
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
				ELSE
					o_ratio := 0;
				END IF;
			END IF;
			
			c.init_discount := round(c.init_discount * t_ratio * o_ratio, 2);
			c.rec_discount := round(c.rec_discount * t_ratio * o_ratio, 2);
		END IF;
		
		-- Strip discount from commission where applicable
		IF	o.campaign_id = c.id AND o.aff_id IS NOT NULL AND
			( NEW.init_comm IS NULL OR NEW.rec_comm IS NULL )
		THEN
			SELECT	init_comm,
					rec_comm
			INTO	p
			FROM	products
			WHERE	id = NEW.product_id;
			
			NEW.init_comm := COALESCE(NEW.init_comm, p.init_comm - c.init_discount, 0);
			NEW.rec_comm := COALESCE(NEW.rec_comm, p.rec_comm - c.rec_discount, 0);
		END IF;

		-- Apply discount
		NEW.init_discount := COALESCE(NEW.init_discount, c.init_discount, 0);
		NEW.rec_discount := COALESCE(NEW.rec_discount, c.rec_discount, 0);
	ELSEIF NEW.coupon_id IS NOT NULL
	THEN
		-- Validate coupon
		SELECT	1
		INTO	c
		FROM	campaigns
		WHERE	id = NEW.coupon_id
		AND		status > 'draft';
		
		IF	NOT FOUND
		THEN
			RAISE EXCEPTION 'Cannot tie inactive campaigns.id = % to order_lines.id = %',
				NEW.coupon_id, NEW.id;
		END IF;
	END IF;
	
	RETURN NEW;
END;
$$ LANGUAGE plpgsql;

CREATE TRIGGER order_lines_03_sanitize_coupon
	BEFORE INSERT OR UPDATE ON order_lines
FOR EACH ROW EXECUTE PROCEDURE order_lines_sanitize_coupon();

/**
 * Forward status changes to orders
 */
CREATE OR REPLACE FUNCTION order_lines_delegate_status()
	RETURNS trigger
AS $$
DECLARE
	new_status	status_billable;
BEGIN
	IF	TG_OP = 'UPDATE'
	THEN
		IF	ROW(NEW.status, NEW.order_id) = ROW(OLD.status, OLD.order_id)
		THEN
			RETURN NEW;
		ELSEIF NEW.order_id <> OLD.order_id
		THEN
			-- Also do this for the old order
			SELECT	MAX(order_lines.status)
			INTO	new_status
			FROM	order_lines
			WHERE	order_id = OLD.order_id;
			
			new_status := COALESCE(new_status, 'trash');
			
			UPDATE	orders
			SET		status = new_status
			WHERE	orders.id = OLD.order_id
			AND		status <> new_status;
			
			-- RAISE NOTICE '%, %', TG_NAME, FOUND;
		END IF;
	END IF;
	
	SELECT	MAX(order_lines.status)
	INTO	new_status
	FROM	order_lines
	WHERE	order_id = NEW.order_id;
	
	UPDATE	orders
	SET		status = new_status
	WHERE	orders.id = NEW.order_id
	AND		status <> new_status;
	
	-- RAISE NOTICE '%, %', TG_NAME, FOUND;
	
	RETURN NEW;
END $$ LANGUAGE plpgsql;

CREATE TRIGGER order_lines_10_delegate_status
	AFTER INSERT OR UPDATE ON order_lines
FOR EACH ROW EXECUTE PROCEDURE order_lines_delegate_status();