/**
 * Checks integrity when a product is trashed.
 */
CREATE OR REPLACE FUNCTION products_check_trash()
	RETURNS trigger
AS $$
BEGIN
	IF	NEW.status = OLD.status OR NEW.status > 'draft'
	THEN
		RETURN NEW;
	END IF;
	
	IF	EXISTS (
		SELECT	1
		FROM	order_lines
		WHERE	product_id = NEW.id
		)
	THEN
		RAISE EXCEPTION 'Cannot trash products.id = %. It is referenced in order_lines.product_id.', NEW.id;
	END IF;
	
	RETURN NEW;
END;
$$ LANGUAGE plpgsql;

CREATE CONSTRAINT TRIGGER products_30_check_trash
	AFTER UPDATE ON products
FOR EACH ROW EXECUTE PROCEDURE products_check_trash();

/**
 * Auto-creates a promo for new products.
 */
CREATE OR REPLACE FUNCTION products_create_promo()
	RETURNS trigger
AS $$
BEGIN
	INSERT INTO campaigns (
		uuid,
		status,
		name,
		product_id,
		promo_id
		)
	VALUES (
		NEW.uuid,
		CASE
		WHEN NEW.status = 'trash'
		THEN 'trash'
		WHEN NEW.status = 'draft'
		THEN 'draft'
		WHEN NEW.status = 'pending'
		THEN 'pending'
		ELSE 'inactive'
		END::status_activatable,
		'Promo on ' || NEW.name,
		NEW.id,
		NEW.id
		);
	
	RETURN NEW;
END;
$$ LANGUAGE plpgsql;

CREATE TRIGGER products_10_create_promo
	AFTER INSERT ON products
FOR EACH ROW EXECUTE PROCEDURE products_create_promo();

/**
 * Process coupons when a product's status changes
 */
CREATE OR REPLACE FUNCTION products_update_status()
	RETURNS trigger
AS $$
BEGIN
	IF	NEW.status = OLD.status
	THEN
		RETURN NEW;
	END IF;
	
	IF	NEW.status < 'future'
	THEN
		-- Product was active but no longer is
		UPDATE	campaigns
		SET		product_id = CASE
					WHEN promo_id = NEW.id
					THEN product_id
					ELSE NULL
				END,
				status = CASE
					WHEN promo_id = NEW.id
					THEN CASE
						WHEN NEW.status = 'trash'
						THEN 'trash'
						WHEN NEW.status = 'draft'
						THEN 'draft'
						WHEN NEW.status = 'pending'
						THEN 'pending'
						ELSE 'inactive'
					END
					WHEN status = 'trash'
					THEN 'trash'
					ELSE 'active'
				END::status_activatable
		WHERE	product_id = NEW.id
		AND		( product_id IS DISTINCT FROM CASE
					WHEN promo_id = NEW.id
					THEN product_id
					ELSE NULL
				END OR
				status <> CASE
					WHEN promo_id = NEW.id
					THEN CASE
						WHEN NEW.status = 'trash'
						THEN 'trash'
						WHEN NEW.status = 'draft'
						THEN 'draft'
						WHEN NEW.status = 'pending'
						THEN 'pending'
						ELSE 'inactive'
					END
					WHEN status = 'trash'
					THEN 'trash'
					ELSE 'active'
				END::status_activatable );
	ELSE
		UPDATE	campaigns
		SET		status = CASE
					WHEN promo_id IS NOT NULL AND status = 'trash'
					THEN 'inactive' -- Untrash the promo
					WHEN status = 'future' AND launch >= NOW()::datetime
					THEN 'active'
					ELSE status
				END::status_activatable
		WHERE	product_id = NEW.id
		AND 	status <> CASE
					WHEN promo_id IS NOT NULL AND status = 'trash'
					THEN 'inactive' -- Untrash the promo
					WHEN status = 'future' AND launch >= NOW()::datetime
					THEN 'active'
					ELSE status
				END::status_activatable;
	END IF;
	
	-- RAISE NOTICE '%, %', TG_NAME, FOUND;
	
	RETURN NEW;
END;
$$ LANGUAGE plpgsql;

CREATE TRIGGER products_10_update_status
	AFTER UPDATE ON products
FOR EACH ROW EXECUTE PROCEDURE products_update_status();

/**
 * Refreshes coupon discounts on product updates.
 */
CREATE OR REPLACE FUNCTION products_update_price()
	RETURNS trigger
