/*
 * Orders
 */
CREATE TABLE orders (
	id				bigserial PRIMARY KEY,
	uuid			uuid NOT NULL DEFAULT uuid() UNIQUE,
	status			status_payable NOT NULL DEFAULT 'draft',
	due_date		datetime,
	cleared_date	datetime,
	name			varchar NOT NULL,
	user_id			bigint REFERENCES users(id) ON UPDATE CASCADE,
	campaign_id		bigint REFERENCES campaigns(id) ON UPDATE CASCADE,
	aff_id			bigint REFERENCES users(id) ON UPDATE CASCADE,
	memo			text NOT NULL DEFAULT '',
	CONSTRAINT valid_flow
		CHECK ( NOT ( due_date IS NULL AND status > 'draft' ) AND
			NOT ( cleared_date IS NULL AND status > 'pending' ) AND
			( due_date IS NULL OR cleared_date IS NULL OR cleared_date >= due_date ) ),
	CONSTRAINT undefined_behavior
		CHECK ( status <> 'inherit' )
);

SELECT	timestampable('orders'),
		searchable('orders'),
		trashable('orders');

CREATE INDEX orders_sort ON orders(due_date DESC);
CREATE INDEX orders_user_id ON orders(user_id);
CREATE INDEX orders_campaign_id ON orders(campaign_id);
CREATE INDEX orders_aff_id ON orders(aff_id);

COMMENT ON TABLE orders IS E'Orders

- user_id gets billed; order_lines.user_id gets shipped.
- aff_id gets the commission and is tied to the campaign_id. It gets stored
  for reference, in case a campaign''s owner changes.';

/**
 * Clean an order before it gets stored.
 */
CREATE OR REPLACE FUNCTION orders_clean()
	RETURNS trigger
AS $$
BEGIN
	-- Trim fields
	NEW.name := NULLIF(trim(NEW.name, ''), '');
	
	-- Default name
	IF	NEW.name IS NULL
	THEN
		IF	NEW.user_id IS NOT NULL
		THEN
			SELECT	name
			INTO	NEW.name
			FROM	users
			WHERE	id = NEW.user_id;
		END IF;
		
		IF	NEW.name IS NULL
		THEN
			NEW.name := 'Order';
		END IF;
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

CREATE TRIGGER orders_05_clean
	BEFORE INSERT OR UPDATE ON orders
FOR EACH ROW EXECUTE PROCEDURE orders_clean();