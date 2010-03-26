/*
 * Users table.
 */
CREATE TABLE users (
	id				bigserial PRIMARY KEY,
	uuid			uuid NOT NULL DEFAULT uuid() UNIQUE,
	name			varchar(255) NOT NULL DEFAULT '',
	email			varchar(255) NOT NULL UNIQUE,
	CONSTRAINT valid_email
		CHECK ( check_email(email) )
);

CREATE INDEX users_sort ON users(name);
CREATE INDEX users_email ON users(email);

/**
 * Cleans a user before storing it
 */
CREATE OR REPLACE FUNCTION users_clean()
	RETURNS trigger
AS $$
BEGIN
	NEW.name := trim(NEW.name);
	NEW.email := trim(lower(NEW.email));
	 
	RETURN NEW;
END;
$$ LANGUAGE plpgsql;

CREATE TRIGGER users_3_clean
	BEFORE INSERT OR UPDATE ON users
FOR EACH ROW EXECUTE PROCEDURE users_clean();