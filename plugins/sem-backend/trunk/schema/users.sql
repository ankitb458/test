/*
 * Users table.
 */
CREATE TABLE users (
	id				bigserial PRIMARY KEY,
	uuid			uuid NOT NULL DEFAULT uuid() UNIQUE,
	ukey			varchar(255) UNIQUE,
	status			status_authenticatable NOT NULL DEFAULT 'pending',
	name			varchar(255) NOT NULL,
	username		varchar(255),
	password		varchar(60) NOT NULL DEFAULT '',
	email			varchar(255),
	nickname		varchar(255) NOT NULL DEFAULT '',
	firstname		varchar(255) NOT NULL DEFAULT '',
	lastname		varchar(255) NOT NULL DEFAULT '',
	phone			varchar(255) NOT NULL DEFAULT '',
	ref_id			bigint REFERENCES users(id) ON UPDATE CASCADE ON DELETE SET NULL,
	paypal			varchar(255),
	CONSTRAINT valid_ukey
		CHECK ( ukey IS NULL OR ukey ~ '^[a-z0-9](?:[a-z0-9._-]*[a-z0-9])?$' AND ukey !~ '^[0-9]+$' ),
	CONSTRAINT valid_username
		CHECK ( username IS NULL OR username <> '' ),
	CONSTRAINT valid_password
		CHECK ( NOT ( password <> '' AND username IS NULL AND email IS NULL ) ),
	CONSTRAINT valid_email
		CHECK ( email IS NULL OR is_email(email) ),
	CONSTRAINT valid_paypal
		CHECK ( paypal IS NULL OR is_email(paypal) )
);

SELECT	sluggable('users'),
		timestampable('users'),
		searchable('users'),
		trashable('users');

CREATE INDEX users_sort ON users(name);
CREATE UNIQUE INDEX users_username_key ON users(lower(username));
CREATE UNIQUE INDEX users_email_key ON users(lower(email));
CREATE INDEX users_ref_id ON users(ref_id);

COMMENT ON TABLE users IS E'Users

- name corresponds to the screen name.';

/**
 * Active users
 */
CREATE OR REPLACE VIEW active_users
AS
SELECT	users.*
FROM	users
WHERE	status = 'active';

COMMENT ON VIEW active_users IS E'Active Users

- status is active.';

/**
 * Cleans a user before storing it
 */
CREATE OR REPLACE FUNCTION users_clean()
	RETURNS trigger
AS $$
BEGIN
	-- Trim fields
	NEW.name := trim(NEW.name);
	
	NEW.username := trim(NEW.username);
	NEW.password := trim(NEW.password);
	
	NEW.firstname := trim(NEW.firstname);
	NEW.lastname := trim(NEW.lastname);
	NEW.nickname := trim(NEW.nickname);
	
	NEW.email := trim(NEW.email);
	NEW.phone := trim(NEW.phone);
	
	NEW.paypal := trim(NEW.paypal);
	
	-- Set name
	IF	( NEW.name <> '' ) IS NOT TRUE
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
	
	-- Disable inherit and trash for now
	IF	NEW.status = 'inherit'
	THEN
		RAISE EXCEPTION 'Undefined behavior for users.status = inherit.';
	END IF;

	IF	NEW.password <> ''
	THEN
		IF	length(NEW.password) = 60 AND substring(NEW.password from 1 for 4) = '$2a$'
		THEN
			-- blowfish hashed already
			NULL;
		ELSEIF length(NEW.password) = 32 AND NEW.password ~ '^[0-9a-f]{32}$'
		THEN
			-- md5 hash, keep as is for backwards compatibility
			NULL;
		ELSE
			-- Hash using blowfish
			NEW.password := crypt(NEW.password, gen_salt('bf', 10));
		END IF;
	END IF;
	
	RETURN NEW;
END;
$$ LANGUAGE plpgsql;

CREATE TRIGGER users_05_clean
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
		IF	NEW.tsv IS NOT DISTINCT FROM OLD.tsv AND
			NEW.nickname IS NOT DISTINCT FROM OLD.nickname AND
			NEW.firstname IS NOT DISTINCT FROM OLD.firstname AND
			NEW.lastname IS NOT DISTINCT FROM OLD.lastname AND
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
	
	IF	is_email(NEW.email)
	THEN
		NEW.tsv := NEW.tsv
			|| setweight(to_tsvector(NEW.email), 'B')
			|| setweight(to_tsvector(regexp_replace(
				substring(NEW.email from 1 for (position('@' in NEW.email) - 1)),
				'[._-]+',
				' ', 'g')), 'B');
	END IF;
	
	IF	is_email(NEW.paypal) AND NEW.paypal IS DISTINCT FROM NEW.email
	THEN
		NEW.tsv := NEW.tsv
			|| setweight(to_tsvector(NEW.paypal), 'B')
			|| setweight(to_tsvector(regexp_replace(
				substring(NEW.paypal from 1 for (position('@' in NEW.paypal) - 1)),
				'[._-]+',
				' ', 'g')), 'B');
	END IF;
	
	RETURN NEW;
END $$ LANGUAGE plpgsql;

CREATE TRIGGER users_20_tsv
	BEFORE INSERT OR UPDATE ON users
FOR EACH ROW EXECUTE PROCEDURE users_tsv();