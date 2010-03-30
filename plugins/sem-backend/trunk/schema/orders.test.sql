\echo
\echo '#'
\echo '# Testing orders'
\echo '#'
\echo

INSERT INTO orders DEFAULT VALUES;

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

DELETE FROM order_lines;
DELETE FROM orders;
DELETE FROM products;
DELETE FROM campaigns;
DELETE FROM users;
--*/