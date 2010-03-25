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
		RAISE EXCEPTION 'promo_id is a read-only field.';
	END IF;
	
	RETURN NEW;
END $$ LANGUAGE plpgsql;

CREATE TRIGGER campaigns_0_update_promo
	AFTER UPDATE ON campaigns
FOR EACH ROW EXECUTE PROCEDURE campaigns_update_promo();

