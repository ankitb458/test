CREATE TABLE IF NOT EXISTS versions (
	type		varchar(64),
	slug		varchar(64),
	version		varchar(32) NOT NULL,
	url			varchar(255),
	package		varchar(255),
	PRIMARY KEY ( type, slug )
) DEFAULT CHARSET=UTF8;
