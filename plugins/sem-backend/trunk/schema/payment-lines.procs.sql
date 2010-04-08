/**
 * Sanitizes a payment line's amounts based on the order line, if any
 */
CREATE OR REPLACE FUNCTION payment_lines_sanitize_financials()
	RETURNS trigger
AS $$
DECLARE
	_order_line	record;
	_user		record;
BEGIN
	-- Payment id and type
	IF	NEW.payment_id IS NOT NULL
	THEN
		IF	NEW.payment_type IS NULL
		THEN
			SELECT	payment_type
			INTO	NEW.payment_type
			FROM	payments
			WHERE	id = NEW.payment_id;
		END IF;
	ELSEIF NEW.payment_id IS NULL
	THEN
		IF	NEW.payment_type IS NOT NULL
		THEN
			INSERT INTO payments ( payment_type )
			VALUES ( NEW.payment_type )
			RETURNING id
			INTO	NEW.payment_id;
		ELSE
			INSERT INTO payments
			DEFAULT VALUES
			RETURNING id,
					payment_type
			INTO	NEW.payment_id,
					NEW.payment_type;
		END IF;
	END IF;
	
	-- Order line / Due amount
	IF	NEW.order_line_id IS NULL
	THEN
		NEW.due_amount := COALESCE(NEW.due_amount, 0);
	ELSEIF NEW.due_amount IS NULL
	THEN
		SELECT	CASE
				WHEN NEW.payment_type = 'order' AND NOT NEW.recurring
				THEN init_amount
				WHEN NEW.payment_type = 'comm' AND NOT NEW.recurring
				THEN init_comm
				WHEN NEW.payment_type = 'order' AND NEW.recurring
				THEN rec_amount
				WHEN NEW.payment_type = 'comm' AND NEW.recurring
				THEN rec_comm
				ELSE NULL -- undefined behavior
				END,
				CASE
				WHEN NEW.payment_type = 'order'
				THEN orders.user_id
				WHEN NEW.payment_type = 'comm'
				THEN orders.aff_id
				ELSE NULL -- undefined behavior
				END
		INTO	NEW.due_amount,
				NEW.user_id
		FROM	order_lines
		JOIN	orders
		ON		orders.id = order_lines.order_id
		WHERE	order_lines.id = NEW.order_line_id;
	END IF;
	
	-- Todo:
	-- Payment method / Due taxes
	IF	NEW.user_id IS NULL
	THEN
		NEW.payment_method := COALESCE(NEW.payment_method, 'misc');
		NEW.due_taxes := COALESCE(NEW.due_taxes, 0);
	ELSE
		NEW.payment_method := COALESCE(NEW.payment_method, 'paypal');
		NEW.due_taxes := COALESCE(NEW.due_taxes, 0);
	END IF;
	
	RETURN NEW;
END $$ LANGUAGE plpgsql;

CREATE TRIGGER payment_lines_02_sanitize_financials
	BEFORE INSERT OR UPDATE ON payment_lines
FOR EACH ROW EXECUTE PROCEDURE payment_lines_sanitize_financials();

/**
 * Forward status changes to payments
 */
CREATE OR REPLACE FUNCTION payment_lines_delegate_status()
	RETURNS trigger
AS $$
DECLARE
	new_status	status_payable;
BEGIN
	IF	TG_OP = 'UPDATE'
	THEN
		IF	ROW(NEW.status, NEW.payment_id) = ROW(OLD.status, OLD.payment_id)
		THEN
			RETURN NEW;
		ELSEIF NEW.payment_id <> OLD.payment_id
		THEN
			-- Also do this for the old payment
			SELECT	MAX(status)
			INTO	new_status
			FROM	payment_lines
			WHERE	payment_id = OLD.payment_id;
			
			new_status := COALESCE(new_status, 'trash');
			
			UPDATE	payments
			SET		status = new_status
			WHERE	id = OLD.payment_id
			AND		status <> new_status;
			
			-- RAISE NOTICE '%, %', TG_NAME, FOUND;
		END IF;
	END IF;
	
	SELECT	MAX(status)
	INTO	new_status
	FROM	payment_lines
	WHERE	payment_id = NEW.payment_id;
	
	UPDATE	payments
	SET		status = new_status
	WHERE	id = NEW.payment_id
	AND		status <> new_status;
	
	-- RAISE NOTICE '%, %', TG_NAME, FOUND;
	
	RETURN NEW;
