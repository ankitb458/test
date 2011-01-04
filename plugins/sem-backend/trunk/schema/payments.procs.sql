/**
 * Sets a default name when needed.
 */
CREATE OR REPLACE FUNCTION payments_sanitize_name()
	RETURNS trigger
AS $$
BEGIN
	-- Default name
	IF	NEW.name IS NULL
	THEN
		NEW.name = CASE
			WHEN NEW.payment_type = 'expense'
			THEN 'Commissions'
			ELSE 'Order'
			END;
	END IF;
	
	RETURN NEW;
END;
$$ LANGUAGE plpgsql;

CREATE TRIGGER payments_05_sanitize_name
	BEFORE INSERT OR UPDATE ON payments
FOR EACH ROW EXECUTE PROCEDURE payments_sanitize_name();

/**
 * Autofills the user id when possible
 */
CREATE OR REPLACE FUNCTION payments_sanitize_user_id()
	RETURNS trigger
AS $$
BEGIN
	IF	NEW.user_id IS NOT NULL OR NEW.order_id IS NULL
	THEN
		RETURN NEW;
	END IF;
	
	SELECT	orders.user_id
	INTO	NEW.user_id
	FROM	orders
	WHERE	id = NEW.order_id;
	
	RETURN NEW;
END $$ LANGUAGE plpgsql;

CREATE TRIGGER payments_02_sanitize_user_id
	BEFORE INSERT ON payments
FOR EACH ROW EXECUTE PROCEDURE payments_sanitize_user_id();

/**
 * Auto-assigns an issue date when needed
 */
CREATE OR REPLACE FUNCTION payments_sanitize_issued_date()
	RETURNS trigger
AS $$
BEGIN
	IF	NEW.status > 'draft' AND NEW.issued_date IS NULL
	THEN
		NEW.issued_date := NOW();
	END IF;
	
	RETURN NEW;
END $$ LANGUAGE plpgsql;

CREATE TRIGGER payments_10_sanitize_issued_date
	BEFORE INSERT OR UPDATE ON payments
FOR EACH ROW EXECUTE PROCEDURE payments_sanitize_issued_date();

/**
 * Autofills an order's payment
 */
CREATE OR REPLACE FUNCTION payments_insert_lines()
	RETURNS trigger
AS $$
BEGIN
	IF	NEW.order_id IS NULL
	THEN
		RETURN NEW;
	END IF;
	
	INSERT INTO payment_lines (
			status,
			payment_id,
			order_line_id,
			parent_id,
			amount
			)
	SELECT	NEW.status,
			NEW.id,
			order_lines.id,
			payment_lines.id,
			CASE
			WHEN payment_lines.parent_id IS NULL
			THEN quantity * ( init_price - init_discount )
			ELSE quantity * ( rec_price - rec_discount )
			END
	FROM	order_lines
	LEFT JOIN payment_lines
	ON		payment_lines.order_line_id = order_lines.id
	AND		payment_lines.parent_id IS NULL
	WHERE	order_lines.order_id = NEW.order_id
	AND		payment_lines.id IS NULL AND order_lines.status IN ('draft', 'pending');
	
	RETURN NEW;
END $$ LANGUAGE plpgsql;

CREATE TRIGGER payments_10_insert_lines
	AFTER INSERT ON payments
FOR EACH ROW EXECUTE PROCEDURE payments_insert_lines();

/**
 * Delegates status changes to payments into payment lines
 */
CREATE OR REPLACE FUNCTION payments_propagate_status()
	RETURNS trigger
AS $$
BEGIN
	IF	NEW.status = OLD.status
	THEN
		RETURN NEW;
	END IF;
	
	UPDATE	payment_lines
	SET		status = NEW.status
	WHERE	payment_id = NEW.id
	AND		status = OLD.status
	AND		status <> NEW.status;
	
	RETURN NEW;
END $$ LANGUAGE plpgsql;

CREATE TRIGGER payments_10_propagate_status
	AFTER UPDATE ON payments
FOR EACH ROW EXECUTE PROCEDURE payments_propagate_status();