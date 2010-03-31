\echo
\echo '#'
\echo '# Testing order lines'
\echo '#'
\echo

INSERT INTO order_lines DEFAULT VALUES;

UPDATE	order_lines
SET		status = 'pending';

SELECT	'Propagate order_line status to order',
		NOT EXISTS (
		SELECT	1
		FROM	order_lines
		WHERE	status <> 'pending'
		);

UPDATE	orders
SET		status = 'draft';

SELECT	'Propagate order status to order_lines',
		NOT EXISTS (
		SELECT	1
		FROM	orders
		WHERE	status <> 'draft'
		);

INSERT INTO users ( status, name, email )
VALUES	( 'trash', 'Joe', 'joe@bar.com' );

SELECT	'Deny using non-active users in order_lines.';
UPDATE	order_lines
SET		user_id = users.id
FROM	users;
\echo

UPDATE	users
SET		status = 'active';

INSERT INTO products ( status ) VALUES ( 'trash' );

SELECT	'Deny using non-active products in order_lines.';
UPDATE	order_lines
SET		product_id = products.id
FROM	products;
\echo

SELECT	'Deny using non-active campaigns in order_lines.';
UPDATE	order_lines
SET		coupon_id = campaigns.id
FROM	campaigns;
\echo

DELETE FROM orders;

UPDATE	products
SET		status = 'active',
		init_price = 12,
		init_comm = 6;

UPDATE	campaigns
SET		status = 'active',
		init_discount = 6;

INSERT INTO order_lines ( product_id )
SELECT	id
FROM	products;

SELECT	'Set coupon on order_line w/ product and w/o promo',
		coupon_id IS NOT NULL AND init_discount = 6
FROM	order_lines;

SELECT	'Set campaign on order_line w/ product and w/o promo',
		campaign_id IS NOT NULL
FROM	orders;

DELETE FROM orders;

INSERT INTO order_lines ( coupon_id )
SELECT	id
FROM	promos;

SELECT	'Set campaign on order_line w/o product and w/ promo',
		campaign_id IS NOT NULL
FROM	orders;

SELECT	'Strip coupon on order_line w/o product and w/ promo',
		coupon_id IS NULL
FROM	order_lines;

DELETE FROM orders;

INSERT INTO campaigns ( status, aff_id, product_id, init_discount )
SELECT	'active',
		users.id,
		products.id,
		3
FROM	users,
		products;

INSERT INTO order_lines ( coupon_id )
SELECT	id
FROM	coupons
WHERE	promo_id IS NULL;

SELECT	'Set campaign on order_line w/o product and w/ coupon',
		campaign_id IS NOT NULL
FROM	orders;

SELECT	'Strip coupon on order_line w/o product and w/ coupon',
		coupon_id IS NULL
FROM	order_lines;

DELETE FROM order_lines;

INSERT INTO order_lines ( order_id, product_id )
SELECT	orders.id,
		products.id
FROM	orders,
		products;

SELECT	'Set coupon on order_line w/ product and w/o coupon',
		coupon_id IS NOT NULL AND init_discount = 3
FROM	order_lines;

DELETE FROM orders;

INSERT INTO order_lines ( product_id, coupon_id )
SELECT	products.id,
		campaigns.id
FROM	products,
		campaigns
WHERE	campaigns.aff_id IS NOT NULL;

SELECT	'Set campaign on order_line w/ product and w/ coupon',
		campaign_id IS NOT NULL AND aff_id IS NOT NULL
FROM	orders;

SELECT	'Set coupon on order_line w/ product and w/ coupon',
		coupon_id IS NOT NULL AND init_discount = 3
FROM	order_lines;

/*
todo:

- try adding a coupon_id with a product
- make sure that aff_id gets set in orders when product_id and coupon_id are supplied
*/

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