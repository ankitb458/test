/*
 * Transactions
 */
CREATE TABLE transactions (
	id				bigserial PRIMARY KEY,
	uuid			uuid NOT NULL DEFAULT uuid() UNIQUE,
	status			status_payable NOT NULL DEFAULT 'draft',
	name			varchar(255) NOT NULL,
	tx_id			varchar(255) NOT NULL DEFAULT '',
	tx_date			datetime,
	memo			text NOT NULL DEFAULT '',
	CONSTRAINT valid_flow
		CHECK ( NOT ( tx_date IS NULL AND status > 'draft' ) )
);

SELECT timestampable('transactions'), searchable('transactions'), trashable('transactions');

CREATE INDEX transactions_sort ON transactions(tx_date DESC);

COMMENT ON TABLE transactions IS E'Transactions

- tx_id corresponds to the counterparty''s transaction id.';

/**
 * Clean an transaction before it gets stored.
 */
CREATE OR REPLACE FUNCTION transactions_clean()
	RETURNS trigger
AS $$
BEGIN
	-- Trim fields
	NEW.name := NULLIF(trim(NEW.name, ''), '');
	NEW.tx_id := trim(NEW.tx_id);
	
	-- Default name
	IF	NEW.name IS NULL
	THEN
		NEW.name := NEW.uuid::varchar;
	END IF;
	
	-- Handle inherit status
	IF	NEW.status = 'inherit'
	THEN
		RAISE EXCEPTION 'Undefined behavior for transactions.status = inherit.';
	END IF;
	
	-- Assign a default date if needed
	IF	NEW.tx_date IS NULL AND NEW.status > 'draft'
	THEN
		NEW.tx_date := NOW()::datetime;
	END IF;
	
	RETURN NEW;
END;
$$ LANGUAGE plpgsql;

CREATE TRIGGER transactions_05_clean
	BEFORE INSERT OR UPDATE ON transactions
FOR EACH ROW EXECUTE PROCEDURE transactions_clean();