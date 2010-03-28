\echo '#'
\echo '# Testing campaigns'
\echo '#'
\echo

INSERT INTO campaigns DEFAULT VALUES;

SELECT 'Deny deleting non-trashed';
DELETE FROM campaigns;
\echo

INSERT INTO users ( email ) VALUES ( 'joe@bar.com' );

SELECT	'Deny adding campaigns to a non-active user';
INSERT INTO campaigns ( aff_id )
SELECT	id
FROM	users;
\echo

UPDATE	users
SET		status = 'inactive';

INSERT INTO campaigns ( aff_id )
SELECT	id
FROM	users;

SELECT	'Allow assigning a campaign to an inactive user',
		EXISTS(
		SELECT	1
		FROM	users
		);

SELECT	'Deny trashing campaign owner';
UPDATE	users
SET		status = 'trash';
\echo

-- clean up
/*
\! sleep 1

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