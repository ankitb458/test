/**
 * Checks integrity when a user is trashed.
 */
CREATE OR REPLACE FUNCTION users_check_trash()
	RETURNS trigger
AS $$
BEGIN
	IF NEW.status = OLD.status OR NEW.status <> 'trash'
	THEN
		RETURN NEW;
	END IF;
	
	IF	EXISTS (
		SELECT	1
		FROM	users
		WHERE	ref_id = NEW.id -- cascade updated
		)
	THEN
		RAISE EXCEPTION 'Cannot delete users.id = %: it is referenced in users.ref_id.', NEW.id;
	END IF;
	
	IF	EXISTS (
		SELECT	1
		FROM	campaigns
		WHERE	aff_id = NEW.id -- cascade updated
		)
	THEN
		RAISE EXCEPTION 'Cannot delete users.id = %: it is referenced in campaigns.aff_id.', NEW.id;
	END IF;
	
	IF	EXISTS (
		SELECT	1
		FROM	orders
		WHERE	user_id = NEW.id -- cascade updated
		)
	THEN
		RAISE EXCEPTION 'Cannot delete users.id = %: it is referenced in orders.user_id.', NEW.id;
	END IF;
	
	IF	EXISTS (
		SELECT	1
		FROM	orders
		WHERE	aff_id = NEW.id -- cascade updated
		)
	THEN
		RAISE EXCEPTION 'Cannot delete users.id = %: it is referenced in orders.aff_id.', NEW.id;
	END IF;
	
	IF	EXISTS (
		SELECT	1
		FROM	order_lines
		WHERE	user_id = NEW.id -- cascade updated
		)
	THEN
		RAISE EXCEPTION 'Cannot delete users.id = %: it is referenced in order_lines.user_id.', NEW.id;
	END IF;
	
	RETURN NEW;
END;
$$ LANGUAGE plpgsql;

CREATE TRIGGER users_01_check_trash
	AFTER UPDATE ON users
FOR EACH ROW EXECUTE PROCEDURE users_check_trash();