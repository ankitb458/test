/**
 * Validates a campaign.
 */
CREATE OR REPLACE FUNCTION campaigns_check_discount()
	RETURNS trigger
AS $$
DECLARE
	p		record;
BEGIN
	IF	TG_TABLE_NAME <> 'campaigns' OR
		NEW.product_id IS NULL OR
		ROW(NEW.init_discount, NEW.rec_discount) = ROW(0, 0)
	THEN
		RETURN NEW;
	ELSEIF TG_OP = 'UPDATE'
	THEN
		IF	ROW(NEW.init_discount, NEW.rec_discount) = ROW(OLD.init_discount, OLD.rec_discount)
		THEN
			RETURN NEW;
		END IF;
	END IF;

	-- Validate product and sanitize status
	SELECT	uuid,
			status,
			init_price,
			init_comm,
			rec_price,
			rec_comm
	INTO	p
	FROM	products
	WHERE	id = NEW.product_id;
	
	IF	NEW.product_id = NEW.promo_id
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
		ELSEIF NEW.status = 'inherit' -- allowed for promos only
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

CREATE TRIGGER campaigns_02_check_discount
	BEFORE INSERT OR UPDATE ON campaigns
FOR EACH ROW EXECUTE PROCEDURE campaigns_check_discount();

/**
 * Prevents promo_id and product_id from being updated when relevant.
 */
CREATE OR REPLACE FUNCTION campaigns_update_promo()
	RETURNS trigger
AS $$
BEGIN
	IF	NEW.promo_id IS NULL AND OLD.promo_id IS NOT NULL OR
		NEW.promo_id IS NOT NULL AND OLD.promo_id IS NULL
	THEN
		RAISE EXCEPTION 'campaigns.id = % is tied to products.id = %.', OLD.id, OLD.promo_id;
	END IF;
	
	RETURN NEW;
END;
$$ LANGUAGE plpgsql;

CREATE TRIGGER campaigns_05_update_promo
	AFTER UPDATE ON campaigns
FOR EACH ROW EXECUTE PROCEDURE campaigns_update_promo();

/**
 * Prevents promos from being deleted
 */
CREATE OR REPLACE FUNCTION campaigns_delete_promo()
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

CREATE TRIGGER campaigns_05_delete_promo
	AFTER DELETE ON campaigns
FOR EACH ROW EXECUTE PROCEDURE campaigns_delete_promo();