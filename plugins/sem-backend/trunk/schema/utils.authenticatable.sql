/**
 * Authenticatable status
 */
CREATE TYPE status_authenticatable AS enum (
	'trash',
	'inherit',
	'pending',
	'banned',
	'inactive',
	'active'
	);

/**
 * Authenticatable behavior
 *
 * Adds fields:
 * - status
 * - username
 * - password
 * - email
 *
 * Adds constraint:
 * - valid_username
 * - valid_password
 * - valid_email
 */
CREATE OR REPLACE FUNCTION authenticatable(varchar)
	RETURNS varchar
AS $$
DECLARE
	t_name		alias for $1;
BEGIN
	IF NOT column_exists(t_name, 'status')
	THEN
		EXECUTE $EXEC$
		ALTER TABLE $EXEC$ || quote_ident(t_name) || $EXEC$
			ADD COLUMN status status_authenticatable NOT NULL DEFAULT 'pending';
		$EXEC$;
	END IF;
	
	IF NOT column_exists(t_name, 'username')
	THEN
		EXECUTE $EXEC$
		ALTER TABLE $EXEC$ || quote_ident(t_name) || $EXEC$
			ADD COLUMN username varchar(255) UNIQUE;
		$EXEC$;
	END IF;
	
	IF NOT column_exists(t_name, 'password')
	THEN
		EXECUTE $EXEC$
		ALTER TABLE $EXEC$ || quote_ident(t_name) || $EXEC$
			ADD COLUMN password varchar(64) NOT NULL DEFAULT '';
		$EXEC$;
	END IF;
	
	IF NOT column_exists(t_name, 'email')
	THEN
		EXECUTE $EXEC$
		ALTER TABLE $EXEC$ || quote_ident(t_name) || $EXEC$
			ADD COLUMN email varchar(255) UNIQUE;
		$EXEC$;
	END IF;
	
	IF NOT constraint_exists(t_name, 'valid_username')
	THEN
		EXECUTE $EXEC$
		ALTER TABLE $EXEC$ || quote_ident(t_name) || $EXEC$
			ADD CONSTRAINT valid_username
				CHECK ( username IS NULL OR username <> '' );
		$EXEC$;
	END IF;
	
	IF NOT constraint_exists(t_name, 'valid_password')
	THEN
		EXECUTE $EXEC$
		ALTER TABLE $EXEC$ || quote_ident(t_name) || $EXEC$
			ADD CONSTRAINT valid_password
				CHECK ( NOT ( password <> '' AND username IS NULL AND email IS NULL ) );
		$EXEC$;
	END IF;
	
	IF NOT constraint_exists(t_name, 'valid_email')
	THEN
		EXECUTE $EXEC$
		ALTER TABLE $EXEC$ || quote_ident(t_name) || $EXEC$
			ADD CONSTRAINT valid_email
				CHECK ( email IS NULL OR check_email(email) );
		$EXEC$;
	END IF;
	
	RETURN t_name;
END;
$$ LANGUAGE plpgsql;
