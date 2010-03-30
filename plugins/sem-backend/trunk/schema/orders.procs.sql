/**
 * Checks integrity when an order is trashed.
 */
CREATE OR REPLACE FUNCTION orders_check_trash()
	RETURNS trigger
AS $$
BEGIN
	IF NEW.status = OLD.status OR NEW.status <> 'trash'
	THEN
		RETURN NEW;
	END IF;
	
	IF	EXISTS (
		SELECT	1
		FROM	order_lines
		WHERE	order_id = NEW.id -- cascade updated
		)
	THEN
		RAISE EXCEPTION 'Cannot delete orders.id = %: it is referenced in order_lines.order_id.', NEW.id;
	END IF;
	
	RETURN NEW;
END;
$$ LANGUAGE plpgsql;

CREATE TRIGGER orders_01_check_trash
	AFTER UPDATE ON orders
FOR EACH ROW EXECUTE PROCEDURE orders_check_trash();
