/*
 * Order lines
 */
CREATE TABLE order_lines (
	id				bigserial PRIMARY KEY,
	uuid			uuid NOT NULL DEFAULT uuid() UNIQUE,
	status			status_payable NOT NULL DEFAULT 'draft',
	due				datetime,
	cleared			datetime,
	name			varchar NOT NULL,
	order_id		bigint NOT NULL REFERENCES orders(id) ON UPDATE CASCADE ON DELETE CASCADE,
	user_id			bigint REFERENCES users(id) ON UPDATE CASCADE,
	product_id		bigint REFERENCES products(id) ON UPDATE CASCADE,
	coupon_id		bigint REFERENCES campaigns(id) ON UPDATE CASCADE,
	quantity		int NOT NULL DEFAULT 1,
	init_amount		numeric(8,2) NOT NULL,
	init_comm		numeric(8,2) NOT NULL,
	init_discount	numeric(8,2) NOT NULL,
	rec_amount		numeric(8,2) NOT NULL,
	rec_comm		numeric(8,2) NOT NULL,
	rec_discount	numeric(8,2) NOT NULL,
	rec_interval	interval,
	rec_count		int,
	created			datetime NOT NULL DEFAULT NOW(),
	modified		datetime NOT NULL DEFAULT NOW(),
	memo			text NOT NULL DEFAULT '',
	tsv				tsvector NOT NULL,
	CONSTRAINT valid_name
		CHECK ( name <> '' ),
	CONSTRAINT valid_amounts
		CHECK ( init_amount >= init_comm AND init_comm >= 0 AND
				rec_amount >= rec_comm AND rec_comm >= 0 ),
	CONSTRAINT valid_discounts
		CHECK ( init_discount >= 0 AND rec_discount >= 0 ),
	CONSTRAINT valid_coupon
		CHECK ( coupon_id IS NULL AND init_discount = 0 AND rec_discount = 0 OR
			coupon_id IS NOT NULL AND product_id IS NOT NULL AND ( init_discount > 0 OR rec_discount > 0 ) ),
	CONSTRAINT valid_interval
		CHECK ( rec_interval IS NULL AND rec_count IS NULL OR
			rec_interval >= '0' AND ( rec_count IS NULL OR rec_count >= 0 ) ),
	CONSTRAINT valid_flow
		CHECK ( NOT ( due IS NULL AND status > 'draft' ) AND
			NOT ( cleared IS NULL AND status > 'pending' ) ),
	CONSTRAINT undefined_behavior
		CHECK ( rec_count IS NULL AND quantity = 1 )
);

SELECT	timestampable('order_lines'),
		repeatable('order_lines'),
		searchable('order_lines'),
		trashable('order_lines');

CREATE INDEX order_lines_order_id ON order_lines(order_id);
CREATE INDEX order_lines_user_id ON order_lines(user_id);
CREATE INDEX order_lines_product_id ON order_lines(product_id);
CREATE INDEX order_lines_coupon_id ON order_lines(coupon_id);

COMMENT ON TABLE orders IS E'Order lines

- user_id gets shipped; orders.user_id gets billed.
- init/rec amount/comm/discount are auto-filled if not provided.
- init/rec amount/comm are used as is in invoices.
- init/rec discount is only stored for reference; it is used nowhere.
- rec_count gets decremented on cleared payments.
- coupon_id is typically the same as the order''s campaign_id, the
  exception would be in the event of a site-wide promo.';

/**
 * Clean an order line before it gets stored.
 */
CREATE OR REPLACE FUNCTION order_lines_clean()
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
		IF	NEW.product_id IS NOT NULL
		THEN
			SELECT	name
			INTO	NEW.name
			FROM	products
			WHERE	id = NEW.product_id;
		END IF;
		
		IF	NEW.name IS NULL
		THEN
			NEW.name := 'Anonymous Product';
		END IF;
	END IF;
	
	IF	NEW.rec_interval IS NULL AND NEW.rec_count IS NOT NULL
	THEN
		NEW.rec_count := NULL;
	END IF;
	
	-- Assign default dates if needed
	IF	NEW.due IS NULL AND NEW.status > 'draft'
	THEN
		NEW.due := NOW();
	END IF;
	IF	NEW.cleared IS NULL AND NEW.status > 'pending'
	THEN
		NEW.cleared := NOW();
	END IF;
	
	RETURN NEW;
END;
$$ LANGUAGE plpgsql;

CREATE TRIGGER order_lines_05_clean
	BEFORE INSERT OR UPDATE ON order_lines
FOR EACH ROW EXECUTE PROCEDURE order_lines_clean();