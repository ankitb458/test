INSERT INTO products ( status, init_price )
VALUES	( 'active', 60 );

--DELETE FROM products;

UPDATE	products
SET		status = 'trash';

DELETE FROM products;