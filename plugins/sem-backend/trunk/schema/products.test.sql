/**
 * Tests products
 */
CREATE OR REPLACE FUNCTION test_products()
	RETURNS VOID
AS $$
DECLARE
	res			boolean;
BEGIN
	INSERT INTO products
	DEFAULT VALUES;

	UPDATE	products
	SET		status = 'trash';

	DELETE FROM products;

	INSERT INTO products ( init_price, init_comm, rec_price, rec_comm )
	VALUES	( 12, 6, 12, 6 );

	UPDATE	products
	SET		status = 'active';

	UPDATE	products
	SET		status = 'trash';
	
	SELECT	campaigns.status = 'inherit'
	INTO	res
	FROM	campaigns;
	
	IF	res IS NOT TRUE
	THEN
		RAISE WARNING 'Failed: inherit campaign status';
	END IF;

	UPDATE	campaigns
	SET		init_discount = 12,
			rec_discount = 12;
	
	SELECT init_discount = 6 AND
			rec_discount = 6
	INTO	res
	FROM	campaigns
	WHERE	aff_id IS NULL;
	
	IF	res IS NOT TRUE
	THEN
		RAISE WARNING 'Failed: autofix promos discount';
	END IF;

	INSERT INTO users ( email ) VALUES ( 'foo@bar.com' );

	-- UPDATE	campaigns SET aff_id = users.id FROM users; -- must fail

	INSERT INTO campaigns ( aff_id, init_discount, rec_discount ) SELECT id, 12, 12 FROM users;
	
	SELECT	init_discount = 0 AND
			rec_discount = 0
	INTO	res
	FROM	campaigns
	WHERE	aff_id IS NOT NULL;
	
	IF	res IS NOT TRUE
	THEN
		RAISE WARNING	'Failed: autofix campaign discount';
	END IF;
	
	UPDATE	campaigns
	SET		init_discount = 12,
			rec_discount = 12,
			product_id = products.id
	FROM	products
	WHERE	campaigns.aff_id IS NOT NULL;
	
	SELECT	init_discount = 0 AND
			rec_discount = 0
	INTO	res
	FROM	campaigns
	WHERE	aff_id IS NOT NULL;
	
	IF	res IS NOT TRUE
	THEN
		RAISE WARNING 'Failed: autofix invalid coupon discount';
	END IF;

	UPDATE	products
	SET		status = 'active';
	
	UPDATE	campaigns
	SET		init_discount = 12,
			rec_discount = 12,
			product_id = products.id
	FROM	products
	WHERE	campaigns.aff_id IS NOT NULL;

	SELECT	init_discount = 6 AND
			rec_discount = 6
	INTO	res
	FROM	campaigns
	WHERE	aff_id IS NOT NULL;
	
	IF	res IS NOT TRUE
	THEN
		RAISE WARNING 'Failed: autofix valid coupon discount';
	END IF;
	
	-- RETURN;
	
	-- clean up

	UPDATE	products
	SET		status = 'trash';

	DELETE FROM products;

	UPDATE	campaigns
	SET		status = 'trash';

	DELETE FROM campaigns;

	DELETE FROM users;
END $$ LANGUAGE plpgsql;

SELECT	test_products();

DROP FUNCTION test_products();