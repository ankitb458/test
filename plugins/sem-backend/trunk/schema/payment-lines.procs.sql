/**
 * Recalculates a payment's status and due amount based on transaction lines
 */
CREATE OR REPLACE FUNCTION payment_lines_delegate_payment_details()
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
	FROM	payment_lines
	WHERE	payment_id = NEW.payment_id;
	
	UPDATE	payments
	SET		status = _status,
			due_amount = _amount
	WHERE	id = NEW.payment_id
	AND		( status <> _status OR due_amount <> _amount );
	
	-- RAISE NOTICE '%, %', TG_NAME, FOUND;
	
	RETURN NEW;
END $$ LANGUAGE plpgsql;

CREATE TRIGGER payment_lines_10_delegate_payment_details
	AFTER INSERT OR UPDATE ON payment_lines
FOR EACH ROW EXECUTE PROCEDURE payment_lines_delegate_payment_details();

/**
 * Delegates the status for order lines
 */
CREATE OR REPLACE FUNCTION payment_lines_delegate_order_details()
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
		FROM	payments
		WHERE	id = NEW.payment_id
		AND		payment_type = 'order'
		)
	THEN
		SELECT	MIN(payment_lines.status)
		INTO	_status
		FROM	payment_lines
		JOIN	payments
		ON		payments.id = payment_lines.payment_id
		AND		payments.payment_type = 'order'
		WHERE	payment_lines.order_line_id = NEW.order_line_id;

		UPDATE	order_lines
		SET		status = _status
		WHERE	id = NEW.order_line_id
		AND		status <> _status;
	END IF;
	
	RETURN NEW;
END $$ LANGUAGE plpgsql;

CREATE TRIGGER payment_lines_20_delegate_order_details
	AFTER INSERT OR UPDATE ON payment_lines
FOR EACH ROW EXECUTE PROCEDURE payment_lines_delegate_order_details();

/*
	-- Extract the commission due date
	_due_date := date_trunc('month', NEW.cleared_date + interval '1 month + 2 week');
	IF	_due_date - NEW.cleared_date < interval '30 day' -- 30 day minimum
	THEN
		_due_date := _due_date + interval '2 week';
	END IF;
*/