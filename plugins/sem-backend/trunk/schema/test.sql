BEGIN;
\i ./reset.sql
\i ./utils.sql
\i ./users.sql
\i ./products.sql
\i ./campaigns.sql
\i ./orders.sql

INSERT INTO products (status, init_price) VALUES ('active', 10);
UPDATE campaigns SET status = 'active', init_discount = 5, max_date = now() + interval '1 week', firesale = true;
INSERT INTO campaigns DEFAULT VALUES;
INSERT INTO orders (campaign_id) SELECT	campaigns.id FROM campaigns LIMIT 1;
INSERT INTO order_lines (order_id, product_id) SELECT orders.id, products.id FROM orders, products;

COMMIT;

SELECT * FROM products;
SELECT * FROM campaigns;
SELECT * FROM orders;
SELECT * FROM order_lines;

