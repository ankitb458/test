/**
 * Autofills the user id when possible
 */
CREATE OR REPLACE FUNCTION payments_fill_user_id()
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

CREATE TRIGGER payments_02_fill_user_id
	BEFORE INSERT ON payments
FOR EACH ROW EXECUTE PROCEDURE payments_fill_user_id();

/**
 * Autofills an order's payment
 */
CREATE OR REPLACE FUNCTION payments_fill_lines()
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
			THEN init_amount
			ELSE rec_amount
			END
	FROM	order_lines
	LEFT JOIN payment_lines
	ON		payment_lines.order_line_id = order_lines.id
	AND		payment_lines.parent_id IS NULL
	WHERE	order_lines.order_id = NEW.order_id
	AND		( -- Initial payment
			payment_lines.parent_id IS NULL AND order_lines.status IN ('draft', 'pending')
	OR		-- Recurring payment
			payment_lines.parent_id IS NULL AND order_lines.status = 'cleared' AND
				order_lines.rec_interval IS NOT NULL AND
				( order_lines.rec_count IS NULL OR order_lines.rec_count > 0 )
			);
	
	RETURN NEW;
END $$ LANGUAGE plpgsql;

CREATE TRIGGER payments_10_fill_lines
	AFTER INSERT ON payments
FOR EACH ROW EXECUTE PROCEDURE payments_fill_lines();