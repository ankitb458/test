/**
 * Checks integrity when a user is trashed.
 */
CREATE OR REPLACE FUNCTION users_trash()
	RETURNS trigger
AS $$
BEGIN
	IF NEW.status = OLD.status OR NEW.status <> 'trash'
	THEN
		RETURN NEW;
	END IF;
	
	IF	EXISTS (
		SELECT	1
		FROM	campaigns
		WHERE	aff_id = NEW.id
		)
	THEN
		RAISE EXCEPTION 'users.id = % is referenced in campaigns.', NEW.id;
	END IF;
	
	IF	EXISTS (
		SELECT	1
		FROM	orders
		WHERE	user_id = NEW.id
		)
	THEN
		RAISE EXCEPTION 'users.id = % is referenced in orders.', NEW.id;
	END IF;
	
	IF	EXISTS (
		SELECT	1
		FROM	order_lines
		WHERE	user_id = NEW.id
		)
	THEN
		RAISE EXCEPTION 'users.id = % is referenced in order_lines.', NEW.id;
	END IF;
	
	RETURN NEW;
END;
$$ LANGUAGE plpgsql;

CREATE TRIGGER users_10_trash
	AFTER UPDATE ON users
FOR EACH ROW EXECUTE PROCEDURE users_trash();