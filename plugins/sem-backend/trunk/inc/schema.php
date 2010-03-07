<?php
add_option('fancy_currency', __('$%s', 'sem-backend'));

$role =& get_role('administrator');
$role->add_cap('manage_products');
$role->add_cap('manage_campaigns');
$role->add_cap('manage_orders');
$role->add_cap('manage_transactions');
$role->add_cap('manage_memberships');

global $wpdb;
$charset_collate = '';
if ( !empty($wpdb->charset) )
	$charset_collate = "DEFAULT CHARACTER SET $wpdb->charset";
if ( !empty($wpdb->collate) )
	$charset_collate .= " COLLATE $wpdb->collate";

$schema = <<<EOS
CREATE TABLE $wpdb->products (
	id				bigint unsigned AUTO_INCREMENT,
	uuid			char(36) NOT NULL,
	ukey			varchar(255),
	status			enum('trash', 'draft', 'pending', 'inactive', 'future', 'active') NOT NULL DEFAULT 'draft',
	name			varchar(255) NOT NULL DEFAULT '',
	init_price		decimal(8,2) unsigned NOT NULL DEFAULT 0,
	init_comm		decimal(8,2) unsigned NOT NULL DEFAULT 0,
	rec_price		decimal(8,2) unsigned NOT NULL DEFAULT 0,
	rec_comm		decimal(8,2) unsigned NOT NULL DEFAULT 0,
	rec_interval	enum('', 'month', 'quarter', 'year') NOT NULL DEFAULT '',
	min_date		datetime,
	max_date		datetime,
	max_orders		int unsigned,
	created_date	datetime NOT NULL,
	modified_date	datetime NOT NULL,
	memo			text NOT NULL DEFAULT '',
	PRIMARY KEY  (id),
	KEY products_status (status),
	UNIQUE KEY products_uuid (uuid),
	UNIQUE KEY products_ukey (ukey)
) $charset_collate;

CREATE TABLE $wpdb->campaigns (
	id				bigint unsigned AUTO_INCREMENT,
	uuid			char(36) NOT NULL,
	ukey			varchar(255),
	status			enum('trash', 'inherit', 'draft', 'pending', 'inactive', 'future', 'active') NOT NULL DEFAULT 'draft',
	name			varchar(255) NOT NULL DEFAULT '',
	aff_id			bigint unsigned,
	product_id		bigint unsigned,
	init_discount	decimal(8,2) unsigned NOT NULL DEFAULT 0,
	rec_discount	decimal(8,2) unsigned NOT NULL DEFAULT 0,
	min_date		datetime,
	max_date		datetime,
	max_orders		int unsigned,
	firesale		tinyint(1) NOT NULL DEFAULT 0,
	created_date	datetime NOT NULL,
	modified_date	datetime NOT NULL,
	memo			text NOT NULL DEFAULT '',
	PRIMARY KEY  (id),
	KEY campaigns_status (status),
	UNIQUE KEY campaigns_uuid (uuid),
	UNIQUE KEY campaigns_ukey (ukey),
	KEY campaigns_aff_id (aff_id),
	KEY campaigns_product_id (product_id)
) $charset_collate;

CREATE TABLE $wpdb->orders (
	id				bigint unsigned AUTO_INCREMENT,
	uuid			char(36) NOT NULL,
	ukey			varchar(255),
	status			enum('trash', 'draft', 'pending', 'cancelled', 'reversed', 'cleared') NOT NULL DEFAULT 'draft',
	name			varchar(255) NOT NULL DEFAULT '',
	init_price		decimal(8,2) unsigned NOT NULL DEFAULT 0,
	init_comm		decimal(8,2) unsigned NOT NULL DEFAULT 0,
	init_discount	decimal(8,2) unsigned NOT NULL DEFAULT 0,
	rec_price		decimal(8,2) unsigned NOT NULL DEFAULT 0,
	rec_comm		decimal(8,2) unsigned NOT NULL DEFAULT 0,
	rec_discount	decimal(8,2) unsigned NOT NULL DEFAULT 0,
	rec_interval	enum('', 'month', 'quarter', 'year') NOT NULL DEFAULT '',
	user_id			bigint unsigned NOT NULL,
	billing_id		bigint unsigned NOT NULL,
	product_id		bigint unsigned,
	aff_id			bigint unsigned,
	campaign_id		bigint unsigned,
	coupon_id		bigint unsigned,
	created_date	datetime NOT NULL,
	modified_date	datetime NOT NULL,
	memo			text NOT NULL DEFAULT '',
	PRIMARY KEY  (id),
	KEY orders_status (status),
	UNIQUE KEY orders_uuid (uuid),
	UNIQUE KEY orders_ukey (ukey),
	KEY orders_user_id (user_id),
	KEY orders_billing_id (billing_id),
	KEY orders_product_id (product_id),
	KEY orders_aff_id (aff_id),
	KEY orders_campaign_id (campaign_id),
	KEY orders_coupon_id (coupon_id)
) $charset_collate;

EOS;

$schema = str_replace("\t", ' ', $schema);

$wpdb->show_errors();
dbDelta($schema);
?>