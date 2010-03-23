
/*
 * Users table.
 */
CREATE TABLE users (
	id				bigint PRIMARY KEY,
	uuid			uuid NOT NULL DEFAULT uuid() UNIQUE
);