END $$ LANGUAGE plpgsql;

CREATE TRIGGER payment_lines_10_delegate_status
	AFTER INSERT OR UPDATE ON payment_lines
FOR EACH ROW EXECUTE PROCEDURE payment_lines_delegate_status();

/**
 * Handles commissions when payments are cleared or cancelled
 */
CREATE OR REPLACE FUNCTION payment_lines_delegate_commissions()
	RETURNS trigger
AS $$
DECLARE
	_aff_id			bigint;
	_aff_comm		numeric;
	_payment_id		bigint;
	_due_date		datetime;
BEGIN
	IF	NEW.payment_type <> 'order' OR
		NEW.order_line_id IS NULL
	THEN
		RETURN NEW;
	ELSEIF TG_OP = 'UPDATE'
	THEN
		IF	NEW.status = OLD.status
		THEN
			RETURN NEW;
		ELSEIF OLD.status = 'cleared'
		THEN
			-- Todo:
			-- use transactions to process the diff between cleared and due amounts
			-- when a commission is paid too early and a refund occurs
			UPDATE	payment_lines
			SET		status = 'cancelled'
			WHERE	parent_id = NEW.id
			AND		payment_type = 'comm'
			AND		status = 'pending';
			
			RETURN NEW;
		END IF;
	END IF;
	
	IF	NEW.status <> 'cleared'
	THEN
		RETURN NEW;
	END IF;
	
	SELECT	aff_id,
			CASE
			WHEN NEW.recurring
			THEN rec_comm
			ELSE init_comm
			END
	INTO	_aff_id,
			_aff_comm
	FROM	orders
	JOIN	order_lines
	ON		order_id = orders.id
	WHERE	order_lines.id = NEW.order_line_id
	AND		aff_id IS NOT NULL
	AND		CASE
			WHEN NEW.recurring
			THEN rec_comm <> 0
			ELSE init_comm <> 0
			END;
	
	IF	NOT FOUND
	THEN
		RETURN NEW;
	END IF;
	
	-- Extract the commission due date
	_due_date := date_trunc('month', NEW.cleared_date + interval '1 month + 2 week');
	IF	_due_date - NEW.cleared_date < interval '30 day' -- 30 day minimum
	THEN
		_due_date := _due_date + interval '2 week';
	END IF;
	
	SELECT	id
	INTO	_payment_id
	FROM	payments
	WHERE	payment_type = 'comm'
	AND		due_date = _due_date
	AND		status = 'pending'
	LIMIT 1;
	
	IF NOT FOUND
	THEN
		INSERT INTO payments (
				payment_type,
				due_date
				)
		VALUES	(
				'comm',
				_due_date
				)
		RETURNING id
		INTO	_payment_id;
	END IF;
	
	IF	EXISTS (
		SELECT	1
		FROM	payment_lines
		WHERE	payment_type = 'comm'
		AND		parent_id = NEW.id
		)
	THEN
		UPDATE	payment_lines
		SET		status = 'pending',
				payment_id = _payment_id,
				due_date = _due_date
		WHERE	payment_type = 'comm'
		AND		parent_id = NEW.id
		AND		status <> 'cleared'
		AND 	( payment_id <> _payment_id OR
				due_date <> _due_date );
	ELSE
		INSERT INTO payment_lines (
				status,
				user_id,
				payment_id,
				parent_id,
				payment_type,
				payment_method,
				due_date,
				due_amount
				)
		SELECT	'pending',
				_aff_id,
				_payment_id,
				NEW.id,
				'comm',
				payment_method,
				_due_date,
				_aff_comm
		FROM	users
		WHERE	id = _aff_id;
	END IF;
	
	RETURN NEW;
END $$ LANGUAGE plpgsql;

CREATE TRIGGER payment_lines_20_delegate_commissions
	AFTER INSERT OR UPDATE ON payment_lines
FOR EACH ROW EXECUTE PROCEDURE payment_lines_delegate_commissions();
