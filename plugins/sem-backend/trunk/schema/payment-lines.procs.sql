/**
 * Sets a default name when needed.
 */
CREATE OR REPLACE FUNCTION payment_lines_sanitize_name()
	RETURNS trigger
AS $$
BEGIN
	-- Default name
	IF	NEW.name IS NULL
	THEN
		IF	NEW.order_line_id IS NOT NULL
		THEN
			SELECT	name
			INTO	NEW.name
			FROM	order_lines
			WHERE	id = NEW.order_line_id;
		ELSEIF NEW.parent_id IS NOT NULL
		THEN
			SELECT	name
			INTO	NEW.name
			FROM	payment_lines
			WHERE	id = NEW.parent_id;
		ELSE
			SELECT	CASE
					WHEN order_id IS NULL
					THEN 'Commission'
					ELSE 'Order'
					END
			INTO	NEW.name
			FROM	payments
			WHERE	id = NEW.payment_id;
		END IF;
	END IF;
	
	RETURN NEW;
END;
$$ LANGUAGE plpgsql;

CREATE TRIGGER payment_lines_05_sanitize_name
	BEFORE INSERT OR UPDATE ON payment_lines
FOR EACH ROW EXECUTE PROCEDURE payment_lines_sanitize_name();

/**
 * Recalculates a payment's status and due amount based on transaction lines
 */
CREATE OR REPLACE FUNCTION payment_lines_delegate_payments()
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
	FROM	payment_lines
	WHERE	payment_id = NEW.payment_id;
	
	_status := COALESCE(_status, 'trash');
	_amount := COALESCE(_amount, 0);
	
	UPDATE	payments
	SET		status = _status,
			due_amount = _amount
	WHERE	id = NEW.payment_id
	AND		( status <> _status OR due_amount <> _amount );
	
	-- RAISE NOTICE '%, %', TG_NAME, FOUND;
	
	RETURN NEW;
END $$ LANGUAGE plpgsql;

CREATE TRIGGER payment_lines_10_delegate_payments
	AFTER INSERT OR UPDATE ON payment_lines
FOR EACH ROW EXECUTE PROCEDURE payment_lines_delegate_payments();

/**
 * Delegates the status for order lines
 */
CREATE OR REPLACE FUNCTION payment_lines_delegate_order_lines()
	RETURNS trigger
