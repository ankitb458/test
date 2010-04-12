/**
 * Cleans a user before storing it
 */
CREATE OR REPLACE FUNCTION users_sanitize_name()
	RETURNS trigger
AS $$
BEGIN
	-- Set name
	IF	NEW.name IS NULL
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
			WHEN NEW.company <> ''
			THEN NEW.company
			ELSE NEW.username
			END;
		IF	NEW.name IS NULL
		THEN
			NEW.name = 'Anonymous';
		END IF;
	END IF;
	
	RETURN NEW;
END;
$$ LANGUAGE plpgsql;

CREATE TRIGGER users_05_sanitize_name
	BEFORE INSERT OR UPDATE ON users
FOR EACH ROW EXECUTE PROCEDURE users_sanitize_name();

/**
 * Makes sure a user's password is stored encrypted
 */
CREATE OR REPLACE FUNCTION users_sanitize_password()
	RETURNS trigger
AS $$
BEGIN
	NEW.password := trim(NEW.password);
	
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
	
	IF	NEW.status >= 'pending' AND NEW.password = ''
	THEN
		NEW.status := 'inactive';
	END IF;
	
	RETURN NEW;
END;
$$ LANGUAGE plpgsql;

CREATE TRIGGER users_05_sanitize_password
	BEFORE INSERT OR UPDATE ON users
FOR EACH ROW EXECUTE PROCEDURE users_sanitize_password();

/**
 * Makes sure a user isn't his own referral
 */
CREATE OR REPLACE FUNCTION users_sanitize_ref_id()
	RETURNS trigger
AS $$
BEGIN
	IF	NEW.ref_id = NEW.id
	THEN
		NEW.ref_id := NULL;
	END IF;
	
	RETURN NEW;
END;
$$ LANGUAGE plpgsql;

CREATE TRIGGER users_80_sanitize_ref_id
	BEFORE INSERT OR UPDATE ON users
FOR EACH ROW EXECUTE PROCEDURE users_sanitize_ref_id();