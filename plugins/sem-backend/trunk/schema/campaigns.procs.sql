/**
 * Sanitizes a campaign's affiliate.
 */
CREATE OR REPLACE FUNCTION campaigns_sanitize_user()
	RETURNS trigger
AS $$
BEGIN
	IF	TG_TABLE_NAME <> 'campaigns' OR -- Trust triggers
		NEW.aff_id IS NULL
	THEN
		RETURN NEW;
	ELSEIF TG_OP = 'UPDATE'
	THEN
		IF	NEW.aff_id IS NOT DISTINCT FROM OLD.aff_id
		THEN
			RETURN NEW;
		END IF;
	END IF;
	
	IF	NOT EXISTS(
		SELECT	1
		FROM	users
		WHERE	id = NEW.aff_id
		AND		status > 'pending'
		)
	THEN
		NEW.aff_id := NULL;
	END IF;
	
	RETURN NEW;
END $$ LANGUAGE plpgsql;

CREATE TRIGGER campaigns_02_sanitize_user
	BEFORE INSERT OR UPDATE ON campaigns
FOR EACH ROW EXECUTE PROCEDURE campaigns_sanitize_user();

/**
 * Validates a coupon's discounts.
 */
CREATE OR REPLACE FUNCTION campaigns_sanitize_coupon()
	RETURNS trigger
AS $$
DECLARE
	p		record;
BEGIN
	IF	NEW.product_id IS NULL
	THEN
		-- Reset coupon fields and exit
		IF	NEW.status <> 'trash'
		THEN
			NEW.status := 'active';
		END IF;
		NEW.init_discount := 0;
		NEW.rec_discount := 0;
		NEW.min_date := NULL;
		NEW.max_date := NULL;
		NEW.max_orders := NULL;
		NEW.firesale := FALSE;
		
		RETURN NEW;
	ELSEIF TG_TABLE_NAME <> 'campaigns' OR -- Trust triggers
		ROW(NEW.product_id, NEW.init_discount, NEW.rec_discount)
		IS NOT DISTINCT FROM ROW(NULL, 0, 0)
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
	
	-- Validate product and sanitize status
	SELECT	status,
			init_price,
			init_comm,
			rec_price,
			rec_comm
	INTO	p
	FROM	products
	WHERE	id = NEW.product_id;
	
	IF	NOT FOUND
	THEN
		NEW.product_id := NULL;
	ELSEIF NEW.product_id = NEW.promo_id
	THEN
		NEW.status := CASE
			WHEN p.status <= 'inherit'
			THEN 'inherit'
			WHEN p.status = 'draft'
			THEN 'draft'
			WHEN p.status = 'pending'
			THEN 'pending'
			WHEN p.status < 'future'
			THEN 'inactive'
			ELSE NEW.status
			END::status_activatable;
	ELSE
		IF p.status < 'future'
		THEN
			NEW.product_id := NULL;
		ELSEIF NEW.status = 'inherit' -- Allowed for promos only
		THEN
			NEW.status := 'trash';
		END IF;
	END IF;
	
	-- Sanitize discount
	IF	NEW.product_id IS NULL
	THEN
		NEW.init_discount := 0;
		NEW.rec_discount := 0;
	ELSEIF NEW.aff_id IS NOT NULL
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
	IF	NEW.promo_id IS DISTINCT FROM OLD.promo_id
	THEN
		RAISE EXCEPTION 'campaigns.id = % is tied to products.id = %.', OLD.id, OLD.promo_id;
	END IF;
	
	RETURN NEW;
END;
$$ LANGUAGE plpgsql;

CREATE TRIGGER campaigns_01_check_update_promo
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

CREATE TRIGGER campaigns_01_check_delete_promo
	AFTER DELETE ON campaigns
FOR EACH ROW EXECUTE PROCEDURE campaigns_check_delete_promo();