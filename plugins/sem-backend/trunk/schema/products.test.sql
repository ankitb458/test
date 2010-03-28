\echo '#'
\echo '# products.test.sql'
\echo '#'
\echo

INSERT INTO products ( init_price, init_comm, rec_price, rec_comm )
VALUES	( 12, 6, 12, 6 );

SELECT 'Deny deleting non-trashed';
DELETE FROM products;
\echo

UPDATE	products
SET		status = 'trash';

SELECT	'Campaign Inherits trash status',
		campaigns.status = 'inherit'
FROM	campaigns;

UPDATE	products
SET		status = 'draft';

SELECT	'Campaign Inherits draft status',
		campaigns.status = 'draft'
FROM	campaigns;

UPDATE	products
SET		status = 'pending';

SELECT	'Campaign Inherits pending status',
		campaigns.status = 'pending'
FROM	campaigns;

UPDATE	products
SET		status = 'inactive';

SELECT	'Campaign Inherits inactive status',
		campaigns.status = 'inactive'
FROM	campaigns;

UPDATE	campaigns
SET		init_discount = 12,
		rec_discount = 12;

SELECT	'Fix campaign discounts',
		init_comm = 6 AND
		rec_comm = 6
FROM	products;

UPDATE	products
SET		init_price = 9,
		rec_price = 9;

SELECT	'Fix promo discounts on price/comm update',
		init_discount = 3 AND
		rec_discount = 3
FROM	campaigns
WHERE	aff_id IS NULL;

UPDATE	products
SET		init_price = 3,
		rec_price = 3;

SELECT	'Fix product commissions on price update',
		init_comm = 3 AND
		rec_comm = 3
FROM	products;

SELECT	'Fix promo discounts on price/comm update',
		init_discount = 0 AND
		rec_discount = 0
FROM	campaigns
WHERE	aff_id IS NULL;

INSERT INTO users ( status, email ) VALUES ( 'active', 'foo@bar.com' );

SELECT	'Deny promo ownership';
UPDATE	campaigns
SET		aff_id = users.id
FROM	users;
\echo

INSERT INTO campaigns ( aff_id, init_discount, rec_discount ) SELECT id, 12, 12 FROM users;

SELECT	'Fix coupon discount',
		init_discount = 0 AND
		rec_discount = 0
FROM	campaigns
WHERE	aff_id IS NOT NULL;

UPDATE	campaigns
SET		init_discount = 12,
		rec_discount = 12,
		product_id = products.id
FROM	products
WHERE	campaigns.aff_id IS NOT NULL;

SELECT	'Fix invalid coupon discount',
		init_discount = 0 AND
		rec_discount = 0
FROM	campaigns
WHERE	aff_id IS NOT NULL;

UPDATE	products
SET		status = 'active';

UPDATE	products
SET		init_price = 12,
		rec_price = 12,
		init_comm = 6,
		rec_comm = 6;

UPDATE	campaigns
SET		init_discount = 12,
		rec_discount = 12,
		product_id = products.id
FROM	products
WHERE	campaigns.aff_id IS NOT NULL;

SELECT	'Fix valid coupon discount',
		init_discount = 6 AND
		rec_discount = 6
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