CREATE TABLE IF NOT EXISTS api_logs (
	log_id			bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
	log_date		datetime NOT NULL,
	api_key			varchar(64) NOT NULL DEFAULT '',
	site_ip			varchar(64) NOT NULL DEFAULT '',
	site_url		varchar(255) NOT NULL DEFAULT '',
	wp_version		varchar(32) NOT NULL DEFAULT '',
	php_version		varchar(32) NOT NULL DEFAULT '',
	mysql_version	varchar(32) NOT NULL DEFAULT '',
	locale			varchar(32) NOT NULL DEFAULT '',
	PRIMARY KEY ( log_id ),
	KEY ( api_key ),
	KEY ( site_url )
) DEFAULT CHARSET=UTF8;