AS $$
DECLARE
	_status			status_payable;
	_payment		record;
	_offset			int := 0;
	_extra			boolean;
	_payment_id		bigint;
	_due_date		datetime;
	_rec_amount		numeric(8,2);
	_rec_interval	interval;
	_rec_count		int;
	_aff_id			bigint;
	_aff_comm		numeric(8,2);
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
	
	SELECT	due_date,
			cleared_date,
			order_id
	INTO	_payment
	FROM	payments
	WHERE	id = NEW.payment_id
	AND		payment_type = 'revenue';
	
	IF	NOT FOUND
	THEN
		RETURN NEW;
	END IF;
	
	SELECT	MIN(payment_lines.status)
	INTO	_status
	FROM	payment_lines
	JOIN	payments
	ON		payments.id = payment_lines.payment_id
	WHERE	payment_lines.order_line_id = NEW.order_line_id
	AND		payments.payment_type = 'revenue'
			-- Ignore drafts and pending payments unless it's the initial one
	AND		( payments.status NOT IN ('trash', 'draft', 'pending') OR payment_lines.parent_id IS NULL );
	
	IF	TG_OP = 'INSERT'
	THEN
		IF	NEW.status = 'cleared'
		THEN
			_extra := TRUE;
			
			IF	NEW.parent_id IS NOT NULL
			THEN
				_offset := -1;
			END IF;
		END IF;
	ELSE
		IF	NEW.status = 'cleared' AND OLD.status <> 'cleared'
		THEN
			_extra := TRUE;
			
			IF	NEW.parent_id IS NOT NULL
			THEN
				_offset := -1;
			END IF;
		ELSEIF NEW.status <> 'cleared' AND OLD.status = 'cleared'
		THEN
			_extra := FALSE;
			
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
	AND		( status <> _status OR rec_count IS NOT NULL AND rec_count <> rec_count + _offset );
		
	-- RAISE NOTICE '%, %', TG_NAME, FOUND;
	
	IF	_extra IS NULL
	THEN
		RETURN NEW;
	END IF;
	
	IF	NOT _extra
	THEN
		-- Cancel all draft and pending payments related to this order line
		UPDATE	payment_lines
		SET		status = 'cancelled'
		FROM	payments
		WHERE	payments.id = payment_lines.payment_id
		AND		payment_lines.order_line_id = NEW.order_line_id
		AND		payment_lines.status IN ('draft', 'pending');
		
		RETURN NEW;
	END IF;
	
	-- Fetch recurring payment details and commission details
	SELECT	quantity * ( rec_price - rec_discount ),
			rec_interval,
			rec_count,
			CASE
			WHEN NEW.parent_id IS NULL
			THEN quantity * init_comm
			ELSE quantity * rec_comm
			END
	INTO	_rec_amount,
			_rec_interval,
			_rec_count,
			_aff_comm
	FROM	order_lines
	WHERE	id = NEW.order_line_id;
	
	IF	_rec_amount > 0 AND ( _rec_count IS NULL OR _rec_count > 0 )
	THEN
		IF	TG_OP = 'UPDATE'
		THEN
			-- Try an update first
			UPDATE	payment_lines
			SET		status = CASE
					WHEN payment_lines.status <> 'cleared'
					THEN 'pending'
					ELSE 'cleared'
					END::status_payable,
					amount = _rec_amount
			FROM	payments
			WHERE	payments.payment_type = 'revenue'
			AND		payment_lines.order_line_id = NEW.order_line_id
			AND		parent_id = NEW.id;
		END IF;
		
		IF	TG_OP = 'INSERT' OR NOT FOUND
		THEN
			-- Extract the payment's due date
			_due_date := _payment.due_date + _rec_interval;
		
			SELECT	id
			INTO	_payment_id
			FROM	payments
			WHERE	payment_type = 'revenue'
			AND		due_date = _due_date;
		
			IF	NOT FOUND
			THEN
				INSERT INTO payments (
						status,
						payment_type,
						order_id,
						due_date
						)
				VALUES	(
						'pending',
						'revenue',
						_payment.order_id,
						_due_date
						)
				RETURNING id
				INTO	_payment_id;
			END IF;
			
			INSERT INTO payment_lines (
					status,
					payment_id,
					order_line_id,
					parent_id,
					amount
					)
			VALUES (
					'pending',
					_payment_id,
					NEW.order_line_id,
					NEW.id,
					_rec_amount
					);
		END IF;
	END IF;
	
	-- Process commission
	IF	_aff_comm <> 0
	THEN
		-- Extract the commission's due date
		_due_date := date_trunc('month', _payment.cleared_date + interval '1 month + 2 week');
		IF	_due_date - _payment.cleared_date < interval '30 day'
		THEN
			_due_date := _due_date + interval '2 week';
		END IF;

		IF	_payment.order_id IS NOT NULL
		THEN
			SELECT	aff_id
			INTO	_aff_id
			FROM	orders
			WHERE	id = _payment.order_id;
		END IF;

		IF	_aff_id IS NOT NULL
		THEN
			SELECT	id
			INTO	_payment_id
			FROM	payments
			WHERE	payment_type = 'expense'
			AND		due_date = _due_date
			AND		user_id = _aff_id;
		ELSE
			SELECT	id
			INTO	_payment_id
			FROM	payments
			WHERE	due_date = _due_date
			AND		payment_type = 'expense'
			AND		user_id IS NULL;
		END IF;

		IF	NOT FOUND
		THEN
			INSERT INTO payments (
					status,
					payment_type,
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
			INTO	_payment_id;
	
			INSERT INTO payment_lines (
					status,
					payment_id,
					order_line_id,
					parent_id,
					amount
					)
			VALUES (
					'pending',
					_payment_id,
					NEW.order_line_id,
					NEW.id,
					_aff_comm
					);
		ELSE
			IF	TG_OP = 'UPDATE'
			THEN
				-- Try an update first
				UPDATE	payment_lines
				SET		status = 'pending',
						amount = _aff_comm
				WHERE	payment_id = _payment_id
				AND		order_line_id = NEW.order_line_id
				AND		parent_id = NEW.id;
			END IF;
	
			IF	TG_OP = 'INSERT' OR NOT FOUND
			THEN
				INSERT INTO payment_lines (
						status,
						payment_id,
						order_line_id,
						parent_id,
						amount
						)
				VALUES (
						'pending',
						_payment_id,
						NEW.order_line_id,
						NEW.id,
						_aff_comm
						);
			END IF;
		END IF;
	END IF;
	
	RETURN NEW;
END $$ LANGUAGE plpgsql;

CREATE TRIGGER payment_lines_20_delegate_order_lines
	AFTER INSERT OR UPDATE ON payment_lines
FOR EACH ROW EXECUTE PROCEDURE payment_lines_delegate_order_lines();