/**
 * Sets a default name when needed.
 */
CREATE OR REPLACE FUNCTION invoices_sanitize_name()
	RETURNS trigger
AS $$
BEGIN
	-- Default name
	IF	NEW.name IS NULL
	THEN
		NEW.name = CASE
			WHEN NEW.invoice_type = 'expense'
			THEN 'Commissions'
			ELSE 'Order'
			END;
	END IF;
	
	RETURN NEW;
END;
$$ LANGUAGE plpgsql;

CREATE TRIGGER invoices_05_sanitize_name
	BEFORE INSERT OR UPDATE ON invoices
FOR EACH ROW EXECUTE PROCEDURE invoices_sanitize_name();

/**
 * Autofills the user id when possible
 */
CREATE OR REPLACE FUNCTION invoices_sanitize_user_id()
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

CREATE TRIGGER invoices_02_sanitize_user_id
	BEFORE INSERT ON invoices
FOR EACH ROW EXECUTE PROCEDURE invoices_sanitize_user_id();

/**
 * Auto-assigns an issue date when needed
 */
CREATE OR REPLACE FUNCTION invoices_sanitize_issue_date()
	RETURNS trigger
AS $$
BEGIN
	IF	NEW.status > 'draft' AND NEW.issue_date IS NULL
	THEN
		NEW.issue_date := NOW();
	END IF;
	
	RETURN NEW;
END $$ LANGUAGE plpgsql;

CREATE TRIGGER invoices_10_sanitize_issue_date
	BEFORE INSERT OR UPDATE ON invoices
FOR EACH ROW EXECUTE PROCEDURE invoices_sanitize_issue_date();

/**
 * Autofills an order's invoice
 */
CREATE OR REPLACE FUNCTION invoices_insert_lines()
	RETURNS trigger
AS $$
BEGIN
	IF	NEW.order_id IS NULL
	THEN
		RETURN NEW;
	END IF;
	
	INSERT INTO invoice_lines (
			status,
			invoice_id,
			order_line_id,
			parent_id,
			amount
			)
	SELECT	NEW.status,
			NEW.id,
			order_lines.id,
			invoice_lines.id,
			CASE
			WHEN invoice_lines.parent_id IS NULL
			THEN quantity * ( init_price - init_discount )
			ELSE quantity * ( rec_price - rec_discount )
			END
	FROM	order_lines
	LEFT JOIN invoice_lines
	ON		invoice_lines.order_line_id = order_lines.id
	AND		invoice_lines.parent_id IS NULL
	WHERE	order_lines.order_id = NEW.order_id
	AND		invoice_lines.id IS NULL AND order_lines.status IN ('draft', 'pending');
	
	RETURN NEW;
END $$ LANGUAGE plpgsql;

CREATE TRIGGER invoices_10_insert_lines
	AFTER INSERT ON invoices
FOR EACH ROW EXECUTE PROCEDURE invoices_insert_lines();

/**
 * Delegates status changes to invoices into invoice lines
 */
CREATE OR REPLACE FUNCTION invoices_propagate_status()
	RETURNS trigger
AS $$
BEGIN
	IF	NEW.status = OLD.status
	THEN
		RETURN NEW;
	END IF;
	
	UPDATE	invoice_lines
	SET		status = NEW.status
	WHERE	invoice_id = NEW.id
	AND		status = OLD.status
	AND		status <> NEW.status;
	
	RETURN NEW;
END $$ LANGUAGE plpgsql;

CREATE TRIGGER invoices_10_propagate_status
	AFTER UPDATE ON invoices
FOR EACH ROW EXECUTE PROCEDURE invoices_propagate_status();