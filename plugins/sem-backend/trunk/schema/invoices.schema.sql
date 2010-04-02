/*
 * Invoices
 */
CREATE TABLE invoices (
	id				bigserial PRIMARY KEY,
	uuid			uuid NOT NULL DEFAULT uuid() UNIQUE,
	status			status_payable NOT NULL DEFAULT 'draft',
	name			varchar NOT NULL,
	due_date		datetime,
	due_amount		numeric(8,2) NOT NULL DEFAULT 0,
	due_tax			numeric(8,2) NOT NULL DEFAULT 0,
	cleared_date	datetime,
	cleared_amount	numeric(8,2) NOT NULL DEFAULT 0,
	cleared_tax		numeric(8,2) NOT NULL DEFAULT 0,
	created			datetime NOT NULL DEFAULT NOW(),
	modified		datetime NOT NULL DEFAULT NOW(),
	memo			text NOT NULL DEFAULT '',
	tsv				tsvector NOT NULL,
	CONSTRAINT valid_name
		CHECK ( name <> '' ),
	CONSTRAINT valid_flow
		CHECK ( NOT ( due_date IS NULL AND status > 'draft' ) AND
			NOT ( cleared_date IS NULL AND status > 'pending' ) )
);

SELECT	timestampable('invoices'),
		searchable('invoices'),
		trashable('invoices');

CREATE INDEX invoices_sort ON invoices(due_date DESC);

COMMENT ON TABLE invoices IS E'Invoices

- due and cleared dates have absolutely no relationship with one another.
  It is possible to advance pay or late pay...
- amount and tax fields are calculated. They correspond to active invoices
  only, i.e. cancelled invoices are ignored.';

/**
 * Clean an invoice before it gets stored.
 */
CREATE OR REPLACE FUNCTION invoices_clean()
	RETURNS trigger
AS $$
BEGIN
	-- Trim fields
	NEW.name := NULLIF(trim(NEW.name, ''), '');
	
	-- Default name
	IF	NEW.name IS NULL
	THEN
		NEW.name := NEW.uuid::varchar;
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

CREATE TRIGGER invoices_05_clean
	BEFORE INSERT OR UPDATE ON invoices
FOR EACH ROW EXECUTE PROCEDURE invoices_clean();