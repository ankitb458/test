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