BEGIN;
\i ./reset.sql
\i ./utils.sql
\i ./users.sql
\i ./products.sql
\i ./campaigns.sql
\i ./orders.sql

INSERT INTO products DEFAULT VALUES;
INSERT INTO campaigns DEFAULT VALUES;
INSERT INTO orders DEFAULT VALUES;
INSERT INTO order_lines (order_id) SELECT id FROM orders;

COMMIT;

SELECT * FROM orders;

