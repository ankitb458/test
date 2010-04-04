/*
 * Invoice lines
 */
CREATE TABLE invoice_lines (
	id				bigserial PRIMARY KEY,
	uuid			uuid NOT NULL DEFAULT uuid() UNIQUE,
	status			status_payable NOT NULL DEFAULT 'draft',
	name			varchar NOT NULL,
	invoice_id		bigint NOT NULL REFERENCES invoices(id) ON UPDATE CASCADE ON DELETE CASCADE,
	user_id			bigint REFERENCES users(id) ON UPDATE CASCADE,
	parent_id		bigint REFERENCES invoice_lines(id) ON UPDATE CASCADE,
	order_line_id	bigint REFERENCES order_lines(id) ON UPDATE CASCADE,
	payment_type	type_payment NOT NULL DEFAULT 'payment',
	payment_method	method_payment,
	payment_ref		varchar UNIQUE,
	recurring		boolean NOT NULL DEFAULT false,
	due_date		datetime NOT NULL DEFAULT NOW(),
	due_amount		numeric(8,2) NOT NULL,
	due_taxes		numeric(8,2) NOT NULL,
	cleared_date	datetime,
	cleared_amount	numeric(8,2) NOT NULL DEFAULT 0,
	cleared_taxes	numeric(8,2) NOT NULL DEFAULT 0,
	created			datetime NOT NULL DEFAULT NOW(),
	modified		datetime NOT NULL DEFAULT NOW(),
	memo			text NOT NULL DEFAULT '',
	tsv				tsvector NOT NULL,
	CONSTRAINT valid_name
		CHECK ( name <> '' AND name = trim(name) ),
	CONSTRAINT valid_flow
		CHECK ( NOT ( due_date IS NULL AND status > 'draft' ) AND
			NOT ( cleared_date IS NULL AND status > 'pending' ) ),
	CONSTRAINT valid_payment_method
		CHECK ( payment_ref IS NULL OR payment_method IS NOT NULL ),
	CONSTRAINT valid_payment_ref
		CHECK ( payment_ref <> '' AND payment_ref = trim(payment_ref) ),
	CONSTRAINT valid_tax
		CHECK ( payment_type = 'commission' AND due_taxes = 0 OR
			payment_type <> 'commission' AND due_taxes >= 0 ),
	CONSTRAINT undefined_behavior
		CHECK ( due_taxes = 0 AND cleared_taxes = 0 )
);

SELECT	timestampable('invoice_lines'),
		searchable('invoice_lines'),
		trashable('invoice_lines');

CREATE INDEX invoice_lines_invoice_id ON invoice_lines(payment_type, invoice_id);
CREATE INDEX invoice_lines_user_id ON invoice_lines(payment_type, invoice_id, user_id);
CREATE INDEX invoice_lines_parent_id ON invoice_lines(payment_type, parent_id);
CREATE INDEX invoice_lines_order_line_id ON invoice_lines(payment_type, order_line_id);

COMMENT ON TABLE invoices IS E'Invoice lines

- due and cleared dates have absolutely no relationship with one another.
  It is possible to advance pay, or late pay...';

/**
 * Clean an invoice line before it gets stored.
 */
CREATE OR REPLACE FUNCTION invoice_lines_clean()
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
			FROM	invoice_lines
			WHERE	id = NEW.parent_id;
		ELSE
			NEW.name := CASE
				WHEN NEW.payment_type = 'commission'
				THEN 'Commission'
				ELSE 'Invoice'
				END;
		END IF;
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

CREATE TRIGGER invoice_lines_05_clean
	BEFORE INSERT OR UPDATE ON invoice_lines
FOR EACH ROW EXECUTE PROCEDURE invoice_lines_clean();
