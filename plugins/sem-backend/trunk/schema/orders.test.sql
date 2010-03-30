\echo
\echo '#'
\echo '# Testing orders'
\echo '#'
\echo

INSERT INTO orders DEFAULT VALUES;

SELECT 'Deny deleting non-trashed orders';
DELETE FROM orders;
\echo

INSERT INTO users ( name, email )
VALUES	( 'Joe', 'joe@bar.com' ),
		( 'Jack', 'jack@bar.com' );

UPDATE	orders
SET		billing_id = users.id
FROM	get_user('joe@bar.com') as users;

UPDATE	orders
SET		status = 'trash';

SELECT	'Allow trashing draft orders that have a billing_id.',
		status = 'trash'
FROM	orders;
\echo


-- clean up
/*
--\! sleep 3

\echo '# Cleaning up...'
\echo

UPDATE	orders
SET		status = 'trash';

DELETE FROM orders;

UPDATE	products
SET		status = 'trash';

DELETE FROM products;

UPDATE	campaigns
SET		status = 'trash';

DELETE FROM campaigns;

UPDATE	users
SET		status = 'trash';

DELETE FROM users;
--*/