/*
 * Invoice lines
 */
CREATE TABLE invoice_lines (
	id				bigserial PRIMARY KEY,
	uuid			uuid NOT NULL DEFAULT uuid() UNIQUE,
	status			status_payable NOT NULL DEFAULT 'draft',
	name			varchar NOT NULL,
	invoice_id		bigint NOT NULL REFERENCES invoices(id) ON DELETE CASCADE,
	order_line_id	bigint REFERENCES order_lines(id),
	parent_id		bigint REFERENCES invoice_lines(id),
	amount			numeric(8,2) NOT NULL,
	created_date	datetime NOT NULL DEFAULT NOW(),
	modified_date	datetime NOT NULL DEFAULT NOW(),
	CONSTRAINT valid_name
		CHECK ( name <> '' AND name = trim(name) ),
	CONSTRAINT valid_amounts
		CHECK ( amount >= 0 )
);

SELECT	timestampable('invoice_lines'),
		trashable('invoice_lines');

CREATE INDEX invoice_lines_invoice_id ON invoice_lines(invoice_id);
CREATE INDEX invoice_lines_parent_id ON invoice_lines(parent_id);
CREATE INDEX invoice_lines_order_line_id ON invoice_lines(order_line_id);

COMMENT ON TABLE invoices IS E'Invoice lines

- A reference to an order line with no parent is a non-recurring invoice
  for an order.
- A reference to an order line with a parent is either of a recurring
  invoice for an order, or a commission related to a invoice.';

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
			SELECT	CASE
					WHEN order_id IS NULL
					THEN 'Commission'
					ELSE 'Order'
					END
			INTO	NEW.name
			FROM	invoices
			WHERE	id = NEW.invoice_id;
		END IF;
	END IF;
	
	RETURN NEW;
END;
$$ LANGUAGE plpgsql;

CREATE TRIGGER invoice_lines_05_clean
	BEFORE INSERT OR UPDATE ON invoice_lines
FOR EACH ROW EXECUTE PROCEDURE invoice_lines_clean();

/**
 * Process read-only fields
 */
CREATE OR REPLACE FUNCTION invoice_lines_readonly()
	RETURNS trigger
AS $$
BEGIN
	IF	ROW(NEW.id, NEW.invoice_id, NEW.order_line_id, NEW.parent_id)
		IS DISTINCT FROM
		ROW(OLD.id, OLD.invoice_id, OLD.order_line_id, OLD.parent_id)
	THEN
		RAISE EXCEPTION 'Can''t edit readonly field in invoice_lines.id = %', NEW.id;
	END IF;
	
	RETURN NEW;
END $$ LANGUAGE plpgsql;

CREATE CONSTRAINT TRIGGER invoice_lines_01_readonly
	AFTER UPDATE ON invoice_lines
FOR EACH ROW EXECUTE PROCEDURE invoice_lines_readonly();