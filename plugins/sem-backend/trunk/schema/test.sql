BEGIN;
\i ./reset.sql
\i ./init.sql

\i ./products.test.sql

INSERT INTO products ( init_price )
VALUES	( 60 );

UPDATE	products
SET		status = 'active';

UPDATE	campaigns
SET		status = 'active',
		init_discount = 30,
		min_date = now() - interval '3 days',
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

