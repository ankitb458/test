/*
 * Invoices
 */
CREATE TABLE invoices (
	id				bigserial PRIMARY KEY,
	uuid			uuid NOT NULL DEFAULT uuid() UNIQUE,
	status			status_payable NOT NULL DEFAULT 'draft',
	name			varchar NOT NULL,
	invoice_type	type_invoice NOT NULL DEFAULT 'revenue',
	payment_method	method_payment,
	payment_ref		varchar UNIQUE,
	order_id		bigint REFERENCES orders(id),
	user_id			bigint REFERENCES users(id),
	due_date		datetime NOT NULL DEFAULT NOW(),
	due_amount		numeric(8,2) NOT NULL DEFAULT 0,
	cleared_date	datetime,
	cleared_amount	numeric(8,2) NOT NULL DEFAULT 0,
	created_date	datetime NOT NULL DEFAULT NOW(),
	modified_date	datetime NOT NULL DEFAULT NOW(),
	memo			text NOT NULL DEFAULT '',
	tsv				tsvector NOT NULL,
	CONSTRAINT valid_name
		CHECK ( name <> '' AND name = trim(name) ),
	CONSTRAINT valid_flow
		CHECK ( NOT ( due_date IS NULL AND status > 'draft' ) AND
			NOT ( cleared_date IS NULL AND status = 'cleared' ) ),
	CONSTRAINT valid_invoice_type
		CHECK ( invoice_type = 'revenue' OR order_id IS NULL ),
	CONSTRAINT valid_payment_method
		CHECK ( payment_ref IS NULL OR payment_method IS NOT NULL ),
	CONSTRAINT valid_payment_ref
		CHECK ( payment_ref <> '' AND payment_ref = trim(payment_ref) ),
	CONSTRAINT valid_amounts
		CHECK ( due_amount >= 0 AND cleared_amount >= 0 )
);

SELECT	timestampable('invoices'),
		payable('invoices'),
		searchable('invoices'),
		trashable('invoices');

CREATE INDEX invoices_sort ON invoices(due_date DESC);
CREATE INDEX invoices_user_id ON invoices(order_id, user_id, due_date DESC);
CREATE INDEX invoices_order_id ON invoices(order_id, due_date DESC);

COMMENT ON TABLE invoices IS E'Invoices

- due and cleared dates have absolutely no relationship with one another.
  It is possible to advance pay, or late pay...';

/**
 * Process read-only fields
 */
CREATE OR REPLACE FUNCTION invoices_readonly()
	RETURNS trigger
AS $$
BEGIN
	IF	ROW(NEW.id, NEW.invoice_type, NEW.order_id)
		IS DISTINCT FROM
		ROW(OLD.id, OLD.invoice_type, OLD.order_id)
	THEN
		RAISE EXCEPTION 'Can''t edit readonly field in invoices.id = %', NEW.id;
	END IF;
	
	RETURN NEW;
END $$ LANGUAGE plpgsql;

CREATE CONSTRAINT TRIGGER invoices_01_readonly
	AFTER UPDATE ON invoices
FOR EACH ROW EXECUTE PROCEDURE invoices_readonly();