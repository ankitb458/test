/**
 * Sanitizes an order's campaign.
 */
CREATE OR REPLACE FUNCTION orders_sanitize_campaign_id()
	RETURNS trigger
AS $$
DECLARE
	_aff_id		bigint;
BEGIN
	IF	NEW.campaign_id IS NULL OR NEW.aff_id IS NOT NULL
	THEN
		RETURN NEW;
	END IF;
	
	SELECT	aff_id
	INTO	NEW.aff_id
	FROM	campaigns
	WHERE	id = NEW.campaign_id;
	
	RETURN NEW;
END $$ LANGUAGE plpgsql;

CREATE TRIGGER orders_01_sanitize_campaign_id
	BEFORE INSERT ON orders
FOR EACH ROW EXECUTE PROCEDURE orders_sanitize_campaign_id();

/**
 * Delegates commission handling on orders
 */
CREATE OR REPLACE FUNCTION orders_delegate_aff_id()
	RETURNS trigger
AS $$
BEGIN
	IF	NEW.aff_id IS NOT DISTINCT FROM OLD.aff_id OR
		NEW.aff_id IS NOT NULL -- Undefined behavior
	THEN
		RETURN NEW;
	END IF;
	
	-- Cancel commissions
	UPDATE	order_lines
	SET		init_comm = 0,
			rec_comm = 0
	WHERE	order_id = NEW.id
	AND		( init_comm <> 0 OR rec_comm <> 0 );
	
	RETURN NEW;
END $$ LANGUAGE plpgsql;

CREATE TRIGGER orders_10_delegate_aff_id
	AFTER UPDATE ON orders
FOR EACH ROW EXECUTE PROCEDURE orders_delegate_aff_id();

/**
 * Delegates stock handling when orders are cleared
 *
 * Todo: use shipments/shipment lines tables, and trigger this on order line updates instead.
 */
CREATE OR REPLACE FUNCTION orders_delegate_stock()
	RETURNS trigger
AS $$
BEGIN
	IF NEW.cleared_date IS NULL AND OLD.cleared_date IS NOT NULL
	THEN
		RAISE EXCEPTION 'Cannot drop cleared date on orders.id = %',
			NEW.id;
	ELSEIF NEW.cleared_date IS NULL OR OLD.cleared_date IS NOT NULL
	THEN
		RETURN NEW;
	END IF;
	
	-- Deplete products
	UPDATE	products
	SET		stock = GREATEST(products.stock - order_lines.quantity, 0)
	FROM	order_lines
	WHERE	order_lines.order_id = NEW.id
	AND		order_lines.status = 'cleared'
	AND		products.id = order_lines.product_id
	AND		products.stock IS NOT NULL;
	
	-- Deplete coupons
	UPDATE	campaigns
	SET		stock = GREATEST(campaigns.stock - order_lines.quantity, 0)
	FROM	order_lines
	WHERE	order_lines.order_id = NEW.id
	AND		order_lines.status = 'cleared'
	AND		campaigns.id = order_lines.coupon_id
	AND		campaigns.stock IS NOT NULL;
	
	RETURN NEW;
END $$ LANGUAGE plpgsql;

CREATE TRIGGER orders_20_delegate_stock
	AFTER UPDATE ON orders
FOR EACH ROW EXECUTE PROCEDURE orders_delegate_stock();

/**
 * Delegate commissions
 */
CREATE OR REPLACE FUNCTION orders_delegate_commissions()
	RETURNS trigger
AS $$
DECLARE
	rec		record;
BEGIN
	IF	NEW.status <> 'cleared' OR
		NEW.aff_id IS NOT NULL AND NEW.aff_id IS NOT DISTINCT FROM OLD.aff_id
	THEN
		RETURN NEW;
	END IF;
	
	IF	OLD.aff_id IS NULL
	THEN
		NULL;
	ELSE
		NULL;
	END IF;
	
	RETURN NEW;
END $$ LANGUAGE plpgsql;

CREATE TRIGGER orders_20_delegate_commissions
	AFTER UPDATE ON orders
FOR EACH ROW EXECUTE PROCEDURE orders_delegate_commissions();