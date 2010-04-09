/**
 * Recalculates a invoice's status and due amount based on transaction lines
 */
CREATE OR REPLACE FUNCTION invoice_lines_delegate_invoice_details()
	RETURNS trigger
AS $$
DECLARE
	_status		status_payable;
	_amount		numeric(8,2);
BEGIN
	IF	TG_OP = 'UPDATE'
	THEN
		IF	ROW(NEW.status, NEW.amount) = ROW(OLD.status, OLD.amount)
		THEN
			RETURN NEW;
		END IF;
	END IF;
	
	SELECT	MAX(status),
			SUM(CASE
			WHEN status IN ('pending', 'cleared')
			THEN amount
			ELSE 0
			END)
	INTO	_status,
			_amount
	FROM	invoice_lines
	WHERE	invoice_id = NEW.invoice_id;
	
	_status := COALESCE(_status, 'trash');
	_amount := COALESCE(_amount, 0);
	
	UPDATE	invoices
	SET		status = _status,
			due_amount = _amount
	WHERE	id = NEW.invoice_id
	AND		( status <> _status OR due_amount <> _amount );
	
	-- RAISE NOTICE '%, %', TG_NAME, FOUND;
	
	RETURN NEW;
END $$ LANGUAGE plpgsql;

CREATE TRIGGER invoice_lines_10_delegate_invoice_details
	AFTER INSERT OR UPDATE ON invoice_lines
FOR EACH ROW EXECUTE PROCEDURE invoice_lines_delegate_invoice_details();

/**
 * Delegates the status for order lines
 */
CREATE OR REPLACE FUNCTION invoice_lines_delegate_order_details()
	RETURNS trigger
AS $$
DECLARE
	_status		status_payable;
BEGIN
	IF	NEW.order_line_id IS NULL
	THEN
		RETURN NEW;
	ELSEIF TG_OP = 'UPDATE'
	THEN
		IF	NEW.status = OLD.status
		THEN
			RETURN NEW;
		END IF;
	END IF;
	
	IF	EXISTS(
		SELECT	1
		FROM	invoices
		WHERE	id = NEW.invoice_id
		AND		order_id IS NOT NULL
		)
	THEN
		SELECT	MIN(invoice_lines.status)
		INTO	_status
		FROM	invoice_lines
		JOIN	invoices
		ON		invoices.id = invoice_lines.invoice_id
		WHERE	invoice_lines.order_line_id = NEW.order_line_id
		AND		invoices.invoice_type = 'revenue'
				-- Ignore drafts and pending invoices unless it's the initial one
		AND		( invoices.status > 'pending' OR invoice_lines.parent_id IS NULL );
		
		UPDATE	order_lines
		SET		status = _status
		WHERE	id = NEW.order_line_id
		AND		status <> _status;
		
		-- RAISE NOTICE '%, %', TG_NAME, FOUND;
	END IF;
	
	RETURN NEW;
END $$ LANGUAGE plpgsql;

CREATE TRIGGER invoice_lines_20_delegate_order_details
	AFTER INSERT OR UPDATE ON invoice_lines
FOR EACH ROW EXECUTE PROCEDURE invoice_lines_delegate_order_details();

/*
	-- Extract the commission due date
	_due_date := date_trunc('month', NEW.cleared_date + interval '1 month + 2 week');
	IF	_due_date - NEW.cleared_date < interval '30 day' -- 30 day minimum
	THEN
		_due_date := _due_date + interval '2 week';
	END IF;
*/