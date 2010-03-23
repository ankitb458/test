BEGIN;
\i ./reset.sql
\i ./utils.sql
\i ./products.sql
\i ./campaigns.sql

INSERT INTO products DEFAULT VALUES;
INSERT INTO campaigns DEFAULT VALUES;

INSERT INTO products (ukey) VALUES ('test');
INSERT INTO products (ukey) VALUES ('test');

SELECT ukey FROM products;

COMMIT;