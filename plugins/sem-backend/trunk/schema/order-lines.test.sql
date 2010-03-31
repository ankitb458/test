\echo
\echo '#'
\echo '# Testing order lines'
\echo '#'
\echo

INSERT INTO order_lines DEFAULT VALUES;

UPDATE	order_lines
SET		status = 'pending';

SELECT	'Propagate order_line status to order',
		NOT EXISTS (
		SELECT	1
		FROM	order_lines
		WHERE	status <> 'pending'
		);

UPDATE	orders
SET		status = 'draft';

SELECT	'Propagate order status to order_lines',
		NOT EXISTS (
		SELECT	1
		FROM	orders
		WHERE	status <> 'draft'
		);

INSERT INTO users ( status, name, email )
VALUES	( 'trash', 'Joe', 'joe@bar.com' );

SELECT	'Deny using non-active users in order_lines.';
UPDATE	order_lines
SET		user_id = users.id
FROM	users;
\echo

UPDATE	users
SET		status = 'active';

INSERT INTO products ( status ) VALUES ( 'trash' );

SELECT	'Deny using non-active products in order_lines.';
UPDATE	order_lines
SET		product_id = products.id
FROM	products;
\echo

SELECT	'Deny using non-active campaigns in order_lines.';
UPDATE	order_lines
SET		coupon_id = campaigns.id
FROM	campaigns;
\echo

DELETE FROM orders;

UPDATE	products
SET		status = 'active',
		init_price = 12;

UPDATE	campaigns
SET		status = 'active',
		init_discount = 6;

INSERT INTO order_lines ( product_id )
SELECT	id
FROM	products;

SELECT	'Autoset discount on campaign-less promos',
		( coupon_id IS NOT NULL AND init_discount = 6 )
FROM	order_lines;


/*
todo:

- try adding a coupon_id with/without a product
- make sure that aff_id gets set in orders when product_id and coupon_id are supplied
*/

-- clean up
/*
--\! sleep 3

\echo '# Cleaning up...'
\echo

DELETE FROM order_lines;
DELETE FROM orders;
DELETE FROM products;
DELETE FROM campaigns;
DELETE FROM users;
--*/