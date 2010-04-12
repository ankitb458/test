/**
 * Sets a default name when needed.
 */
CREATE OR REPLACE FUNCTION order_lines_sanitize_name()
	RETURNS trigger
AS $$
BEGIN
	-- Default name
	IF	NEW.name IS NULL
	THEN
		IF	NEW.product_id IS NOT NULL
		THEN
			SELECT	name
			INTO	NEW.name
			FROM	products
			WHERE	id = NEW.product_id;
		ELSE
			NEW.name := 'Anonymous Product';
		END IF;
	END IF;
	
	RETURN NEW;
END;
$$ LANGUAGE plpgsql;

CREATE TRIGGER order_lines_05_sanitize_name
	BEFORE INSERT OR UPDATE ON order_lines
FOR EACH ROW EXECUTE PROCEDURE order_lines_sanitize_name();

/**
 * Sanitizes an order line's coupon.
 *
 * To force a refresh of an order's details (e.g. when changing its
 * campaign, affiliate, or coupon), update an order line and set the
 * relevant amount fields to NULL.
 */
CREATE OR REPLACE FUNCTION order_lines_sanitize_financials()
	RETURNS trigger
AS $$
DECLARE
	_order		record;
	_product	record;
	_coupon		record;
	cur_orders	bigint;
	t_ratio		float8 := 1;
	o_ratio		float8 := 1;