AS $$
BEGIN
	IF	ROW(NEW.init_price, NEW.rec_price) = ROW(OLD.init_price, OLD.rec_price) AND
		ROW(NEW.init_comm, NEW.rec_comm) = ROW(OLD.init_comm, OLD.rec_comm)
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
				WHEN init_discount = 0 OR
					NEW.init_price = 0 OR OLD.init_price = 0 OR
					aff_id IS NOT NULL AND ( NEW.init_comm = 0 OR OLD.init_comm = 0 )
				THEN 0
				WHEN aff_id IS NOT NULL
				THEN LEAST(CASE
					WHEN init_discount = OLD.init_comm
					THEN NEW.init_comm
					ELSE round(init_discount * NEW.init_comm / OLD.init_comm, 2)
					END, NEW.init_comm)
				ELSE LEAST(CASE
					WHEN NEW.init_comm = OLD.init_comm
					THEN init_discount
					WHEN init_discount = OLD.init_comm
					THEN NEW.init_comm
					WHEN init_discount = OLD.init_price
					THEN NEW.init_price
					ELSE round(init_discount * NEW.init_price / OLD.init_price, 2)
					END, NEW.init_price - NEW.init_comm)
			END,
			rec_discount = CASE
				WHEN rec_discount = 0 OR
					NEW.rec_price = 0 OR OLD.rec_price = 0 OR
					aff_id IS NOT NULL AND ( NEW.rec_comm = 0 OR OLD.rec_comm = 0 )
				THEN 0
				WHEN aff_id IS NOT NULL
				THEN LEAST(CASE
					WHEN rec_discount = OLD.rec_comm
					THEN NEW.rec_comm
					ELSE round(rec_discount * NEW.rec_comm / OLD.rec_comm, 2)
					END, NEW.rec_comm)
				ELSE LEAST(CASE
					WHEN NEW.rec_comm = OLD.rec_comm
					THEN rec_discount
					WHEN rec_discount = OLD.rec_comm
					THEN NEW.rec_comm
					WHEN rec_discount = OLD.rec_price
					THEN NEW.rec_price
					ELSE round(rec_discount * NEW.rec_price / OLD.rec_price, 2)
					END, NEW.rec_price - NEW.rec_comm)
			END
	WHERE	product_id = NEW.id
	AND		( status <> CASE
				WHEN aff_id IS NOT NULL AND status IN ('active', 'future')
				THEN 'pending'
				ELSE status
			END OR
			init_discount <> CASE
				WHEN init_discount = 0 OR
					NEW.init_price = 0 OR OLD.init_price = 0 OR
					aff_id IS NOT NULL AND ( NEW.init_comm = 0 OR OLD.init_comm = 0 )
				THEN 0
				WHEN aff_id IS NOT NULL
				THEN LEAST(CASE
					WHEN init_discount = OLD.init_comm
					THEN NEW.init_comm
					ELSE round(init_discount * NEW.init_comm / OLD.init_comm, 2)
					END, NEW.init_comm)
				ELSE LEAST(CASE
					WHEN NEW.init_comm = OLD.init_comm
					THEN init_discount
					WHEN init_discount = OLD.init_comm
					THEN NEW.init_comm
					WHEN init_discount = OLD.init_price
					THEN NEW.init_price
					ELSE round(init_discount * NEW.init_price / OLD.init_price, 2)
					END, NEW.init_price - NEW.init_comm)
			END OR
			rec_discount <> CASE
				WHEN rec_discount = 0 OR
					NEW.rec_price = 0 OR OLD.rec_price = 0 OR
					aff_id IS NOT NULL AND ( NEW.rec_comm = 0 OR OLD.rec_comm = 0 )
				THEN 0
				WHEN aff_id IS NOT NULL
				THEN LEAST(CASE
					WHEN rec_discount = OLD.rec_comm
					THEN NEW.rec_comm
					ELSE round(rec_discount * NEW.rec_comm / OLD.rec_comm, 2)
					END, NEW.rec_comm)
				ELSE LEAST(CASE
					WHEN NEW.rec_comm = OLD.rec_comm
					THEN rec_discount
					WHEN rec_discount = OLD.rec_comm
					THEN NEW.rec_comm
					WHEN rec_discount = OLD.rec_price
					THEN NEW.rec_price
					ELSE round(rec_discount * NEW.rec_price / OLD.rec_price, 2)
					END, NEW.rec_price - NEW.rec_comm)
			END );
	
	-- RAISE NOTICE '%, %', TG_NAME, FOUND;
	
	RETURN NEW;
END;
$$ LANGUAGE plpgsql;

CREATE TRIGGER products_20_update_price
	AFTER UPDATE ON products
FOR EACH ROW EXECUTE PROCEDURE products_update_price();