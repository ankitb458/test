/**
 * Sanitizes an orders's campaign.
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

CREATE TRIGGER orders_20_delegate_aff_id
	AFTER UPDATE ON orders
FOR EACH ROW EXECUTE PROCEDURE orders_delegate_aff_id();