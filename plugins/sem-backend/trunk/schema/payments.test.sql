\echo
\echo '#'
\echo '# Testing payments'
\echo '#'
\echo

INSERT INTO payments DEFAULT VALUES;
INSERT INTO payment_lines DEFAULT VALUES;

DELETE FROM payments;

INSERT INTO users ( name, email )
VALUES	( 'Joe', 'joe@bar.com' ),
		( 'Jack', 'jack@bar.com' );

INSERT INTO products ( init_price, init_comm ) VALUES ( 12, 6 );

INSERT INTO orders ( user_id, aff_id )
SELECT	u.id,
		a.id
FROM	get_user('joe@bar.com') as u,
		get_user('jack@bar.com') as a;

INSERT INTO order_lines ( order_id, user_id, product_id )
SELECT	orders.id,
		users.id,
		products.id
FROM	orders,
		get_user('joe@bar.com') as users,
		products;

INSERT INTO payment_lines ( order_line_id )
SELECT	id
FROM	order_lines;

UPDATE	payment_lines
SET		cleared_amount = due_amount,
		status = 'cleared';

SELECT	'Delegate payment_line status',
		status = 'cleared'
FROM	payment_lines
WHERE	payment_type = 'order';

SELECT	'Handle commissions for cleared payments',
		status = 'pending'
FROM	payment_lines
WHERE	payment_type = 'comm';

UPDATE	payment_lines
SET		cleared_amount = 0,
		status = 'reversed'
WHERE	payment_type = 'order';

SELECT	'Handle commissions for reversed payments',
		status = 'cancelled'
FROM	payment_lines
WHERE	payment_type = 'comm';

-- clean up
/*
--\! sleep 3

\echo '# Cleaning up...'
\echo

-- DELETE FROM transaction_lines;
-- DELETE FROM transactions;
DELETE FROM payment_lines;
DELETE FROM payments;
DELETE FROM order_lines;
DELETE FROM orders;
DELETE FROM products;
DELETE FROM campaigns;
DELETE FROM users;
--*/