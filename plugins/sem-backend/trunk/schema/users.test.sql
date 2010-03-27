\echo '***'
\echo '*** users.test.sql'
\echo '***'
\echo

INSERT INTO users ( email ) VALUES ( 'joe@bar.com' );

SELECT	NULL
AS		"Deny Delete non-trashed user";
DELETE FROM users;

INSERT INTO users ( email ) VALUES ( 'joe@1.2.3.4' );

SELECT	NULL
AS		"Deny Invalid email x 2";
INSERT INTO users ( email ) VALUES ( 'joe@localhost' );
UPDATE	users
SET		email = 'joe';

SELECT	NULL
AS		"Deny Duplicate email x2";
INSERT INTO users ( email ) VALUES ( 'Joe@bar.com' );
UPDATE	users
SET		email = 'Joe@bar.com';

-- clean up
UPDATE	users
SET		status = 'trash'
WHERE	lower(email) <> 'joe@bar.com';

DELETE FROM users
WHERE	status = 'trash';

UPDATE	users
SET		firstname = 'Joe',
		lastname = 'Bar',
		nickname = 'joe'
WHERE	lower(email) = 'joe@bar.com';

SELECT	name = 'Joe Bar'
AS		"Extract name from first, last and nicknames"
FROM	users
WHERE	lower(email) = 'joe@bar.com';

SELECT	EXISTS(
		SELECT	1
		FROM	users
		WHERE	tsv @@ plainto_tsquery('BAR')
		)
AS		"Case insensitive search on user names";

SELECT	EXISTS(
		SELECT	1
		FROM	users
		WHERE	tsv @@ plainto_tsquery('JOE@BAR.COM')
		)
AS		"Case insensitive search on user emails";

-- clean up
UPDATE	users
SET		status = 'trash';

DELETE FROM users;