/**
 * Sanitizes a campaign's name based on the affiliate.
 */
CREATE OR REPLACE FUNCTION campaigns_sanitize_name()
	RETURNS trigger
AS $$
DECLARE
	_user		record;
BEGIN
	IF	NEW.name IS NOT NULL AND NEW.ukey IS NOT NULL
	THEN
		RETURN NEW;
	ELSEIF NEW.aff_id IS NULL
	THEN
		IF	NEW.promo_id IS NULL
		THEN
			NEW.name := COALESCE(NEW.name, 'Campaign');
			NEW.ukey := COALESCE(NEW.ukey, 'campaign');
		END IF;
		
		RETURN NEW;
	END IF;
	
	SELECT	COALESCE(NEW.name, name),
			COALESCE(NEW.ukey, ukey, to_slug(name), NEW.id::varchar)
	INTO	NEW.name,
			NEW.ukey
	FROM	users
	WHERE	id = NEW.aff_id;
	
	RETURN NEW;
END $$ LANGUAGE plpgsql;

CREATE TRIGGER campaigns_01_sanitize_name
	BEFORE INSERT OR UPDATE ON campaigns
FOR EACH ROW EXECUTE PROCEDURE campaigns_sanitize_name();

/**
 * Validates a coupon's discounts.
 */
CREATE OR REPLACE FUNCTION campaigns_sanitize_coupon()
	RETURNS trigger
AS $$
DECLARE
	_product		record;
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
	
	SELECT	status,
			init_price,
			init_comm,
			rec_price,
			rec_comm
	INTO	_product
	FROM	products
	WHERE	id = NEW.product_id;
	
	IF	NEW.product_id = NEW.promo_id
	THEN
		IF	TG_OP = 'INSERT'
		THEN
			NEW.status := CASE
				WHEN _product.status = 'trash'
				THEN 'trash'
				WHEN _product.status = 'draft'
				THEN 'draft'
				WHEN _product.status = 'pending'
				THEN 'pending'
				WHEN _product.status < 'future' OR NEW.status = 'trash'
				THEN 'inactive'
				ELSE NEW.status
				END::status_activatable;
		ELSE
			NEW.status := CASE
				WHEN _product.status = 'trash'
				THEN 'trash'
				WHEN _product.status = 'draft'
				THEN 'draft'
				WHEN _product.status = 'pending'
				THEN 'pending'
				WHEN _product.status < 'future' OR OLD.status = 'trash' OR NEW.status = 'trash'
				THEN 'inactive'
				ELSE NEW.status
				END::status_activatable;
		END IF;
	END IF;
	
	-- Sanitize discount
	IF	NEW.aff_id IS NOT NULL
	THEN
		NEW.init_discount := LEAST(NEW.init_discount, _product.init_comm);
		NEW.rec_discount := LEAST(NEW.rec_discount, _product.rec_comm);
	ELSE
		NEW.init_discount := LEAST(NEW.init_discount, _product.init_price - _product.init_comm);
		NEW.rec_discount := LEAST(NEW.rec_discount, _product.rec_price - _product.rec_comm);
	END IF;
	
	RETURN NEW;
END $$ LANGUAGE plpgsql;

CREATE TRIGGER campaigns_03_sanitize_coupon
	BEFORE INSERT OR UPDATE ON campaigns
FOR EACH ROW EXECUTE PROCEDURE campaigns_sanitize_coupon();

/**
 * Automatically sets the release date
 */
CREATE OR REPLACE FUNCTION campaigns_sanitize_launch_date()
	RETURNS trigger
AS $$
BEGIN
	IF	NEW.product_id IS NULL
	THEN
		NEW.launch_date := NULL;
		NEW.expire_date := NULL;
		RETURN NEW;
	ELSEIF NEW.status > 'inactive' AND NEW.launch_date IS NULL
	THEN
		NEW.launch_date := NOW();
		RETURN NEW;
	ELSEIF NEW.status <= 'inactive' OR TG_OP = 'INSERT'
	THEN
		RETURN NEW;
	END IF;
	
	IF	ROW(NEW.product_id, NEW.init_discount, NEW.rec_discount, NEW.firesale)
		IS NOT DISTINCT FROM
		ROW(OLD.product_id, OLD.init_discount, OLD.rec_discount, OLD.firesale)
	THEN
		RETURN NEW;
	ELSEIF NEW.launch_date IS DISTINCT FROM OLD.launch_date
	THEN
		RETURN NEW;
	END IF;
	
	NEW.launch_date := GREATEST(NEW.launch_date, NOW());
	IF	NEW.expire_date IS NOT NULL AND NEW.launch_date > NEW.expire_date
	THEN
		NEW.expire_date := NEW.launch_date;
	END IF;
	
	RETURN NEW;
END $$ LANGUAGE plpgsql;

CREATE TRIGGER campaigns_90_sanitize_launch_date
	BEFORE INSERT OR UPDATE ON campaigns
FOR EACH ROW EXECUTE PROCEDURE campaigns_sanitize_launch_date();

/**
 * Prevents promos from being deleted before the product it is tied to.
 */
CREATE OR REPLACE FUNCTION campaigns_delete_promo()
	RETURNS trigger
AS $$
BEGIN
	IF	OLD.promo_id IS NULL
	THEN
		RETURN OLD;
	ELSEIF EXISTS (
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

CREATE CONSTRAINT TRIGGER campaigns_01_delete_promo
	AFTER DELETE ON campaigns
FOR EACH ROW EXECUTE PROCEDURE campaigns_delete_promo();