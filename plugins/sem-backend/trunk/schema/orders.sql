/*
 * Orders
 */
CREATE TABLE orders (
	id				bigserial PRIMARY KEY,
	uuid			uuid NOT NULL DEFAULT uuid() UNIQUE,
	status			status_billable NOT NULL DEFAULT 'draft',
	name			varchar(255) NOT NULL,
	order_date		datetime,
	billing_id		bigint REFERENCES users(id) ON UPDATE CASCADE,
	aff_id			bigint REFERENCES users(id) ON UPDATE CASCADE,
	campaign_id		bigint REFERENCES campaigns(id) ON UPDATE CASCADE,
	memo			text NOT NULL DEFAULT '',
	CONSTRAINT valid_order_flow
		CHECK ( NOT ( order_date IS NULL AND status > 'draft' ) )
);

SELECT timestampable('orders'), searchable('orders'), trashable('orders');

CREATE INDEX orders_sort ON orders(order_date DESC);
CREATE INDEX orders_billing_id ON orders(billing_id);
CREATE INDEX orders_aff_id ON orders(aff_id);
CREATE INDEX orders_campaign_id ON orders(campaign_id);

COMMENT ON TABLE orders IS E'Orders

- billing_id gets billed; order_lines.user_id gets shipped.
- aff_id gets the commission and is tied to the campaign_id. It gets stored
  for reference, in case a campaign''s owner changes.
- coupon_id, when present, is typically the same as the campaign_id. A system-
  wide promo on a product may make the two different, however.';

/**
 * Clean an order before it gets stored.
 */
CREATE OR REPLACE FUNCTION orders_clean()
	RETURNS trigger
AS $$
BEGIN
	NEW.name := NULLIF(trim(NEW.name, ''), '');
	
	IF	NEW.name IS NULL
	THEN
		IF	NEW.billing_id IS NOT NULL
		THEN
			SELECT	name
			INTO	NEW.name
			FROM	users
			WHERE	id = NEW.billing_id;
		END IF;
		
		IF	NEW.name = ''
		THEN
			NEW.name := 'Anonymous User';
		END IF;
	END IF;
	
	IF	NEW.order_date IS NULL AND NEW.status > 'draft'
	THEN
		NEW.order_date := NOW();
	END IF;
	
	RETURN NEW;
END;
$$ LANGUAGE plpgsql;

CREATE TRIGGER orders_03_clean
	BEFORE INSERT OR UPDATE ON orders
FOR EACH ROW EXECUTE PROCEDURE orders_clean();