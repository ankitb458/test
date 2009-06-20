CREATE TABLE IF NOT EXISTS packages (
	id					int auto_increment,
	type				varchar(64),
	slug				varchar(64),
	url					varchar(255),
	readme				text NOT NULL DEFAULT '',
	stable_version		varchar(32) NOT NULL DEFAULT '',
	stable_requires		varchar(32) NOT NULL DEFAULT '',
	stable_compat		varchar(32) NOT NULL DEFAULT '',
	stable_package		varchar(255),
	bleeding_version	varchar(32) NOT NULL DEFAULT '',
	bleeding_package	varchar(255),
	bleeding_requires	varchar(32) NOT NULL DEFAULT '',
	bleeding_compat		varchar(32) NOT NULL DEFAULT '',
	PRIMARY KEY ( id ),
	UNIQUE KEY ( type, slug )
) DEFAULT CHARSET=UTF8;

INSERT INTO packages ( type, slug, url, stable_version, stable_package, bleeding_version, bleeding_package )
VALUES
	( 'core', 'sem-pro', 'http://www.getsemiologic.com',
		'5.7.1', 'http://www.semiologic.com/members/sem-pro/download/sem-pro.zip',
		'6.0 beta', 'http://www.semiologic.com/media/members/sem-pro/bleeding/sem-pro-dev.zip'
	),
	( 'themes', 'sem-reloaded', 'http://www.semiologic.com/software/sem-reloaded/',
		'0.5', 'http://downloads.wordpress.org/themes/sem-reloaded.zip',
		'2.0 RC2', 'http://www.semiologic.com/media/software/sem-reloaded/sem-reloaded-dev.zip'
	),
	( 'plugins', 'ad-manager', 'http://www.semiologic.com/software/ad-manager/',
		'1.2', 'http://www.semiologic.com/members/plugins/ad-manager/ad-manager.zip',
		'2.0 RC2', 'http://www.semiologic.com/media/members/plugins/ad-manager/ad-manager-dev.zip'
	),
	( 'plugins', 'version-checker', 'http://www.semiologic.com/software/version-checker/',
		'2.0', 'http://www.semiologic.com/members/plugins/version-checker/version-checker.zip',
		'2.0', 'http://www.semiologic.com/media/members/plugins/version-checker/version-checker-dev.zip'
	);