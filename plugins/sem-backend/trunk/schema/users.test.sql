\echo
\echo '#'
\echo '# Testing users'
\echo '#'
\echo

INSERT INTO users ( email, password ) VALUES ( 'joe@bar.com', 'joebar' );

SELECT	'Deny deleting non-trashed users';
DELETE FROM users;
\echo

INSERT INTO users ( email ) VALUES ( 'joe@1.2.3.4' );

SELECT	'Deny invalid emails (x2)';
INSERT INTO users ( email ) VALUES ( 'joe@localhost' );
\echo
UPDATE	users
SET		email = 'joe';
\echo


SELECT	'Deny duplicate emails (x2)';
INSERT INTO users ( email ) VALUES ( 'Joe@bar.com' );
\echo
\echo # Note - PGSQL 9 allows the next statement to succeed if the constraint is INITIALLY DEFERRED.
\echo
UPDATE	users
SET		email = 'Joe@bar.com';
\echo

-- clean up
UPDATE	users
SET		status = 'trash'
WHERE	lower(email) <> 'joe@bar.com';

DELETE FROM users
WHERE	status = 'trash';

UPDATE	users
SET		name = '',
		firstname = 'Joe',
		lastname = 'Bar',
		nickname = 'Joe'
WHERE	lower(email) = 'joe@bar.com';

SELECT	'Extract name from first, last and nicknames',
		name = 'Joe Bar'
FROM	users
WHERE	lower(email) = 'joe@bar.com';

SELECT	'Case insensitive search on user names',
		EXISTS(
		SELECT	1
		FROM	users
		WHERE	tsv @@ plainto_tsquery('BAR')
		);

SELECT	'Case insensitive search on user emails',
		EXISTS(
		SELECT	1
		FROM	users
		WHERE	tsv @@ plainto_tsquery('JOE@BAR.COM')
		);

-- clean up
--/*
--\! sleep 3

\echo '# Cleaning up...'
\echo

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