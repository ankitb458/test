/**
 * Auto-creates a promo for new products.
 */
CREATE OR REPLACE FUNCTION products_autocreate_promo()
	RETURNS trigger
AS $$
BEGIN
	INSERT INTO campaigns (
		uuid,
		status,
		name,
		product_id
		)
	SELECT	NEW.uuid,
			CASE
			WHEN NEW.status = 'draft'
			THEN 'draft'
			ELSE 'inactive'
			END::status_activatable,
			'Promo on ' || NEW.name,
			NEW.id;
	
	RETURN NEW;
END $$ LANGUAGE plpgsql;

CREATE TRIGGER products_20_autocreate_promo
	AFTER INSERT ON products
FOR EACH ROW EXECUTE PROCEDURE products_autocreate_promo();

/**
 * Refreshes coupon discounts on product updates.
 */
CREATE OR REPLACE FUNCTION products_refresh_coupons()
	RETURNS trigger
AS $$
BEGIN
	IF NEW.init_price = OLD.init_price AND NEW.rec_price = OLD.rec_price
	THEN
		RETURN NEW;
	END IF;
	
	UPDATE	campaigns
	SET		status = CASE
			WHEN aff_id IS NOT NULL AND status IN ('active', 'future')
			THEN 'pending'
			ELSE status
			END,
			init_discount = CASE
			-- Zero in, when possible
			WHEN init_discount = 0 OR NEW.init_price = 0 OR aff_id IS NOT NULL AND NEW.init_comm = 0
			THEN 0
			-- Keep common comm ratios
			WHEN init_discount = round(OLD.init_comm / 2, 2)
			THEN round(NEW.init_comm / 2, 2)
			-- Keep affiliate comm ratios for affiliate coupons
			WHEN aff_id IS NOT NULL
			THEN round(init_discount * NEW.init_comm / OLD.init_comm, 2)
			-- Keep discount ratios for site coupons
			ELSE round(init_discount * NEW.init_price / OLD.init_price, 2)
			END,
			rec_discount = CASE
			-- Zero in, when possible
			WHEN rec_discount = 0 OR NEW.rec_price = 0 OR aff_id IS NOT NULL AND NEW.rec_comm = 0
			THEN 0
			-- Keep common comm ratios
			WHEN rec_discount = round(OLD.rec_comm / 2, 2)
			THEN round(NEW.rec_comm / 2, 2)
			-- Keep affiliate comm ratios for affiliate coupons
			WHEN aff_id IS NOT NULL
			THEN round(rec_discount * NEW.rec_comm / OLD.rec_comm, 2)
			-- Keep discount ratios for site coupons
			ELSE round(rec_discount * NEW.rec_price / OLD.rec_price, 2)
			END
	WHERE	product_id = NEW.id
	AND		( -- Always update on price changes
			NEW.init_price <> OLD.init_price OR NEW.rec_price <> OLD.rec_price
			-- Conditionally update on commission changes
			OR aff_id IS NOT NULL
			AND ( NEW.init_comm <> OLD.init_comm OR NEW.rec_comm <> OLD.rec_comm ) );
	
	RETURN NEW;
END $$ LANGUAGE plpgsql;

CREATE TRIGGER products_20_refresh_coupons
	AFTER UPDATE ON products
FOR EACH ROW EXECUTE PROCEDURE products_refresh_coupons();