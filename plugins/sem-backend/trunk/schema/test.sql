BEGIN;
\i ./reset.sql
\i ./utils.sql
\i ./users.sql
\i ./products.sql
\i ./campaigns.sql
\i ./orders.sql
\i ./order-lines.sql

INSERT INTO products (status, init_price) VALUES ('active', 10);
UPDATE campaigns SET status = 'active', init_discount = 5, max_date = now() + interval '1 week', firesale = true;
INSERT INTO campaigns DEFAULT VALUES;
INSERT INTO order_lines (product_id) SELECT products.id FROM products;

COMMIT;

--SELECT * FROM products;
--SELECT * FROM campaigns;
SELECT * FROM orders;
SELECT * FROM order_lines;

