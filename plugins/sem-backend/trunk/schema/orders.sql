/*
 * Orders
 */
CREATE TABLE orders (
	id				bigserial PRIMARY KEY,
	uuid			uuid NOT NULL DEFAULT uuid() UNIQUE,
	status			status_billable NOT NULL DEFAULT 'draft',
	name			varchar(255) NOT NULL,
	order_date		datetime,
	user_id			bigint REFERENCES users(id) ON UPDATE CASCADE,
	campaign_id		bigint REFERENCES campaigns(id) ON UPDATE CASCADE,
	aff_id			bigint REFERENCES users(id) ON UPDATE CASCADE,
	memo			text NOT NULL DEFAULT '',
	CONSTRAINT valid_order_flow
		CHECK ( NOT ( order_date IS NULL AND status <> 'draft' ) )
);

SELECT timestampable('orders'), searchable('orders'), trashable('orders');

CREATE INDEX orders_sort ON orders(order_date DESC);
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
	
	-- Handle inherit status
	IF	NEW.status = 'inherit'
	THEN
		RAISE EXCEPTION 'Undefined behavior for orders.status = inherit.';
	END IF;
	
	-- Assign a default date if needed
	IF	NEW.order_date IS NULL AND NEW.status <> 'draft'
	THEN
		NEW.order_date := NOW()::datetime;
	END IF;
	
	-- Raise warning if user_id = aff_id
	IF	NEW.user_id = NEW.aff_id AND NEW.status > 'inherit'
	THEN
		RAISE WARNING 'In orders.id = %, user_id = aff_id = %.', NEW.id, NEW.user_id;
	END IF;
	
	RETURN NEW;
END;
$$ LANGUAGE plpgsql;

CREATE TRIGGER orders_05_clean
	BEFORE INSERT OR UPDATE ON orders
FOR EACH ROW EXECUTE PROCEDURE orders_clean();