DROP TABLE IF EXISTS packages;

CREATE TABLE packages (
	type				varchar(64) NOT NULL,
	package				varchar(64) NOT NULL,
	url					varchar(255) NOT NULL,
	stable_version		varchar(32) NOT NULL,
	stable_package		varchar(255) NOT NULL,
	stable_modified		date NOT NULL,
	stable_requires		varchar(32) NOT NULL DEFAULT '',
	stable_compat		varchar(32) NOT NULL DEFAULT '',
	bleeding_version	varchar(32) NOT NULL,
	bleeding_package	varchar(255) NOT NULL,
	bleeding_modified	date NOT NULL,
	bleeding_requires	varchar(32) NOT NULL DEFAULT '',
	bleeding_compat		varchar(32) NOT NULL DEFAULT '',
	stable_readme		text NOT NULL DEFAULT '',
	bleeding_readme		text NOT NULL DEFAULT '',
	PRIMARY KEY ( type, package ),
	UNIQUE INDEX stable_packages ( stable_package ),
	UNIQUE INDEX bleeding_packages ( bleeding_package )
) DEFAULT CHARSET=UTF8;


INSERT INTO packages (
	type,
	package,
	url,
	stable_version,
	stable_package,
	stable_modified,
	bleeding_version,
	bleeding_package,
	bleeding_modified
	)
VALUES (
	'core',
	'sem-pro',
	'http://www.semiologic.com/members/sem-pro/',
	'6.0-beta3',
	'http://www.semiologic.com/media/members/sem-pro/download/sem-pro.zip',
	NOW(),
	'6.0-beta3',
	'http://www.semiologic.com/media/members/sem-pro/bleeding/sem-pro-bleeding.zip',
	NOW()
	), (
	'plugins',
	'version-checker',
	'http://www.semiologic.com/software/version-checker/',
	'2.0 RC3',
	'http://www.semiologic.com/media/members/sem-pro/download/version-checker.zip',
	NOW(),
	'2.0 RC3',
	'http://www.semiologic.com/media/members/sem-pro/download/version-checker.zip',
	NOW()
	);