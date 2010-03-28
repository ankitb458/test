\echo '#'
\echo '# Testing campaigns'
\echo '#'
\echo

INSERT INTO campaigns DEFAULT VALUES;

SELECT 'Deny deleting non-trashed';
DELETE FROM campaigns;
\echo

INSERT INTO users ( email ) VALUES ( 'joe@bar.com' );

INSERT INTO campaigns ( aff_id )
SELECT	id
FROM	users;

SELECT	*
FROM	users;

SELECT	*
FROM	campaigns;

-- clean up
/*
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