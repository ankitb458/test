
/*
 * Users table.
 */
CREATE TABLE users (
	id				bigserial PRIMARY KEY,
	uuid			uuid NOT NULL DEFAULT uuid() UNIQUE,
	name			varchar(255) NOT NULL DEFAULT '',
	email			varchar(255) NOT NULL UNIQUE
);

CREATE INDEX users_sort ON users(name);
