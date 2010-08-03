/*
 * payments
 */
CREATE TABLE payments (
	id				bigserial PRIMARY KEY,
	uuid			uuid NOT NULL DEFAULT uuid() UNIQUE,
	status			status_payable NOT NULL DEFAULT 'draft',
	name			varchar NOT NULL,
	payment_type	type_payment NOT NULL DEFAULT 'revenue',
	payment_method	method_payment,
	payment_ref		varchar UNIQUE,
	order_id		bigint REFERENCES orders(id),
	user_id			bigint REFERENCES users(id),
	issue_date		datetime,
	due_date		datetime,
	due_amount		numeric(8,2) NOT NULL DEFAULT 0,
	cleared_date	datetime,
	cleared_amount	numeric(8,2) NOT NULL DEFAULT 0,
	created_date	datetime NOT NULL DEFAULT NOW(),
	modified_date	datetime NOT NULL DEFAULT NOW(),
	memo			text NOT NULL DEFAULT '',
--	tsv				tsvector NOT NULL,
	CONSTRAINT valid_name
		CHECK ( name <> '' AND name = trim(name) ),
	CONSTRAINT valid_flow
		CHECK ( NOT ( issue_date IS NULL AND status > 'draft' ) AND
			NOT ( due_date IS NULL AND status > 'draft' ) AND
			NOT ( cleared_date IS NULL AND status = 'cleared' ) ),
	CONSTRAINT valid_payment_type
		CHECK ( payment_type = 'revenue' OR order_id IS NULL ),
	CONSTRAINT valid_payment_method
		CHECK ( payment_ref IS NULL OR payment_method IS NOT NULL ),
	CONSTRAINT valid_payment_ref
		CHECK ( payment_ref <> '' AND payment_ref = trim(payment_ref) )
);

SELECT	timestampable('payments'),
		payable('payments'),
--		searchable('payments'),
		trashable('payments');

COMMENT ON TABLE payments IS E'payments

- due and cleared dates have absolutely no relationship with one another.
  It is possible to advance pay, or late pay...';

/**
 * Process read-only fields
 */
CREATE OR REPLACE FUNCTION payments_readonly()
	RETURNS trigger
AS $$
BEGIN
	IF	ROW(NEW.id, NEW.payment_type, NEW.order_id)
		IS DISTINCT FROM
		ROW(OLD.id, OLD.payment_type, OLD.order_id)
	THEN
		RAISE EXCEPTION 'Can''t edit readonly field in payments.id = %', NEW.id;
	END IF;
	
	RETURN NEW;
END $$ LANGUAGE plpgsql;

CREATE CONSTRAINT TRIGGER payments_01_readonly
	AFTER UPDATE ON payments
FOR EACH ROW EXECUTE PROCEDURE payments_readonly();