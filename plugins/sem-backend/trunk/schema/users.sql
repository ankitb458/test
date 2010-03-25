
/*
 * Users table.
 */
CREATE TABLE users (
	id				bigint PRIMARY KEY,
	uuid			uuid NOT NULL DEFAULT uuid() UNIQUE,
	name			varchar(255) NOT NULL DEFAULT '',
	email			varchar(255) NOT NULL DEFAULT ''
);
