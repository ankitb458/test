\echo '***'
\echo '*** products.test.sql'
\echo '***'
\echo

INSERT INTO products
DEFAULT VALUES;

UPDATE	products
SET		status = 'trash';

DELETE FROM products;

INSERT INTO products ( init_price, init_comm, rec_price, rec_comm )
VALUES	( 12, 6, 12, 6 );

UPDATE	products
SET		status = 'active';

UPDATE	products
SET		status = 'trash';

SELECT	campaigns.status = 'inherit'
AS		"Campaign Inherits trash status"
FROM	campaigns;

UPDATE	products
SET		status = 'draft';

SELECT	campaigns.status = 'draft'
AS		"Campaign Inherits draft status"
FROM	campaigns;

UPDATE	products
SET		status = 'pending';

SELECT	campaigns.status = 'pending'
AS		"Campaign Inherits pending status"
FROM	campaigns;

UPDATE	products
SET		status = 'inactive';

SELECT	campaigns.status = 'inactive'
AS		"Campaign Inherits inactive status"
FROM	campaigns;

UPDATE	campaigns
SET		init_discount = 12,
		rec_discount = 12;

SELECT init_discount = 6 AND
		rec_discount = 6
AS		"Fix promo discount"
FROM	campaigns
WHERE	aff_id IS NULL;

INSERT INTO users ( status, email ) VALUES ( 'active', 'foo@bar.com' );

-- UPDATE	campaigns SET aff_id = users.id FROM users; -- must fail

INSERT INTO campaigns ( aff_id, init_discount, rec_discount ) SELECT id, 12, 12 FROM users;

SELECT	init_discount = 0 AND
		rec_discount = 0
AS		"Fix coupon discount"
FROM	campaigns
WHERE	aff_id IS NOT NULL;

UPDATE	campaigns
SET		init_discount = 12,
		rec_discount = 12,
		product_id = products.id
FROM	products
WHERE	campaigns.aff_id IS NOT NULL;

SELECT	init_discount = 0 AND
		rec_discount = 0
AS		"Fix invalid coupon discount"
FROM	campaigns
WHERE	aff_id IS NOT NULL;

UPDATE	products
SET		status = 'active';

UPDATE	campaigns
SET		init_discount = 12,
		rec_discount = 12,
		product_id = products.id
FROM	products
WHERE	campaigns.aff_id IS NOT NULL;

SELECT	init_discount = 6 AND
		rec_discount = 6
AS		"Fix valid coupon discount"
FROM	campaigns
WHERE	aff_id IS NOT NULL;

-- clean up

UPDATE	products
SET		status = 'trash';

DELETE FROM products;

UPDATE	campaigns
SET		status = 'trash';

DELETE FROM campaigns;

UPDATE	users
SET		status = 'trash';

DELETE FROM users;