\echo
\echo '#'
\echo '# Testing invoices'
\echo '#'
\echo

INSERT INTO invoices DEFAULT VALUES;

DELETE FROM invoices;

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

INSERT INTO invoices (
		status,
		order_id
		)
SELECT	'pending',
		id
FROM	orders;

SELECT	'Autofill invoice lines',
		status = 'pending'
FROM	invoice_lines;

SELECT	'Autofill invoice''s due amount',
		due_amount = 12
FROM	invoices;

SELECT	'Delegate invoice line status to order lines',
		status = 'pending'
FROM	order_lines;

UPDATE	invoices
SET		status = 'cleared',
		cleared_amount = due_amount;

SELECT	'Delegate invoice status to lines',
		status = 'cleared'
FROM	invoice_lines;

SELECT	invoices.id,
		invoices.status,
		invoices.user_id,
		invoices.order_id,
		invoices.due_date::date,
		invoice_lines.id,
		invoice_lines.status,
		invoice_lines.order_line_id,
		invoice_lines.parent_id,
		invoice_lines.amount
FROM	invoices
JOIN	invoice_lines
ON		invoice_lines.invoice_id = invoices.id
ORDER BY invoices.id,
		invoice_lines.id;


-- clean up
/*
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