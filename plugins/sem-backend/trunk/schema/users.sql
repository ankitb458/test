/*
 * Users table.
 */
CREATE TABLE users (
	id				bigserial PRIMARY KEY,
	uuid			uuid NOT NULL DEFAULT uuid() UNIQUE,
	ukey			varchar(255) UNIQUE,
	status			status_authenticatable NOT NULL DEFAULT 'pending',
	name			varchar(255) NOT NULL,
	username		varchar(255) UNIQUE,
	password		varchar(64) NOT NULL DEFAULT '',
	email			varchar(255) UNIQUE,
	nickname		varchar(255) NOT NULL DEFAULT '',
	firstname		varchar(255) NOT NULL DEFAULT '',
	lastname		varchar(255) NOT NULL DEFAULT '',
	phone			varchar(255) NOT NULL DEFAULT '',
	paypal			varchar(255),
	ref_id			bigint REFERENCES users(id) ON UPDATE CASCADE ON DELETE SET NULL,
	CONSTRAINT valid_email
		CHECK ( email IS NULL OR check_email(email) ),
	CONSTRAINT valid_paypal
		CHECK ( paypal IS NULL OR check_email(paypal) )
);

SELECT	authenticatable('users'),
		sluggable('users'),
		timestampable('users'),
		searchable('users'),
		trashable('users');

CREATE INDEX users_sort ON users(name);

COMMENT ON TABLE users IS E'Users

- name corresponds to the screen name.';

/**
 * Cleans a user before storing it
 */
CREATE OR REPLACE FUNCTION users_clean()
	RETURNS trigger
AS $$
BEGIN
	-- trim fields
	NEW.name := trim(NEW.name);
	
	NEW.username := trim(NEW.username);
	NEW.password := trim(NEW.password);
	
	NEW.firstname := trim(NEW.firstname);
	NEW.lastname := trim(NEW.lastname);
	NEW.nickname := trim(NEW.nickname);
	
	NEW.email := trim(lower(NEW.email));
	NEW.phone := trim(NEW.phone);
	
	NEW.paypal := trim(NEW.paypal);
	
	-- set name
	IF	COALESCE(NEW.name, '') = ''
	THEN
		NEW.name := CASE
			WHEN NEW.firstname <> '' AND NEW.lastname <> ''
			THEN NEW.firstname || ' ' || NEW.lastname
			WHEN NEW.nickname <> ''
			THEN NEW.nickname
			WHEN NEW.firstname <> ''
			THEN NEW.firstname
			WHEN NEW.lastname <> ''
			THEN NEW.lastname
			ELSE COALESCE(NEW.username, '')
			END;
	END IF;
	
	-- disable inherit and trash for now
	IF	NEW.status <= 'inherit'
	THEN
		RAISE EXCEPTION 'users cannot be trashed yet.';
	END IF;
	
	RETURN NEW;
END;
$$ LANGUAGE plpgsql;

CREATE TRIGGER users_03_clean
	BEFORE INSERT OR UPDATE ON users
FOR EACH ROW EXECUTE PROCEDURE users_clean();

/**
 * Extends tsv for users.
 */
CREATE OR REPLACE FUNCTION users_tsv()
	RETURNS trigger
AS $$
BEGIN
	IF TG_OP = 'UPDATE'
	THEN
		IF	NEW.tsv = OLD.tsv AND
			NEW.nickname = OLD.nickname AND
			NEW.firstname = OLD.firstname AND
			NEW.lastname = OLD.lastname AND
			NEW.username IS NOT DISTINCT FROM OLD.username AND
			NEW.email IS NOT DISTINCT FROM OLD.email AND
			NEW.paypal IS NOT DISTINCT FROM OLD.paypal
		THEN
			RETURN NEW;
		END IF;
	END IF;
	
	NEW.tsv := NEW.tsv
		|| setweight(to_tsvector(NEW.nickname), 'A')
		|| setweight(to_tsvector(NEW.firstname), 'A')
		|| setweight(to_tsvector(NEW.lastname), 'A')
		|| setweight(to_tsvector(COALESCE(NEW.username, '')), 'B');
	
	IF	NEW.email IS NOT NULL
	THEN
		NEW.tsv := NEW.tsv
			|| setweight(to_tsvector(NEW.email), 'B')
			|| setweight(to_tsvector(regexp_replace(
				substring(NEW.email from 1 for (position('@' in NEW.email) - 1)),
				'[._-]+',
				' ')), 'B');
	END IF;
	
	IF	NEW.paypal IS NOT NULL AND NEW.paypal IS DISTINCT FROM NEW.email
	THEN
		NEW.tsv := NEW.tsv
			|| setweight(to_tsvector(NEW.paypal), 'B')
			|| setweight(to_tsvector(regexp_replace(
				substring(NEW.paypal from 1 for (position('@' in NEW.paypal) - 1)),
				'[._-]+',
				' ')), 'B');
	END IF;
	
	RETURN NEW;
END $$ LANGUAGE plpgsql;

CREATE TRIGGER users_20_tsv
	BEFORE INSERT OR UPDATE ON users
FOR EACH ROW EXECUTE PROCEDURE users_tsv();