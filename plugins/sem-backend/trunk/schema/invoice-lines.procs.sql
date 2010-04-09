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
			WHEN status IN ('draft', 'pending', 'cleared')
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
CREATE OR REPLACE FUNCTION invoice_lines_delegate_order_line_details()
	RETURNS trigger
AS $$
DECLARE
	_status		status_payable;
	_offset		int := 0;
	_do_comm	boolean;
	_due_date	datetime;
	_aff_id		bigint;
	_comm		numeric(8,2);
	_comm_id	bigint;
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
	
	IF	NOT EXISTS(
		SELECT	1
		FROM	invoices
		WHERE	id = NEW.invoice_id
		AND		invoice_type = 'revenue'
		)
	THEN
		RETURN NEW;
	END IF;
	
	SELECT	MIN(invoice_lines.status)
	INTO	_status
	FROM	invoice_lines
	JOIN	invoices
	ON		invoices.id = invoice_lines.invoice_id
	WHERE	invoice_lines.order_line_id = NEW.order_line_id
	AND		invoices.invoice_type = 'revenue'
			-- Ignore drafts and pending invoices unless it's the initial one
	AND		( invoices.status NOT IN ('trash', 'draft', 'pending') OR invoice_lines.parent_id IS NULL );
	
	IF	TG_OP = 'INSERT'
	THEN
		IF	NEW.status = 'cleared'
		THEN
			_do_comm := TRUE;
			
			IF	NEW.parent_id IS NOT NULL
			THEN
				_offset := -1;
			END IF;
		END IF;
	ELSE
		IF	NEW.status = 'cleared' AND OLD.status <> 'cleared'
		THEN
			_do_comm := TRUE;
			
			IF	NEW.parent_id IS NOT NULL
			THEN
				_offset := -1;
			END IF;
		ELSEIF NEW.status <> 'cleared' AND OLD.status = 'cleared'
		THEN
			_do_comm := FALSE;
			
			IF	NEW.parent_id IS NOT NULL
			THEN
				_offset := 1;
			END IF;
		END IF;
	END IF;
	
	UPDATE	order_lines
	SET		status = _status,
			rec_count = rec_count + _offset
	WHERE	id = NEW.order_line_id
	AND		status <> _status;
		
	-- RAISE NOTICE '%, %', TG_NAME, FOUND;
	
	IF	_do_comm IS NULL
	THEN
		RETURN NEW;
	END IF;
	
	SELECT	CASE
			WHEN NEW.parent_id IS NULL
			THEN quantity * init_comm
			ELSE quantity * rec_comm
			END
	INTO	_comm
	FROM	order_lines
	WHERE	id = NEW.order_line_id;
	
	IF	_comm = 0
	THEN
		RETURN NEW;
	END IF;
	
	IF	NOT _do_comm
	THEN
		-- Cancel all commissions related to that transaction and exit
		UPDATE	invoice_lines
		SET		status = 'cancelled'
		FROM	invoices
		WHERE	invoices.id = invoice_lines.invoice_id
		AND		invoices.invoice_type = 'expense'
		AND		invoice_lines.order_line_id = NEW.order_line_id
		AND		invoice_lines.parent_id = NEW.id
		AND		invoice_lines.status NOT IN ('trash', 'cancelled');
		
		RETURN NEW;
	END IF;
	
	-- Extract the aff_id and the commission due date
	SELECT	orders.aff_id,
			CASE
			WHEN date_trunc('month', invoices.cleared_date + interval '1 month + 2 week')
				- invoices.cleared_date < interval '30 day'
			THEN date_trunc('month', invoices.cleared_date + interval '1 month + 2 week')
				+ interval '2 week'
			ELSE date_trunc('month', invoices.cleared_date + interval '1 month + 2 week')
			END
	INTO	_aff_id,
			_due_date
	FROM	invoices
	LEFT JOIN orders
	ON		orders.id = invoices.order_id
	WHERE	invoices.id = NEW.invoice_id;
	
	IF	_aff_id IS NOT NULL
	THEN
		SELECT	id
		INTO	_comm_id
		FROM	invoices
		WHERE	due_date = _due_date
		AND		invoice_type = 'expense'
		AND		user_id = _aff_id;
	ELSE
		SELECT	id
		INTO	_comm_id
		FROM	invoices
		WHERE	due_date = _due_date
		AND		invoice_type = 'expense'
		AND		user_id IS NULL;
	END IF;
	
	IF	NOT FOUND
	THEN
		INSERT INTO invoices (
				status,
				invoice_type,
				user_id,
				due_date
				)
		VALUES	(
				'pending',
				'expense',
				_aff_id,
				_due_date
				)
		RETURNING id
		INTO	_comm_id;
		
		INSERT INTO invoice_lines (
				status,
				invoice_id,
				order_line_id,
				parent_id,
				amount
				)
		VALUES (
				'pending',
				_comm_id,
				NEW.order_line_id,
				NEW.id,
				_comm
				);
	ELSE
		-- Try an update first
		UPDATE	invoice_lines
		SET		status = 'pending',
				amount = _comm
		WHERE	invoice_id = _comm_id
		AND		order_line_id = NEW.order_line_id
		AND		parent_id = NEW.id;
		
		IF	NOT FOUND
		THEN
			INSERT INTO invoice_lines (
					status,
					invoice_id,
					order_line_id,
					parent_id,
					amount
					)
			VALUES (
					'pending',
					_comm_id,
					NEW.order_line_id,
					NEW.id,
					_comm
					);
		END IF;
	END IF;
	
	RETURN NEW;
END $$ LANGUAGE plpgsql;

CREATE TRIGGER invoice_lines_20_delegate_order_line_details
	AFTER INSERT OR UPDATE ON invoice_lines
FOR EACH ROW EXECUTE PROCEDURE invoice_lines_delegate_order_line_details();