BEGIN
	IF	TG_OP = 'UPDATE' AND
		NEW.init_price IS NOT NULL AND NEW.rec_price IS NOT NULL AND
		NEW.init_comm IS NOT NULL AND NEW.rec_comm IS NOT NULL AND
		NEW.init_discount IS NOT NULL AND NEW.rec_discount IS NOT NULL
	THEN
		-- Sanitize and bail early
		NEW.init_comm = LEAST(NEW.init_comm, NEW.init_price - NEW.init_discount);
		NEW.rec_comm = LEAST(NEW.rec_comm, NEW.rec_price - NEW.rec_discount);
		
		-- RAISE NOTICE '%, %, % / %, %, %',
		-- 	NEW.init_price, NEW.init_comm, NEW.init_discount,
		-- 	NEW.rec_price, NEW.rec_comm, NEW.rec_discount;
		
		RETURN NEW;
	END IF;
	
	IF	NEW.product_id IS NULL
	THEN
		-- Try to bail early
		NEW.init_discount := COALESCE(NEW.init_discount, 0);
		NEW.rec_discount := COALESCE(NEW.rec_discount, 0);
		NEW.init_price := COALESCE(NEW.init_price, 0);
		NEW.rec_price := COALESCE(NEW.rec_price, 0);
		NEW.init_comm := COALESCE(NEW.init_comm, 0);
		NEW.rec_comm := COALESCE(NEW.rec_comm, 0);
		
		NEW.init_comm = LEAST(NEW.init_comm, NEW.init_price - NEW.init_discount);
		NEW.rec_comm = LEAST(NEW.rec_comm, NEW.rec_price - NEW.rec_discount);
		
		IF	NEW.order_id IS NOT NULL AND
			ROW(NEW.init_comm, NEW.rec_comm) = ROW(0, 0)
		THEN
			-- We've an order, and no product, so no coupon
			NEW.coupon_id := NULL;
			
			-- RAISE NOTICE '%, %, % / %, %, %',
			-- 	NEW.init_price, NEW.init_comm, NEW.init_discount,
			-- 	NEW.rec_price, NEW.rec_comm, NEW.rec_discount;
			
			RETURN NEW;
		END IF;
	END IF;
	
	IF	NEW.order_id IS NOT NULL
	THEN
		-- Fetch key order details
		SELECT	id,
				user_id,
				campaign_id,
				aff_id
		INTO	_order
		FROM	orders
		WHERE	id = NEW.order_id;
	ELSEIF NEW.product_id IS NOT NULL
	THEN
		-- Auto-create order using the product_id
		INSERT INTO orders (
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
		LEFT JOIN campaigns as campaign -- Non-active coupons are sanitized later
		ON		campaign.id = NEW.coupon_id
		AND		promo.product_id = products.id
		WHERE	products.id = NEW.product_id
		RETURNING id,
				user_id,
				campaign_id,
				aff_id
		INTO	_order;
		NEW.order_id := _order.id;
	ELSEIF NEW.coupon_id IS NOT NULL
	THEN
		-- Auto-create order using the coupon_id
		INSERT INTO orders (
				status,
				user_id,
				campaign_id,
				aff_id
				)
		SELECT	NEW.status,
				NEW.user_id,
				NEW.coupon_id,
				campaign.aff_id
		FROM	campaigns as campaign -- Non-active coupons are sanitized later
		WHERE	campaign.id = NEW.coupon_id
		RETURNING id,
				user_id,
				campaign_id,
				aff_id
		INTO	_order;
		NEW.order_id := _order.id;
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
				user_id,
				campaign_id,
				aff_id
		INTO	_order;
		NEW.order_id := _order.id;
	END IF;
	
	IF	NEW.product_id IS NULL
	THEN
		-- We've an order, no product, so no coupon
		-- The coupon got passed as the order's campaign
		NEW.coupon_id := NULL;
		
		-- RAISE NOTICE '%, %, % / %, %, %',
		-- 	NEW.init_price, NEW.init_comm, NEW.init_discount,
		-- 	NEW.rec_price, NEW.rec_comm, NEW.rec_discount;
		
		RETURN NEW;
	END IF;
	
	-- Validate product
	SELECT	init_price,
			init_comm,
			rec_price,
			rec_comm,
			rec_interval,
			rec_count
	INTO	_product
	FROM	products
	WHERE	id = NEW.product_id;
	
	IF	ROW(NEW.init_discount, NEW.rec_discount) = ROW(0, 0)
	THEN
		-- Squash the coupon
		NEW.coupon_id := NULL;
	END IF;
	
	IF	NEW.coupon_id IS NOT NULL OR NEW.init_discount IS NULL OR NEW.rec_discount IS NULL
	THEN
		IF	NEW.coupon_id IS NOT NULL
		THEN
			-- Validate coupon
			SELECT	aff_id,
					init_discount,
					rec_discount,
					firesale,
					launch_date,
					expire_date,
					stock
			INTO	_coupon
			FROM	active_coupons
			WHERE	id = NEW.coupon_id
			AND		product_id = NEW.product_id;
		
			IF	NOT FOUND OR
				-- Drop inconsistent sponsors
				_coupon.aff_id IS NOT NULL AND _order.campaign_id IS NOT NULL AND
				_coupon.aff_id IS DISTINCT FROM _order.aff_id
			THEN
				NEW.coupon_id := NULL;
			END IF;
		END IF;
	
		IF	NEW.coupon_id IS NULL AND _order.campaign_id IS NOT NULL
		THEN
			-- Autofech coupon
			SELECT	aff_id,
					init_discount,
					rec_discount,
					firesale,
					launch_date,
					expire_date,
					stock
			INTO	_coupon
			FROM	active_coupons
			WHERE	id = _order.campaign_id
			AND		product_id = NEW.product_id;
		
			IF	FOUND
			THEN
				NEW.coupon_id := _order.campaign_id;
			END IF;
		END IF;
	
		IF	NEW.coupon_id IS NULL
		THEN
			-- Autofetch promo
			SELECT	aff_id,
					init_discount,
					rec_discount,
					firesale,
					launch_date,
					expire_date,
					stock
			INTO	_coupon
			FROM	active_promos
			WHERE	promo_id = NEW.product_id;
		
			IF	FOUND
			THEN
				NEW.coupon_id := _order.campaign_id;
			END IF;
		END IF;
	END IF;
	
	IF	NEW.coupon_id IS NULL
	THEN
		-- Sanitize amounts
		NEW.init_discount := COALESCE(NEW.init_discount, 0);
		NEW.rec_discount := COALESCE(NEW.rec_discount, 0);
		NEW.init_price := COALESCE(NEW.init_price, _product.init_price);
		NEW.rec_price := COALESCE(NEW.rec_price, _product.rec_price);
		IF	_order.aff_id IS NULL
		THEN
			NEW.init_comm := 0;
			NEW.rec_comm := 0;
		ELSE -- Assume a site discount, if any
			NEW.init_comm := COALESCE(NEW.init_comm, _product.init_comm);
			NEW.rec_comm := COALESCE(NEW.rec_comm, _product.rec_comm);
			
			NEW.init_comm := LEAST(NEW.init_comm, NEW.init_price - NEW.init_discount);
			NEW.rec_comm := LEAST(NEW.rec_comm, NEW.rec_price - NEW.rec_discount);
		END IF;
	ELSE
		-- Process firesale, if any
		IF	_coupon.firesale
		THEN
			IF	_coupon.expire_date IS NOT NULL
			THEN
				t_ratio := ( EXTRACT(EPOCH FROM _coupon.expire_date - NOW()::datetime) /
					EXTRACT(EPOCH FROM _coupon.expire_date - _coupon.launch_date) )::float8;
			END IF;
		
			IF	_coupon.stock IS NOT NULL
			THEN
				SELECT	SUM(order_lines.quantity::bigint)::bigint
				INTO	cur_orders
				FROM	order_lines
				JOIN	orders
				ON		orders.id = order_lines.order_id
				WHERE	order_lines.order_id <> NEW.order_id
				AND		order_lines.coupon_id = NEW.coupon_id
				AND		order_lines.status > 'pending'
				AND		orders.cleared_date >= _coupon.launch_date;
		
				o_ratio := ( _coupon.stock / ( COALESCE(cur_orders, 0) + _coupon.stock ) )::float8;
			END IF;
		
			_coupon.init_discount := round(_coupon.init_discount * t_ratio * o_ratio, 2);
			_coupon.rec_discount := round(_coupon.rec_discount * t_ratio * o_ratio, 2);
		END IF;
		
		-- Sanitize amounts
		NEW.init_discount := COALESCE(NEW.init_discount, _coupon.init_discount, 0);
		NEW.rec_discount := COALESCE(NEW.rec_discount, _coupon.rec_discount, 0);
		NEW.init_price := COALESCE(NEW.init_price, _product.init_price);
		NEW.rec_price := COALESCE(NEW.rec_price, _product.rec_price);
		IF	_coupon.aff_id IS NULL
		THEN
			NEW.init_comm := COALESCE(NEW.init_comm, _product.init_comm);
			NEW.rec_comm := COALESCE(NEW.rec_comm, _product.rec_comm);
		ELSE
			NEW.init_comm := COALESCE(NEW.init_comm, _product.init_comm - NEW.init_discount);
			NEW.rec_comm := COALESCE(NEW.rec_comm, _product.rec_comm - NEW.rec_discount);
		END IF;
		NEW.init_comm := LEAST(NEW.init_comm, NEW.init_price - NEW.init_discount);
		NEW.rec_comm := LEAST(NEW.rec_comm, NEW.rec_price - NEW.rec_discount);
	END IF;
	
	-- Fetch interval/count
	IF	NEW.rec_price > 0
	THEN
		NEW.rec_interval := COALESCE(NEW.rec_interval, _product.rec_interval);
		NEW.rec_count := COALESCE(NEW.rec_count, _product.rec_count);
	ELSE
		NEW.rec_interval := NULL;
		NEW.rec_count := NULL;
	END IF;
	
	--RAISE NOTICE '%, %, % / %, %, %',
	--	NEW.init_price, NEW.init_comm, NEW.init_discount,
	--	NEW.rec_price, NEW.rec_comm, NEW.rec_discount;
	
	RETURN NEW;
END;
$$ LANGUAGE plpgsql;

CREATE TRIGGER order_lines_02_sanitize_financials
	BEFORE INSERT OR UPDATE ON order_lines
FOR EACH ROW EXECUTE PROCEDURE order_lines_sanitize_financials();

/**
 * Forward status changes to orders
 */
CREATE OR REPLACE FUNCTION order_lines_delegate_status()
	RETURNS trigger
AS $$
DECLARE
	_status		status_payable;
BEGIN
	IF	TG_OP = 'UPDATE'
	THEN
		IF	NEW.status = OLD.status
		THEN
			RETURN NEW;
		END IF;
	END IF;
	
	SELECT	MAX(status)
	INTO	_status
	FROM	order_lines
	WHERE	order_id = NEW.order_id;
	
	UPDATE	orders
	SET		status = _status
	WHERE	id = NEW.order_id
	AND		status <> _status;
	
	-- RAISE NOTICE '%, %', TG_NAME, FOUND;
	
	RETURN NEW;
END $$ LANGUAGE plpgsql;

CREATE TRIGGER order_lines_10_delegate_status
	AFTER INSERT OR UPDATE ON order_lines
FOR EACH ROW EXECUTE PROCEDURE order_lines_delegate_status();

/**
 * Forwards coupon_id to orders.
 *
 * Doing the reciprocal query, or checking the validity of coupon owners
 * on order updates, is invalid because campaign owners may change.
 */
CREATE OR REPLACE FUNCTION order_lines_delegate_campaign_id()
	RETURNS trigger
AS $$
BEGIN
	IF	NEW.coupon_id IS NULL
	THEN
		RETURN NEW;
	ELSEIF TG_OP = 'UPDATE'
	THEN
		IF	NEW.coupon_id IS NOT DISTINCT FROM OLD.coupon_id
		THEN
			RETURN NEW;
		END IF;
	END IF;
	
	UPDATE	orders
	SET		campaign_id = NEW.coupon_id,
			aff_id = campaigns.aff_id
	FROM	campaigns
	WHERE	orders.id = NEW.order_id
	AND		campaign_id IS NULL
	AND		campaigns.id = NEW.coupon_id;
	
	-- RAISE NOTICE '%, %', TG_NAME, FOUND;
	
	RETURN NEW;
END $$ LANGUAGE plpgsql;

CREATE TRIGGER order_lines_15_delegate_campaign_id
	AFTER INSERT OR UPDATE ON order_lines
FOR EACH ROW EXECUTE PROCEDURE order_lines_delegate_campaign_id();

/**
 * Delegate changes in amounts to uncleared invoices
 */
CREATE OR REPLACE FUNCTION order_lines_delegate_invoice_lines()
	RETURNS trigger
AS $$
DECLARE
	rec		record;
BEGIN
	IF	ROW(NEW.init_price, NEW.rec_price) = ROW(OLD.init_price, OLD.rec_price) AND
		ROW(NEW.init_comm, NEW.rec_comm) = ROW(OLD.init_comm, OLD.rec_comm) AND
		ROW(NEW.init_discount, NEW.rec_discount) = ROW(OLD.init_discount, OLD.rec_discount)
	THEN
		RETURN NEW;
	END IF;
	
	-- Todo: update uncleared payments
	
	RETURN NEW;
END $$ LANGUAGE plpgsql;

CREATE TRIGGER order_lines_20_delegate_invoice_lines
	AFTER UPDATE ON order_lines
FOR EACH ROW EXECUTE PROCEDURE order_lines_delegate_invoice_lines();