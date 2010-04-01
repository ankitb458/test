/**
 * Checks integrity when an order is trashed.
 */
CREATE OR REPLACE FUNCTION orders_check_trash()
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
		WHERE	order_id = NEW.id -- cascade updated
		AND		status > 'draft'
		)
	THEN
		RAISE EXCEPTION 'Cannot trash orders.id = %. It is referenced in order_lines.order_id.', NEW.id;
	END IF;
	
	RETURN NEW;
END;
$$ LANGUAGE plpgsql;

CREATE CONSTRAINT TRIGGER orders_30_check_trash
	AFTER UPDATE ON orders
FOR EACH ROW EXECUTE PROCEDURE orders_check_trash();

/**
 * Sanitizes an orders's campaign.
 */
CREATE OR REPLACE FUNCTION orders_sanitize_campaign_id()
	RETURNS trigger
AS $$
DECLARE
	a_id		bigint;
BEGIN
	IF	NEW.campaign_id IS NULL
	THEN
		RETURN NEW;
	ELSEIF TG_OP = 'UPDATE'
	THEN
		IF	NEW.campaign_id IS NOT DISTINCT FROM OLD.campaign_id
		THEN
			RETURN NEW;
		END IF;
	END IF;
	
	SELECT	aff_id
	INTO	a_id
	FROM	campaigns
	WHERE	id = NEW.campaign_id
	AND		status > 'draft';
	
	IF	NOT FOUND
	THEN
		RAISE EXCEPTION 'Cannot tie inactive campaigns.id = % to orders.id = %.',
			NEW.campaign_id, NEW.id;
	ELSEIF TG_OP = 'INSERT'
	THEN
		-- auto-correct aff_id on inserts
		NEW.aff_id := a_id;
	END IF;
	
	RETURN NEW;
END $$ LANGUAGE plpgsql;

CREATE TRIGGER orders_02_sanitize_campaign_id
	BEFORE INSERT OR UPDATE ON orders
FOR EACH ROW EXECUTE PROCEDURE orders_sanitize_campaign_id();

/**
 * Sanitizes an orders's billing user.
 */
CREATE OR REPLACE FUNCTION orders_sanitize_user_id()
	RETURNS trigger
AS $$
DECLARE
	u_id		bigint;
BEGIN
	IF	NEW.user_id IS NULL
	THEN
		RETURN NEW;
	ELSEIF TG_OP = 'UPDATE'
	THEN
		IF	NEW.user_id IS NOT DISTINCT FROM OLD.user_id
		THEN
			RETURN NEW;
		END IF;
	END IF;
	
	IF	NOT EXISTS (
		SELECT	1
		FROM	users
		WHERE	id = NEW.user_id
		AND		status > 'pending'
		)
	THEN
		RAISE EXCEPTION 'Cannot tie inactive users.id = % to orders.id = %.',
			NEW.user_id, NEW.id;
	END IF;
	
	RETURN NEW;
END $$ LANGUAGE plpgsql;

CREATE TRIGGER orders_02_sanitize_user_id
	BEFORE INSERT OR UPDATE ON orders
FOR EACH ROW EXECUTE PROCEDURE orders_sanitize_user_id();

/**
 * Sanitizes an orders's affiliate.
 */
CREATE OR REPLACE FUNCTION orders_sanitize_aff_id()
	RETURNS trigger
AS $$
DECLARE
	u_id		bigint;
BEGIN
	IF	NEW.aff_id IS NULL
	THEN
		RETURN NEW;
	ELSEIF TG_OP = 'UPDATE'
	THEN
		IF	NEW.aff_id IS NOT DISTINCT FROM OLD.aff_id
		THEN
			RETURN NEW;
		END IF;
	END IF;
	
	IF	NOT EXISTS (
		SELECT	1
		FROM	users
		WHERE	id = NEW.aff_id
		AND		status > 'pending'
		)
	THEN
		RAISE EXCEPTION 'Cannot tie inactive users.id = % to orders.id = %.',
			NEW.aff_id, NEW.id;
	END IF;
	
	RETURN NEW;
END $$ LANGUAGE plpgsql;

CREATE TRIGGER orders_02_sanitize_aff_id
	BEFORE INSERT OR UPDATE ON orders
FOR EACH ROW EXECUTE PROCEDURE orders_sanitize_aff_id();

/**
 * Delegates status handling on orders
 */
CREATE OR REPLACE FUNCTION orders_delegate_status()
	RETURNS trigger
AS $$
BEGIN
	IF	NEW.status = OLD.status OR
		NEW.status > 'pending' AND OLD.status > 'pending' -- Undefined behavior
	THEN
		RETURN NEW;
	END IF;
	
	UPDATE	order_lines
	SET		status = NEW.status
	WHERE	order_id = NEW.id
	AND		status = OLD.status;
	
	-- RAISE NOTICE '%, %', TG_NAME, FOUND;
	
	RETURN NEW;
END $$ LANGUAGE plpgsql;

CREATE TRIGGER orders_10_delegate_status
	AFTER UPDATE ON orders
FOR EACH ROW EXECUTE PROCEDURE orders_delegate_status();

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

CREATE TRIGGER orders_20_delegate_aff_id
	AFTER UPDATE ON orders
FOR EACH ROW EXECUTE PROCEDURE orders_delegate_aff_id();