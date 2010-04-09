/*
 * Order lines
 */
CREATE TABLE order_lines (
	id				bigserial PRIMARY KEY,
	uuid			uuid NOT NULL DEFAULT uuid() UNIQUE,
	status			status_payable NOT NULL DEFAULT 'draft',
	name			varchar NOT NULL,
	order_id		bigint NOT NULL REFERENCES orders(id) ON DELETE CASCADE,
	user_id			bigint REFERENCES users(id),
	product_id		bigint REFERENCES products(id),
	coupon_id		bigint REFERENCES campaigns(id),
	quantity		int NOT NULL DEFAULT 1,
	init_price		numeric(8,2) NOT NULL,
	init_comm		numeric(8,2) NOT NULL,
	init_discount	numeric(8,2) NOT NULL,
	rec_price		numeric(8,2) NOT NULL,
	rec_comm		numeric(8,2) NOT NULL,
	rec_discount	numeric(8,2) NOT NULL,
	rec_interval	interval,
	rec_count		int,
	created			datetime NOT NULL DEFAULT NOW(),
	modified		datetime NOT NULL DEFAULT NOW(),
	CONSTRAINT valid_name
		CHECK ( name <> '' AND name = trim(name) ),
	CONSTRAINT valid_amounts
		CHECK ( init_price >= init_comm + init_discount AND init_comm >= 0 AND init_discount >= 0 AND
				rec_price >= rec_comm + rec_discount AND rec_comm >= 0 AND rec_discount >= 0 ),
	CONSTRAINT valid_coupon
		CHECK ( coupon_id IS NULL AND init_discount = 0 AND rec_discount = 0 OR
			coupon_id IS NOT NULL AND product_id IS NOT NULL AND ( init_discount > 0 OR rec_discount > 0 ) ),
	CONSTRAINT valid_interval
		CHECK ( rec_interval IS NULL AND rec_count IS NULL OR rec_interval >= '0' ),
	CONSTRAINT unsupported_behavior
		CHECK ( rec_count IS NULL AND quantity = 1 )
);

SELECT	timestampable('order_lines'),
		repeatable('order_lines'),
		trashable('order_lines');

CREATE INDEX order_lines_order_id ON order_lines(order_id);
CREATE INDEX order_lines_user_id ON order_lines(user_id);
CREATE INDEX order_lines_product_id ON order_lines(product_id);
CREATE INDEX order_lines_coupon_id ON order_lines(coupon_id);

COMMENT ON TABLE orders IS E'Order lines

- user_id gets shipped; orders.user_id gets invoiced.
- due and cleared dates have absolutely no relationship with one another.
  It is possible to advance pay or late pay...
- init/rec amount/comm/discount are auto-filled if not provided.
- init/rec amount/comm are used as is in invoices.
- init/rec discount is only stored for reference; it is used nowhere.
- rec_count gets decremented on cleared_date invoices.
- coupon_id is typically the same as the order''s campaign_id, the
  exception would be in the event of a site-wide promo.';

/**
 * Clean an order line before it gets stored.
 */
CREATE OR REPLACE FUNCTION order_lines_clean()
	RETURNS trigger
AS $$
BEGIN
	-- Default name
	IF	NEW.name IS NULL
	THEN
		IF	NEW.product_id IS NOT NULL
		THEN
			SELECT	name
			INTO	NEW.name
			FROM	products
			WHERE	id = NEW.product_id;
		ELSE
			NEW.name := 'Anonymous Product';
		END IF;
	END IF;
	
	IF	NEW.rec_interval IS NULL AND NEW.rec_count IS NOT NULL
	THEN
		NEW.rec_count := NULL;
	END IF;
	
	RETURN NEW;
END;
$$ LANGUAGE plpgsql;

CREATE TRIGGER order_lines_05_clean
	BEFORE INSERT OR UPDATE ON order_lines
FOR EACH ROW EXECUTE PROCEDURE order_lines_clean();

/**
 * Process read-only fields
 */
CREATE OR REPLACE FUNCTION order_lines_readonly()
	RETURNS trigger
AS $$
BEGIN
	IF	ROW(NEW.id, NEW.order_id) IS DISTINCT FROM ROW(OLD.id, OLD.order_id)
	THEN
		RAISE EXCEPTION 'Can''t edit readonly field in order_lines.id = %', NEW.id;
	END IF;
	
	RETURN NEW;
END $$ LANGUAGE plpgsql;

CREATE CONSTRAINT TRIGGER order_lines_01_readonly
	AFTER UPDATE ON order_lines
FOR EACH ROW EXECUTE PROCEDURE order_lines_readonly();