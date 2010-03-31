/*
 * Transactions
 */
CREATE TABLE transactions (
	id				bigserial PRIMARY KEY,
	uuid			uuid NOT NULL DEFAULT uuid() UNIQUE,
	status			status_payable NOT NULL DEFAULT 'draft',
	due_date		datetime,
	cleared_date	datetime,
	name			varchar NOT NULL,
	tx_type			transaction_type NOT NULL DEFAULT 'init_in',
	ext_tx_id		varchar(128) UNIQUE,
	ext_status		varchar(64) NOT NULL DEFAULT '',
	memo			text NOT NULL DEFAULT '',
	CONSTRAINT valid_flow
		CHECK ( NOT ( due_date IS NULL AND status > 'draft' ) AND
			NOT ( cleared_date IS NULL AND status > 'pending' ) AND
			( due_date IS NULL OR cleared_date IS NULL OR cleared_date >= due_date ) ),
	CONSTRAINT undefined_behavior
		CHECK ( status <> 'inherit' )
);

SELECT	timestampable('transactions'),
		searchable('transactions'),
		trashable('transactions');

CREATE INDEX transactions_sort ON transactions(cleared_date DESC);

COMMENT ON TABLE transactions IS E'Transactions

- ext_tx_id and ext_status correspond to the counterparty''s
  transaction id and status.';

/**
 * Clean an transaction before it gets stored.
 */
CREATE OR REPLACE FUNCTION transactions_clean()
	RETURNS trigger
AS $$
BEGIN
	-- Trim fields
	NEW.name := NULLIF(trim(NEW.name, ''), '');
	NEW.ext_tx_id := trim(NEW.ext_tx_id);
	NEW.ext_status := trim(NEW.ext_status);
	
	-- Default name
	IF	NEW.name IS NULL
	THEN
		NEW.name := NEW.uuid::varchar;
	END IF;
	
	-- Assign default dates if needed
	IF	NEW.due_date IS NULL AND NEW.status > 'draft'
	THEN
		NEW.due_date := NOW()::datetime;
	END IF;
	IF	NEW.cleared_date IS NULL AND NEW.status > 'pending'
	THEN
		NEW.cleared_date := NOW()::datetime;
	END IF;
	
	RETURN NEW;
END;
$$ LANGUAGE plpgsql;

CREATE TRIGGER transactions_05_clean
	BEFORE INSERT OR UPDATE ON transactions
FOR EACH ROW EXECUTE PROCEDURE transactions_clean();