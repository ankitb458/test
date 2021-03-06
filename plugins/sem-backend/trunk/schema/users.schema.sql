/*
 * Users table.
 */
CREATE TABLE users (
	id				bigserial PRIMARY KEY,
	uuid			uuid NOT NULL DEFAULT uuid() UNIQUE,
	ukey			slug UNIQUE,
	status			status_authenticatable NOT NULL DEFAULT 'pending',
	name			varchar NOT NULL,
	username		varchar(128),
	password		varchar(60) NOT NULL DEFAULT '',
	email			email,
	company			varchar NOT NULL DEFAULT '',
	nickname		varchar NOT NULL DEFAULT '',
	firstname		varchar NOT NULL DEFAULT '',
	lastname		varchar NOT NULL DEFAULT '',
	country			code_country,
	state			code_state,
	tax_dispense	varchar,
	tax_docs		boolean NOT NULL DEFAULT false,
	payment_method	method_payment NOT NULL DEFAULT 'paypal',
	paypal			email,
	ref_id			bigint REFERENCES users(id),
	ip				inet,
	token			uuid,
	created_date	datetime NOT NULL DEFAULT NOW(),
	modified_date	datetime NOT NULL DEFAULT NOW(),
	memo			text NOT NULL DEFAULT '',
--	tsv				tsvector NOT NULL,
	CONSTRAINT valid_name
		CHECK ( name <> '' AND name = trim(name) AND
			nickname = trim(nickname) AND firstname = trim(firstname) AND lastname = trim(lastname) ),
	CONSTRAINT valid_username
		CHECK ( username <> '' and username = trim(username) ),
	CONSTRAINT valid_password
		CHECK ( NOT ( password <> '' AND username IS NULL AND email IS NULL ) ),
	CONSTRAINT valid_state
		CHECK ( state IS NULL OR state IS NOT NULL AND country IN ('US', 'CA', 'AU') ),
	CONSTRAINT valid_tax_dispense
		CHECK ( tax_dispense <> '' AND tax_dispense = trim(tax_dispense) ),
	CONSTRAINT valid_referral
		CHECK ( id <> ref_id ),
	CONSTRAINT unsupported_behavior
		CHECK ( payment_method = 'paypal' )
);

SELECT	sluggable('users'),
		timestampable('users'),
--		searchable('users'),
		trashable('users');

CREATE INDEX users_sort ON users(name);
CREATE UNIQUE INDEX users_username_key ON users(lower(username));
CREATE UNIQUE INDEX users_email_key ON users(lower(email));
CREATE INDEX users_ref_id ON users(ref_id);

COMMENT ON TABLE users IS E'Users

- name corresponds to the screen name.
- aff_docs corresponds to whether the affiliate''s tax docs were sent.
- ref_id is the id of whichever affiliate referred the user.
- ip is the last known IP address.
- token is not null when the user requests an action (unlock, reset password...).';

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
 * Extends tsv for users.
 */
CREATE OR REPLACE FUNCTION users_tsv()
	RETURNS trigger
AS $$
BEGIN
	IF	TG_OP = 'UPDATE'
	THEN
		IF	NEW.tsv IS NOT DISTINCT FROM OLD.tsv AND
			NEW.nickname IS NOT DISTINCT FROM OLD.nickname AND
			NEW.firstname IS NOT DISTINCT FROM OLD.firstname AND
			NEW.lastname IS NOT DISTINCT FROM OLD.lastname AND
			NEW.company IS NOT DISTINCT FROM OLD.company AND
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
		|| setweight(to_tsvector(NEW.company), 'A')
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

--CREATE TRIGGER users_20_tsv
--	BEFORE INSERT OR UPDATE ON users
--FOR EACH ROW EXECUTE PROCEDURE users_tsv();

/**
 * Process read-only fields
 */
CREATE OR REPLACE FUNCTION users_readonly()
	RETURNS trigger
AS $$
BEGIN
	IF	ROW(NEW.id) IS DISTINCT FROM ROW(OLD.id)
	THEN
		RAISE EXCEPTION 'Can''t edit readonly field in users.id = %', NEW.id;
	END IF;
	
	RETURN NEW;
END $$ LANGUAGE plpgsql;

CREATE CONSTRAINT TRIGGER users_01_readonly
	AFTER UPDATE ON users
FOR EACH ROW EXECUTE PROCEDURE users_readonly();

/**
 * Retrieve a user based on its email or username
 */
CREATE OR REPLACE FUNCTION get_user(varchar)
	RETURNS SETOF users
AS $$
DECLARE
	_key		varchar := $1;
BEGIN
	_key := trim(_key);
	
	IF	_key = ''
	THEN
		RETURN	QUERY
		SELECT	NULL::users;
	ELSEIF is_email(_key)
	THEN
		RETURN QUERY
		SELECT	*
		FROM	users
		WHERE	email = lower(_key);
	ELSE
		RETURN QUERY
		SELECT	*
		FROM	users
		WHERE	username = lower(_key);
	END IF;
END $$ LANGUAGE plpgsql STABLE STRICT ROWS 1;