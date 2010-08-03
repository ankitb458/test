/*
 * Orders
 */
CREATE TABLE orders (
	id				bigserial PRIMARY KEY,
	uuid			uuid NOT NULL DEFAULT uuid() UNIQUE,
	status			status_payable NOT NULL DEFAULT 'draft',
	name			varchar NOT NULL,
	user_id			bigint REFERENCES users(id),
	campaign_id		bigint REFERENCES campaigns(id),
	aff_id			bigint REFERENCES users(id),
	issue_date		datetime,
	due_date		datetime,
	cleared_date	datetime,
	created_date	datetime NOT NULL DEFAULT NOW(),
	modified_date	datetime NOT NULL DEFAULT NOW(),
	memo			text NOT NULL DEFAULT '',
--	tsv				tsvector NOT NULL,
	CONSTRAINT valid_name
		CHECK ( name <> '' AND name = trim(name) ),
	CONSTRAINT valid_flow
		CHECK ( NOT ( issue_date IS NULL AND status > 'draft' ) AND
			NOT ( due_date IS NULL AND status > 'draft' ) AND
			NOT ( cleared_date IS NULL AND status = 'cleared' ) )
);

SELECT	timestampable('orders'),
		payable('orders'),
--		searchable('orders'),
		trashable('orders');

CREATE INDEX orders_sort ON orders(due_date DESC);
CREATE INDEX orders_user_id ON orders(user_id);
CREATE INDEX orders_campaign_id ON orders(campaign_id);
CREATE INDEX orders_aff_id ON orders(aff_id);

COMMENT ON TABLE orders IS E'Orders

- user_id gets paymentd; order_lines.user_id gets shipped.
- aff_id gets the commission and is extracted from the campaign_id.
- due and cleared dates have absolutely no relationship with one another.
  It is possible to advance pay or late pay...';

/**
 * Process read-only fields
 */
CREATE OR REPLACE FUNCTION orders_readonly()
	RETURNS trigger
AS $$
BEGIN
	IF	ROW(NEW.id) IS DISTINCT FROM ROW(OLD.id)
	THEN
		RAISE EXCEPTION 'Can''t edit readonly field in orders.id = %', NEW.id;
	END IF;
	
	RETURN NEW;
END $$ LANGUAGE plpgsql;

CREATE CONSTRAINT TRIGGER orders_01_readonly
	AFTER UPDATE ON orders
FOR EACH ROW EXECUTE PROCEDURE orders_readonly();