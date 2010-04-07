\echo
\echo '#'
\echo '# Testing products'
\echo '#'
\echo

INSERT INTO products ( init_price, init_comm, rec_price, rec_comm )
VALUES	( 12, 6, 12, 6 );

UPDATE	products
SET		status = 'trash';

SELECT	'Propagate trash status to promo',
		campaigns.status = 'trash'
FROM	campaigns;

UPDATE	products
SET		status = 'draft';

SELECT	'Propagate draft status to promo',
		campaigns.status = 'draft'
FROM	campaigns;

UPDATE	products
SET		status = 'pending';

SELECT	'Propagate pending status to promo',
		campaigns.status = 'pending'
FROM	campaigns;

UPDATE	products
SET		status = 'inactive';

SELECT	'Propagate inactive status to promo',
		campaigns.status = 'inactive'
FROM	campaigns;

UPDATE	products
SET		status = 'trash';

UPDATE	products
SET		status = 'active';

SELECT	'Propagate active status to promo',
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
FROM	campaigns;

UPDATE	products
SET		init_price = 4,
		rec_price = 4;

SELECT	'Fix product commissions on price update',
		init_comm = 4 AND
		rec_comm = 4
FROM	products;

UPDATE	products
SET		init_price = 18,
		rec_price = 18,
		init_comm = 6,
		rec_comm = 6;

UPDATE	campaigns
SET		init_discount = 4,
		rec_discount = 4;

UPDATE	products
SET		init_price = 12,
		rec_price = 12;

INSERT INTO users ( name, status, email ) VALUES ( 'Joe', 'active', 'foo@bar.com' );

SELECT	'Deny promo ownership:';
UPDATE	campaigns
SET		aff_id = users.id
FROM	users;
\echo

SELECT	'Deny promo product change:';
UPDATE	campaigns
SET		ukey = 'test',
		promo_id = NULL;
\echo

UPDATE	campaigns
SET		status = 'trash';

SELECT	'Fix non-trashed product promo trashing',
		status = 'inactive'
FROM	campaigns;

-- clean up
--/*
--\! sleep 3

\echo '# Cleaning up...'
\echo

-- DELETE FROM transaction_lines;
-- DELETE FROM transactions;
DELETE FROM invoice_lines;
DELETE FROM invoices;
DELETE FROM order_lines;
DELETE FROM orders;
DELETE FROM products;
DELETE FROM campaigns;
DELETE FROM users;
--*/