/**
 * Checks integrity when a campaign is trashed.
 */
CREATE OR REPLACE FUNCTION campaigns_check_trash()
	RETURNS trigger
AS $$
BEGIN
	IF	NEW.status = OLD.status OR NEW.status > 'draft'
	THEN
		RETURN NEW;
	END IF;
	
	IF	EXISTS (
		SELECT	1
		FROM	orders
		WHERE	campaign_id = NEW.id -- cascade updated
		)
	THEN
		RAISE EXCEPTION 'Cannot delete campaigns.id = %. It is referenced in orders.campaign_id.', NEW.id;
	END IF;
	
	IF	EXISTS (
		SELECT	1
		FROM	order_lines
		WHERE	coupon_id = NEW.id -- cascade updated
		)
	THEN
		RAISE EXCEPTION 'Cannot delete campaigns.id = %. It is referenced in order_lines.coupon_id.', NEW.id;
	END IF;
	
	RETURN NEW;
END;
$$ LANGUAGE plpgsql;

CREATE CONSTRAINT TRIGGER campaigns_30_check_trash
	AFTER UPDATE ON campaigns
FOR EACH ROW EXECUTE PROCEDURE campaigns_check_trash();

/**
 * Sanitizes a campaign's affiliate.
 */
CREATE OR REPLACE FUNCTION campaigns_sanitize_aff_id()
	RETURNS trigger
AS $$
DECLARE
	u			record;
BEGIN
	IF	NEW.aff_id IS NULL
	THEN
		IF	NEW.promo_id IS NULL
		THEN
			NEW.name := COALESCE(NEW.name, 'Campaign');
			NEW.ukey := COALESCE(NEW.ukey, 'campaign');
		END IF;
		RETURN NEW;
	ELSEIF TG_OP = 'UPDATE'
	THEN
		IF	NEW.aff_id IS NOT DISTINCT FROM OLD.aff_id
		THEN
			RETURN NEW;
		END IF;
	END IF;
	
	SELECT	name,
			ukey
	INTO	u
	FROM	users
	WHERE	id = NEW.aff_id
	AND		status > 'pending';
	
	IF	NOT FOUND
	THEN
		RAISE EXCEPTION 'Cannot tie inactive users.id = % to campaigns.id = %.',
			NEW.aff_id, NEW.id;
	ELSE
		NEW.name := COALESCE(NULLIF(NEW.name, ''), u.name);
		NEW.ukey := COALESCE(NULLIF(NEW.ukey, ''), u.ukey, u.name);
	END IF;
	
	RETURN NEW;
END $$ LANGUAGE plpgsql;

CREATE TRIGGER campaigns_02_sanitize_aff_id
	BEFORE INSERT OR UPDATE ON campaigns
FOR EACH ROW EXECUTE PROCEDURE campaigns_sanitize_aff_id();

/**
 * Validates a coupon's discounts.
 */
CREATE OR REPLACE FUNCTION campaigns_sanitize_coupon()
	RETURNS trigger
AS $$
DECLARE
	p			record;
BEGIN
	IF	NEW.product_id IS NULL OR
		TG_OP = 'INSERT' AND NEW.promo_id IS NOT NULL
	THEN
		RETURN NEW;
	ELSEIF TG_OP = 'UPDATE'
	THEN
		IF	ROW(NEW.status, NEW.product_id, NEW.init_discount, NEW.rec_discount)
			IS NOT DISTINCT FROM ROW(OLD.status, OLD.product_id, OLD.init_discount, OLD.rec_discount)
		THEN
			RETURN NEW;
		END IF;
	END IF;
	
	IF	NEW.product_id = NEW.promo_id
	THEN
		SELECT	status,
				init_price,
				init_comm,
				rec_price,
				rec_comm
		INTO	p
		FROM	products
		WHERE	id = NEW.product_id;
		
		IF	TG_OP = 'INSERT'
		THEN
			NEW.status := CASE
				WHEN p.status <= 'inherit'
				THEN 'inherit'
				WHEN p.status = 'draft'
				THEN 'draft'
				WHEN p.status = 'pending'
				THEN 'pending'
				WHEN p.status < 'future' OR NEW.status <= 'inherit'
				THEN 'inactive'
				ELSE NEW.status
				END::status_activatable;
		ELSE
			NEW.status := CASE
				WHEN p.status <= 'inherit'
				THEN 'inherit'
				WHEN p.status = 'draft'
				THEN 'draft'
				WHEN p.status = 'pending'
				THEN 'pending'
				WHEN p.status < 'future' OR OLD.status <= 'inherit' OR NEW.status <= 'inherit'
				THEN 'inactive'
				ELSE NEW.status
				END::status_activatable;
		END IF;
	ELSE
		SELECT	init_price,
				init_comm,
				rec_price,
				rec_comm
		INTO	p
		FROM	products
		WHERE	id = NEW.product_id
		AND		status > 'draft';
		
		IF	NOT FOUND
		THEN
			RAISE EXCEPTION 'Cannot tie inactive campaigns.id = % to products.id = %.',
				NEW.id, NEW.product_id;
		END IF;
	END IF;
	
	-- Sanitize discount
	IF	NEW.aff_id IS NOT NULL
	THEN
		NEW.init_discount := LEAST(NEW.init_discount, p.init_comm);
		NEW.rec_discount := LEAST(NEW.rec_discount, p.rec_comm);
	ELSE
		NEW.init_discount := LEAST(NEW.init_discount, p.init_price - p.init_comm);
		NEW.rec_discount := LEAST(NEW.rec_discount, p.rec_price - p.rec_comm);
	END IF;
	
	RETURN NEW;
END $$ LANGUAGE plpgsql;

CREATE TRIGGER campaigns_03_sanitize_coupon
	BEFORE INSERT OR UPDATE ON campaigns
FOR EACH ROW EXECUTE PROCEDURE campaigns_sanitize_coupon();

/**
 * Prevents promo_id and product_id from being updated when relevant.
 *
 * Insert is prevented by the unique index.
 */
CREATE OR REPLACE FUNCTION campaigns_check_update_promo()
	RETURNS trigger
AS $$
BEGIN
	-- IS DISTINCT FROM would disallow SQL to propagate an id change
	IF	NEW.promo_id IS NULL AND OLD.promo_id IS NOT NULL OR
		NEW.promo_id IS NOT NULL AND OLD.promo_id IS NULL
	THEN
		RAISE EXCEPTION 'Cannot change promo_id on campaigns.id = %.',
			NEW.id;
	END IF;
	
	RETURN NEW;
END;
$$ LANGUAGE plpgsql;

CREATE CONSTRAINT TRIGGER campaigns_01_check_update_promo
	AFTER UPDATE ON campaigns
FOR EACH ROW EXECUTE PROCEDURE campaigns_check_update_promo();

/**
 * Prevents promos from being deleted before the product it is tied to.
 */
CREATE OR REPLACE FUNCTION campaigns_check_delete_promo()
	RETURNS trigger
AS $$
BEGIN
	IF	EXISTS (
		SELECT	1
		FROM	products
		WHERE	id = OLD.promo_id
		)
	THEN
		RAISE EXCEPTION 'campaigns.id = % is tied to products.id = %.', OLD.id, OLD.promo_id;
	END IF;
	
	RETURN OLD;
END;
$$ LANGUAGE plpgsql;

CREATE CONSTRAINT TRIGGER campaigns_01_check_delete_promo
	AFTER DELETE ON campaigns
FOR EACH ROW EXECUTE PROCEDURE campaigns_check_delete_promo();