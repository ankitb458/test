/*
 * Payment lines
 */
CREATE TABLE payment_lines (
	id				bigserial PRIMARY KEY,
	uuid			uuid NOT NULL DEFAULT uuid() UNIQUE,
	status			status_payable NOT NULL DEFAULT 'draft',
	name			varchar NOT NULL,
	payment_id		bigint NOT NULL REFERENCES payments(id) ON DELETE CASCADE,
	order_line_id	bigint REFERENCES order_lines(id),
	parent_id		bigint REFERENCES payment_lines(id),
	amount			numeric(8,2) NOT NULL,
	created			datetime NOT NULL DEFAULT NOW(),
	modified		datetime NOT NULL DEFAULT NOW(),
	CONSTRAINT valid_name
		CHECK ( name <> '' AND name = trim(name) ),
	CONSTRAINT valid_amounts
		CHECK ( amount >= 0 )
);

SELECT	timestampable('payment_lines'),
		trashable('payment_lines');

CREATE INDEX payment_lines_payment_id ON payment_lines(payment_id);
CREATE INDEX payment_lines_parent_id ON payment_lines(parent_id);
CREATE INDEX payment_lines_order_line_id ON payment_lines(order_line_id);

COMMENT ON TABLE payments IS E'Payment lines

- A reference to an order line with no parent is non-recurring payment
  for an order.
- A reference to an order line with a parent is either of a recurring
  payment for an order, or a commission related to a payment.';

/**
 * Clean an payment line before it gets stored.
 */
CREATE OR REPLACE FUNCTION payment_lines_clean()
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
					WHEN payment_type = 'comm'
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

CREATE TRIGGER payment_lines_05_clean
	BEFORE INSERT OR UPDATE ON payment_lines
FOR EACH ROW EXECUTE PROCEDURE payment_lines_clean();

/**
 * Process read-only fields
 */
CREATE OR REPLACE FUNCTION payment_lines_readonly()
	RETURNS trigger
AS $$
BEGIN
	IF	ROW(NEW.id, NEW.payment_id, NEW.order_line_id, NEW.parent_id)
		IS DISTINCT FROM
		ROW(OLD.id, OLD.payment_id, OLD.order_line_id, OLD.parent_id)
	THEN
		RAISE EXCEPTION 'Can''t edit readonly field in payment_lines.id = %', NEW.id;
	END IF;
	
	RETURN NEW;
END $$ LANGUAGE plpgsql;

CREATE CONSTRAINT TRIGGER payment_lines_01_readonly
	AFTER UPDATE ON payment_lines
FOR EACH ROW EXECUTE PROCEDURE payment_lines_readonly();