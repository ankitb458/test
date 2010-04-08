/*
 * Payments
 */
CREATE TABLE payments (
	id				bigserial PRIMARY KEY,
	uuid			uuid NOT NULL DEFAULT uuid() UNIQUE,
	status			status_payable NOT NULL DEFAULT 'draft',
	name			varchar NOT NULL,
	payment_type	type_payment NOT NULL DEFAULT 'order',
	payment_method	method_payment,
	payment_ref		varchar UNIQUE,
	order_id		bigint REFERENCES orders(id),
	user_id			bigint REFERENCES users(id),
	due_date		datetime NOT NULL DEFAULT NOW(),
	due_amount		numeric(8,2) NOT NULL DEFAULT 0,
	cleared_date	datetime,
	cleared_amount	numeric(8,2) NOT NULL DEFAULT 0,
	created			datetime NOT NULL DEFAULT NOW(),
	modified		datetime NOT NULL DEFAULT NOW(),
	memo			text NOT NULL DEFAULT '',
	tsv				tsvector NOT NULL,
	CONSTRAINT valid_name
		CHECK ( name <> '' AND name = trim(name) ),
	CONSTRAINT valid_flow
		CHECK ( NOT ( due_date IS NULL AND status > 'draft' ) AND
			NOT ( cleared_date IS NULL AND status > 'pending' ) ),
	CONSTRAINT valid_payment_type
		CHECK ( order_id IS NULL OR payment_type = 'order' ),
	CONSTRAINT valid_payment_method
		CHECK ( payment_ref IS NULL OR payment_method IS NOT NULL ),
	CONSTRAINT valid_payment_ref
		CHECK ( payment_ref <> '' AND payment_ref = trim(payment_ref) ),
	CONSTRAINT valid_amounts
		CHECK ( due_amount >= 0 AND cleared_amount >= 0 )
);

SELECT	timestampable('payments'),
		searchable('payments'),
		trashable('payments');

CREATE INDEX payments_sort ON payments(payment_type, due_date DESC);
CREATE INDEX payments_user_id ON payments(payment_type, user_id, due_date DESC);
CREATE INDEX payments_order_id ON payments(payment_type, order_id, due_date DESC);

COMMENT ON TABLE payments IS E'Payments

- due and cleared dates have absolutely no relationship with one another.
  It is possible to advance pay, or late pay...';

/**
 * Clean a payment before it gets stored.
 */
CREATE OR REPLACE FUNCTION payments_clean()
	RETURNS trigger
AS $$
BEGIN
	-- Default name
	IF	NEW.name IS NULL
	THEN
		NEW.name = CASE
			WHEN NEW.payment_type = 'comm'
			THEN 'Commissions'
			ELSE 'Payment'
			END;
	END IF;
	
	-- Assign default dates if needed
	IF	NEW.due_date IS NULL AND NEW.status > 'draft'
	THEN
		NEW.due_date := NOW();
	END IF;
	IF	NEW.cleared_date IS NULL AND NEW.status > 'pending'
	THEN
		NEW.cleared_date := NOW();
	END IF;
	
	RETURN NEW;
END;
$$ LANGUAGE plpgsql;

CREATE TRIGGER payments_05_clean
	BEFORE INSERT OR UPDATE ON payments
FOR EACH ROW EXECUTE PROCEDURE payments_clean();

/**
 * Process read-only fields
 */
CREATE OR REPLACE FUNCTION payments_readonly()
	RETURNS trigger
AS $$
BEGIN
	IF	ROW(NEW.id, NEW.payment_type) IS DISTINCT FROM ROW(OLD.id, OLD.payment_type)
	THEN
		RAISE EXCEPTION 'Can''t edit readonly field in payments.id = %', NEW.id;
	END IF;
	
	RETURN NEW;
END $$ LANGUAGE plpgsql;

CREATE CONSTRAINT TRIGGER payments_01_readonly
	AFTER UPDATE ON payments
FOR EACH ROW EXECUTE PROCEDURE payments_readonly();