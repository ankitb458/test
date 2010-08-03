/*
 * payment lines
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
	created_date	datetime NOT NULL DEFAULT NOW(),
	modified_date	datetime NOT NULL DEFAULT NOW(),
	CONSTRAINT valid_name
		CHECK ( name <> '' AND name = trim(name) )
);

SELECT	timestampable('payment_lines'),
		trashable('payment_lines');

CREATE INDEX payment_lines_payment_id ON payment_lines(payment_id);
CREATE INDEX payment_lines_parent_id ON payment_lines(parent_id);
CREATE INDEX payment_lines_order_line_id ON payment_lines(order_line_id);

COMMENT ON TABLE payments IS E'payment lines

- A reference to an order line with no parent is a non-recurring payment
  for an order.
- A reference to an order line with a parent is either of a recurring
  payment for an order, or a commission related to a payment.';

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