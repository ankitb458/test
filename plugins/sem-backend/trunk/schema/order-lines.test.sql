\echo
\echo '#'
\echo '# Testing order lines'
\echo '#'
\echo

INSERT INTO order_lines DEFAULT VALUES;

INSERT INTO order_lines (status) VALUES ('trash');

UPDATE	order_lines
SET		status = 'pending';

UPDATE	orders
SET		status = 'draft';

SELECT	*
FROM	order_lines;

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