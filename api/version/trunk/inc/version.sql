CREATE TABLE IF NOT EXISTS packages (
	id					int auto_increment,
	type				varchar(64),
	slug				varchar(64),
	url					varchar(255),
	stable_version		varchar(32) NOT NULL DEFAULT '',
	stable_requires		varchar(32) NOT NULL DEFAULT '',
	stable_compat		varchar(32) NOT NULL DEFAULT '',
	stable_package		varchar(255),
	bleeding_version	varchar(32) NOT NULL DEFAULT '',
	bleeding_requires	varchar(32) NOT NULL DEFAULT '',
	bleeding_compat		varchar(32) NOT NULL DEFAULT '',
        bleeding_package        varchar(255),
        readme                          text NOT NULL DEFAULT '',
	PRIMARY KEY ( id ),
	UNIQUE KEY ( type, slug )
) DEFAULT CHARSET=UTF8;
