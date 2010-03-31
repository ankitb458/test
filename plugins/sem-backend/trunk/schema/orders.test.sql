\echo
\echo '#'
\echo '# Testing orders'
\echo '#'
\echo

INSERT INTO orders DEFAULT VALUES;

INSERT INTO users ( status, name, email )
VALUES	( 'trash', 'Joe', 'joe@bar.com' ),
		( 'trash', 'Jack', 'jack@bar.com' );

SELECT	'Deny non-active users in orders:';
UPDATE	orders
SET		user_id = users.id,
		aff_id = affs.id
FROM	get_user('joe@bar.com') as users,
		get_user('jack@bar.com') as affs;
\echo

INSERT INTO campaigns ( status ) VALUES ( 'trash' );

SELECT	'Deny non-active campaigns in orders:';
UPDATE	orders
SET		campaign_id = campaigns.id
FROM	campaigns;
\echo

UPDATE	users
SET		status = 'inactive';

SELECT	'Warn that user_id = aff_id';
UPDATE	orders
SET		user_id = users.id,
		aff_id = affs.id
FROM	get_user('joe@bar.com') as users,
		get_user('joe@bar.com') as affs;
\echo

-- clean up
--/*
--\! sleep 3

\echo '# Cleaning up...'
\echo

DELETE FROM order_lines;
DELETE FROM orders;
DELETE FROM products;
DELETE FROM campaigns;
DELETE FROM users;
--*/