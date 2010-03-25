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

SELECT	campaigns.status = 'inherit' AS check_campaign_inherit
FROM	campaigns;

UPDATE	campaigns
SET		init_discount = 12,
		rec_discount = 12;

SELECT	init_discount = 6 AND rec_discount = 6 as check_autofix_promos_discount
FROM	campaigns
WHERE	aff_id IS NULL;

INSERT INTO users (email) VALUES ('foo@bar.com');

-- UPDATE	campaigns SET aff_id = users.id FROM users; -- must fail

INSERT INTO campaigns(aff_id, init_discount, rec_discount) SELECT id, 12, 12 FROM users;

SELECT	init_discount = 0 AND
		rec_discount = 0
		as check_campaigns_autofix_campaign_discount
FROM	campaigns
WHERE	aff_id IS NOT NULL;

UPDATE	campaigns
SET		init_discount = 12,
		rec_discount = 12,
		product_id = products.id
FROM	products
WHERE	campaigns.aff_id IS NOT NULL;

SELECT	init_discount = 0 AND
		rec_discount = 0
		as check_campaigns_autofix_invalid_coupon_discount
FROM	campaigns
WHERE	aff_id IS NOT NULL;

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
		as check_campaigns_autofix_valid_coupon_discount
FROM	campaigns
WHERE	aff_id IS NOT NULL;
