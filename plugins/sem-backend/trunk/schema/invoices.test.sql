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
FROM	invoice_lines
WHERE	parent_id IS NULL;

SELECT	'Auto schedule commissions on cleared payments',
		status = 'pending'
FROM	invoice_lines
WHERE	parent_id IS NOT NULL;

UPDATE	invoices
SET		status = 'reversed',
		cleared_amount = 0
WHERE	order_id IS NOT NULL;

SELECT	'Auto cancel commissions on reversed payments',
		status = 'cancelled'
FROM	invoice_lines
WHERE	parent_id IS NOT NULL;

UPDATE	invoices
SET		status = 'cleared',
		cleared_amount = due_amount
WHERE	order_id IS NOT NULL;

SELECT	'Auto restore commissions on cancelled reversals',
		status = 'pending'
FROM	invoice_lines
WHERE	parent_id IS NOT NULL;

UPDATE	invoices
SET		status = 'cleared',
		cleared_amount = due_amount
WHERE	order_id IS NULL;

SELECT	'Allow to advance-pay commissions',
		status = 'cleared'
FROM	invoice_lines
WHERE	parent_id IS NOT NULL;

UPDATE	invoices
SET		status = 'reversed',
		cleared_amount = 0
WHERE	order_id IS NOT NULL;

SELECT	'Keep a trace of unbalanced payments on reversed commissions',
		cleared_amount = 6
FROM	invoices
WHERE	order_id IS NULL;

DELETE FROM invoices;

UPDATE	order_lines
SET		status = 'draft',
		rec_price = 3,
		rec_interval = '1 month';

INSERT INTO invoices ( status, order_id )
SELECT	'cleared',
		id
FROM	orders;

SELECT	'Auto insert pending recurring invoices',
		invoice_lines.status = 'pending'
FROM	invoices
JOIN	invoice_lines
ON		invoice_lines.invoice_id = invoices.id
AND		invoices.invoice_type = 'revenue'
AND		invoice_lines.parent_id IS NOT NULL;

UPDATE	invoices
SET		status = 'reversed'
FROM	invoice_lines
WHERE	invoice_lines.invoice_id = invoices.id
AND		invoices.invoice_type = 'revenue'
AND		invoice_lines.parent_id IS NULL;

SELECT	'Cancel pending recurring invoices on payment reversal',
		invoice_lines.status = 'cancelled'
FROM	invoices
JOIN	invoice_lines
ON		invoice_lines.invoice_id = invoices.id
AND		invoices.invoice_type = 'revenue'
AND		invoice_lines.parent_id IS NOT NULL;

UPDATE	invoices
SET		status = 'cleared'
FROM	invoice_lines
WHERE	invoice_lines.invoice_id = invoices.id
AND		invoices.invoice_type = 'revenue'
AND		invoice_lines.parent_id IS NULL;

SELECT	'Auto restore recurring invoices on cancelled reversals',
		invoice_lines.status = 'pending'
FROM	invoices
JOIN	invoice_lines
ON		invoice_lines.invoice_id = invoices.id
AND		invoices.invoice_type = 'revenue'
AND		invoice_lines.parent_id IS NOT NULL;

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