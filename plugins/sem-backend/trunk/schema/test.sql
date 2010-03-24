BEGIN;
\i ./reset.sql
\i ./utils.sql
\i ./users.sql
\i ./products.sql
\i ./campaigns.sql
--\i ./orders.sql

INSERT INTO products (status, init_price) VALUES ('active', 10);
UPDATE campaigns SET status = 'active', init_discount = 5, max_date = now() + interval '1 week', firesale = true;
INSERT INTO campaigns DEFAULT VALUES;
--INSERT INTO orders (product_id) SELECT products.id FROM products LIMIT 1;

COMMIT;

SELECT * FROM products;
SELECT * FROM campaigns;
--SELECT * FROM orders;

