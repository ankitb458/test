/*
 * Transaction lines
 */
CREATE TABLE transaction_lines (
	id				bigserial PRIMARY KEY,
	uuid			uuid NOT NULL DEFAULT uuid() UNIQUE,
	status			status_payable NOT NULL DEFAULT 'draft',
	name			varchar NOT NULL,
	due_date		datetime NOT NULL DEFAULT NOW()::datetime,
	paid_date		datetime,
	tx_id			bigint NOT NULL REFERENCES transactions(id) ON UPDATE CASCADE ON DELETE CASCADE,
	tx_type			transaction_type NOT NULL DEFAULT 'init_in',
	parent_id		bigint REFERENCES transaction_lines(id) ON UPDATE CASCADE,
	order_line_id	bigint REFERENCES order_lines(id) ON UPDATE CASCADE,
	user_id			bigint REFERENCES users(id) ON UPDATE CASCADE,
	amount			numeric(8,2) NOT NULL,
	fee				numeric(8,2) NOT NULL,
	tax				numeric(8,2) NOT NULL,
	ext_id			varchar(128) UNIQUE,
	ext_status		varchar(64) NOT NULL DEFAULT '',
	memo			text NOT NULL DEFAULT '',
	CONSTRAINT valid_amounts
		CHECK ( amount >= 0 AND fee >= 0 AND tax >= 0 ),
	CONSTRAINT valid_flow
		CHECK ( NOT ( paid_date IS NULL AND status > 'draft' ) ),
	CONSTRAINT undefined_behavior
		CHECK ( status <> 'inherit' AND tax = 0 )
);

SELECT	timestampable('transaction_lines'),
		searchable('transaction_lines'),
		trashable('transaction_lines');

CREATE INDEX transaction_lines_sort ON transaction_lines(paid_date DESC);
CREATE INDEX transaction_lines_tx_id ON transaction_lines(tx_id);
CREATE INDEX transaction_lines_order_line_id ON transaction_lines(order_line_id);
CREATE INDEX transaction_lines_user_id ON transaction_lines(user_id);

COMMENT ON TABLE transactions IS E'Transaction lines

- product_id is only here for statistics.
- ext_id and ext_status correspond to the counterparty''s
  transaction id and status.';

/**
 * Clean an transaction line before it gets stored.
 */
CREATE OR REPLACE FUNCTION transaction_lines_clean()
	RETURNS trigger
AS $$
DECLARE
	c			campaigns;
BEGIN
	-- Trim fields
	NEW.name := NULLIF(trim(NEW.name, ''), '');
	NEW.ext_id := trim(NEW.ext_id);
	NEW.ext_status := trim(NEW.ext_status);
	
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
			NEW.name := 'Transaction';
		END IF;
	END IF;
	
	RETURN NEW;
END;
$$ LANGUAGE plpgsql;

CREATE TRIGGER transaction_lines_05_clean
	BEFORE INSERT OR UPDATE ON transaction_lines
FOR EACH ROW EXECUTE PROCEDURE transaction_lines_clean();
