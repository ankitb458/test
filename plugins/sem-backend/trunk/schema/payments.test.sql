\echo
\echo '#'
\echo '# Testing payments'
\echo '#'
\echo

INSERT INTO payments DEFAULT VALUES;

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

INSERT INTO payments (
		status,
		order_id
		)
SELECT	'pending',
		id
FROM	orders;

SELECT	'Autofill payment lines',
		status = 'pending'
FROM	payment_lines;

SELECT	'Autofill payment''s due amount',
		due_amount = 12
FROM	payments;

SELECT	'Delegate payment line status to order lines',
		status = 'pending'
FROM	order_lines;

UPDATE	payments
SET		status = 'cleared',
		cleared_amount = due_amount;

SELECT	'Delegate payment status to lines',
		status = 'cleared'
FROM	payment_lines
WHERE	parent_id IS NULL;

SELECT	'Auto schedule commissions on cleared payments',
		status = 'pending'
FROM	payment_lines
WHERE	parent_id IS NOT NULL;

UPDATE	payments
SET		status = 'reversed',
		cleared_amount = 0
WHERE	order_id IS NOT NULL;

SELECT	'Auto cancel commissions on reversed payments',
		status = 'cancelled'
FROM	payment_lines
WHERE	parent_id IS NOT NULL;

UPDATE	payments
SET		status = 'cleared',
		cleared_amount = due_amount
WHERE	order_id IS NOT NULL;

SELECT	'Auto restore commissions on cancelled reversals',
		status = 'pending'
FROM	payment_lines
WHERE	parent_id IS NOT NULL;

UPDATE	payments
SET		status = 'cleared',
		cleared_amount = due_amount
WHERE	order_id IS NULL;

SELECT	'Allow to advance-pay commissions',
		status = 'cleared'
FROM	payment_lines
WHERE	parent_id IS NOT NULL;

UPDATE	payments
SET		status = 'reversed',
		cleared_amount = 0
WHERE	order_id IS NOT NULL;

SELECT	'Keep a trace of unbalanced payments on reversed commissions',
		cleared_amount = 6
FROM	payments
WHERE	order_id IS NULL;

DELETE FROM payments;

UPDATE	order_lines
SET		status = 'draft',
		rec_price = 3,
		rec_interval = '1 month';

INSERT INTO payments ( status, order_id )
SELECT	'cleared',
		id
FROM	orders;

SELECT	'Auto insert pending recurring payments',
		payment_lines.status = 'pending'
FROM	payments
JOIN	payment_lines
ON		payment_lines.payment_id = payments.id
AND		payments.payment_type = 'revenue'
AND		payment_lines.parent_id IS NOT NULL;

UPDATE	payments
SET		status = 'reversed'
FROM	payment_lines
WHERE	payment_lines.payment_id = payments.id
AND		payments.payment_type = 'revenue'
AND		payment_lines.parent_id IS NULL;

SELECT	'Cancel pending recurring payments on payment reversal',
		payment_lines.status = 'cancelled'
FROM	payments
JOIN	payment_lines
ON		payment_lines.payment_id = payments.id
AND		payments.payment_type = 'revenue'
AND		payment_lines.parent_id IS NOT NULL;

UPDATE	payments
SET		status = 'cleared'
FROM	payment_lines
WHERE	payment_lines.payment_id = payments.id
AND		payments.payment_type = 'revenue'
AND		payment_lines.parent_id IS NULL;

SELECT	'Auto restore recurring payments on cancelled reversals',
		payment_lines.status = 'pending'
FROM	payments
JOIN	payment_lines
ON		payment_lines.payment_id = payments.id
AND		payments.payment_type = 'revenue'
AND		payment_lines.parent_id IS NOT NULL;

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