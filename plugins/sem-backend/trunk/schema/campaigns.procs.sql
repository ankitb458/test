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

/**
 * Prevents promos from being deleted
 */
CREATE OR REPLACE FUNCTION campaigns_delete_promo()
	RETURNS trigger
AS $$
BEGIN
	IF	EXISTS(
		SELECT	1
		FROM	products
		WHERE	id = OLD.promo_id )
	THEN
		RAISE EXCEPTION 'Campaign %s cannot be deleted. Delete the product instead.';
	END IF;
	
	RETURN OLD;
END $$ LANGUAGE plpgsql;

CREATE TRIGGER campaigns_0_delete_promo
	AFTER DELETE ON campaigns
FOR EACH ROW EXECUTE PROCEDURE campaigns_delete_promo();