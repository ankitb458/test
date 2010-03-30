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

SELECT	'Warn that billing_id = aff_id';
UPDATE	orders
SET		billing_id = users.id,
		aff_id = affs.id
FROM	get_user('joe@bar.com') as users,
		get_user('joe@bar.com') as affs;
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