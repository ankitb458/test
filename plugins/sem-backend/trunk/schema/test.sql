BEGIN;
\i ./reset.sql
\i ./utils.sql
\i ./users.sql
\i ./products.sql
\i ./campaigns.sql
\i ./orders.sql
\i ./order-lines.sql
\i ./products.procs.sql

INSERT INTO products ( status, init_price )
VALUES	( 'active', 60 );

UPDATE	campaigns
SET		status = 'active',
		init_discount = 30,
		max_date = now() + interval '1 day',
		max_orders = 3,
		firesale = true;

INSERT INTO campaigns
DEFAULT VALUES;

INSERT INTO orders
DEFAULT VALUES;

INSERT INTO order_lines ( order_id, product_id )
SELECT	orders.id,
		products.id
FROM	orders,
		products;

COMMIT;

--SELECT * FROM products;
--SELECT * FROM campaigns;
--SELECT * FROM orders;
SELECT * FROM order_lines;

