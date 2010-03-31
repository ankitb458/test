\echo
\echo '#'
\echo '# Testing campaigns'
\echo '#'
\echo

INSERT INTO campaigns DEFAULT VALUES;

INSERT INTO users ( email, password ) VALUES ( 'joe@bar.com', 'joebar' );

SELECT	'Deny adding campaigns to a non-active user:';
INSERT INTO campaigns ( aff_id )
SELECT	id
FROM	users;
\echo

UPDATE	users
SET		status = 'inactive';

INSERT INTO campaigns ( aff_id )
SELECT	id
FROM	users;

SELECT	'Allow assigning a campaign to an inactive user',
		EXISTS (
		SELECT	1
		FROM	users
		);

SELECT	'Deny trashing campaign owner:';
UPDATE	users
SET		status = 'trash';
\echo

INSERT INTO products ( init_price, init_comm, rec_price, rec_comm ) VALUES ( 12, 6, 12, 6 );

UPDATE	campaigns
SET		init_discount = 12,
		rec_discount = 12
WHERE	promo_id IS NOT NULL;

SELECT	'Allow creating a promo on an inactive product',
		init_discount = 6 AND rec_discount = 6
FROM	campaigns
WHERE	promo_id IS NOT NULL;

SELECT	'Deny creating a coupon on an inactive product (x2):';
UPDATE	campaigns
SET		product_id = products.id,
		init_discount = 12,
		rec_discount = 12
FROM	products
WHERE	aff_id IS NULL AND promo_id IS NULL;
\echo
UPDATE	campaigns
SET		product_id = products.id,
		init_discount = 12,
		rec_discount = 12
FROM	products
WHERE	aff_id IS NOT NULL;
\echo

UPDATE	products
SET		status = 'future',
		min_date = NOW() + interval '1 week';

UPDATE	campaigns
SET		product_id = products.id,
		init_discount = 12,
		rec_discount = 12
FROM	products;

SELECT	'Allow creating a coupon on a future posted product',
		NOT EXISTS (
		SELECT	1
		FROM	campaigns
		WHERE	init_discount <> 6
		OR		rec_discount <> 6
		);

UPDATE	products
SET		init_comm = 8,
		rec_comm = 8;

SELECT	'Change aff discounts on price changes',
		status = 'pending' AND
		init_discount = 8 AND
		rec_discount = 8
FROM	campaigns
WHERE	aff_id IS NOT NULL;

SELECT	'Change non-aff discounts on price changes',
		NOT EXISTS (
		SELECT	1
		FROM	campaigns
		WHERE	aff_id IS NULL
		AND		( init_discount <> 4 OR rec_discount <> 4 )
		);

UPDATE	products
SET		status = 'trash';

SELECT	'Invalidate coupons on product trash',
		NOT EXISTS (
		SELECT	1
		FROM	campaigns
		WHERE	promo_id IS NULL
		AND		product_id IS NOT NULL
		);

UPDATE	products
SET		status = 'active';

UPDATE	campaigns
SET		status = 'inactive',
		product_id = products.id,
		init_discount = 0,
		rec_discount = 0
FROM	products;

SELECT	'Allow for inactive coupons with no discounts',
		NOT EXISTS (
		SELECT	1
		FROM	campaigns
		WHERE	( product_id IS NULL OR status <> 'inactive' )
		);

UPDATE	campaigns
SET		status = 'active',
		init_discount = 8,
		rec_discount = 8;

UPDATE	products
SET		status = 'future',
		min_date = NOW() + interval '1 week';

SELECT	'Allow for active coupons on future products',
		NOT EXISTS (
		SELECT	1
		FROM	campaigns
		WHERE	( product_id IS NULL OR status <> 'active' )
		);

-- clean up
--/*
--\! sleep 3

\echo '# Cleaning up...'
\echo

DELETE FROM order_lines;
DELETE FROM orders;
DELETE FROM products;
DELETE FROM campaigns;
DELETE FROM users;
--*/