INSERT INTO products ( status, init_price )
VALUES	( 'active', 60 );

UPDATE	products
SET		status = 'trash';

DELETE FROM products;

INSERT INTO products ( init_price )
VALUES	( 60 );

UPDATE	products
SET		status = 'active';

UPDATE	products
SET		status = 'trash';

SELECT	campaigns.status = 'inherit' AS check_campaign_inherit FROM campaigns;