/*
 * Invoice lines
 */
CREATE TABLE invoice_lines (
	id				bigserial PRIMARY KEY,
	uuid			uuid NOT NULL DEFAULT uuid() UNIQUE,
	status			status_payable NOT NULL DEFAULT 'draft',
	name			varchar NOT NULL,
	due_date		datetime,
	cleared_date	datetime,
	invoice_id		bigint NOT NULL REFERENCES invoices(id) ON UPDATE CASCADE ON DELETE CASCADE,
	parent_id		bigint REFERENCES invoice_lines(id) ON UPDATE CASCADE,
	order_line_id	bigint REFERENCES order_lines(id) ON UPDATE CASCADE,
	user_id			bigint REFERENCES users(id) ON UPDATE CASCADE,
	amount			numeric(8,2) NOT NULL,
	tax				numeric(8,2) NOT NULL,
	created			datetime NOT NULL DEFAULT NOW(),
	modified		datetime NOT NULL DEFAULT NOW(),
	memo			text NOT NULL DEFAULT '',
	tsv				tsvector NOT NULL,
	CONSTRAINT valid_name
		CHECK ( name <> '' ),
	CONSTRAINT valid_amounts
		CHECK ( amount >= 0 AND tax >= 0 ),
	CONSTRAINT valid_flow
		CHECK ( NOT ( due_date IS NULL AND status > 'draft' ) AND
			NOT ( cleared_date IS NULL AND status > 'pending' ) )
);

SELECT	timestampable('invoice_lines'),
		searchable('invoice_lines'),
		trashable('invoice_lines');

CREATE INDEX invoice_lines_sort ON invoice_lines(cleared_date DESC);
CREATE INDEX invoice_lines_invoice_id ON invoice_lines(invoice_id);
CREATE INDEX invoice_lines_order_line_id ON invoice_lines(order_line_id);
CREATE INDEX invoice_lines_user_id ON invoice_lines(user_id);

COMMENT ON TABLE invoices IS E'Invoice lines

';

/**
 * Clean an invoice line before it gets stored.
 */
CREATE OR REPLACE FUNCTION invoice_lines_clean()
	RETURNS trigger
AS $$
DECLARE
	c			campaigns;
BEGIN
	-- Trim fields
	NEW.name := NULLIF(trim(NEW.name, ''), '');
	
	-- Default name
	IF	NEW.name IS NULL
	THEN
		IF	NEW.order_line_id IS NOT NULL
		THEN
			SELECT	name
			INTO	NEW.name
			FROM	order_lines
			WHERE	id = NEW.order_line_id;
		END IF;
		
		IF	NEW.name IS NULL
		THEN
			NEW.name := 'Invoice';
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
