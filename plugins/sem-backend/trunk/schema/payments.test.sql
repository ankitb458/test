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

SELECT	payments.id,
		payments.status,
		payments.payment_type,
		payments.user_id,
		payments.order_id,
		payments.due_date::date,
		payment_lines.id,
		payment_lines.status,
		payment_lines.order_line_id,
		payment_lines.parent_id,
		payment_lines.amount
FROM	payments
JOIN	payment_lines
ON		payment_lines.payment_id = payments.id
ORDER BY payments.id,
		payment_lines.id;

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