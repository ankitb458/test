/**
 * Activatable status
 */
CREATE TYPE status_activatable AS enum (
	'trash',
	'draft',
	'pending',
	'inactive',
	'future',
	'active'
	);

/**
 * Activatable behavior
 *
 * Checks constraint:
 * - valid_activatable
 *
 * Adds indexes:
 * - {table}_activate
 * - {table}_deactivate
 *
 * Adds triggers:
 * - {table}_01__sanitize_activatable
 *
 * Adds functions
 * - {table}_activate
 * - {table}_deactivate
 */
CREATE OR REPLACE FUNCTION activatable(varchar, varchar)
	RETURNS varchar
AS $$
DECLARE
	t_name		alias for $1;
	t_field		alias for $2;
BEGIN
	IF	NOT constraint_exists(t_name, 'valid_' || t_field)
	THEN
		RAISE EXCEPTION 'Constraint valid_% does not exist on %. Default: %', t_field, t_name,
		$EXEC$
			CONSTRAINT valid_activatable
				CHECK ( expire_date >= $EXEC$ || t_field || $EXEC$ )
		$EXEC$;
	END IF;
	
	IF	NOT index_exists(t_name, t_name || '_activate')
	THEN
		EXECUTE $EXEC$
		CREATE INDEX $EXEC$ || quote_ident(t_name || '_activate') || $EXEC$
			ON $EXEC$ || quote_ident(t_name) || $EXEC$($EXEC$ || quote_ident(t_field) || $EXEC$)
		WHERE	status = 'future';
		$EXEC$;
	END IF;
	
	IF	NOT index_exists(t_name, t_name || '_deactivate')
	THEN
		EXECUTE $EXEC$
		CREATE INDEX $EXEC$ || quote_ident(t_name || '_deactivate') || $EXEC$
			ON $EXEC$ || quote_ident(t_name) || $EXEC$(expire_date)
		WHERE	status = 'active' AND expire_date IS NOT NULL;
		$EXEC$;
	END IF;
	
	EXECUTE $EXEC$
	CREATE OR REPLACE FUNCTION $EXEC$ || quote_ident(t_name || '_activate') || $EXEC$()
		RETURNS boolean
	AS $DEF$
	BEGIN
		UPDATE	$EXEC$ || quote_ident(t_name) || $EXEC$
		SET		status = 'active'
		WHERE	status = 'future'
		AND		$EXEC$ || quote_ident(t_field) || $EXEC$ <= NOW()::datetime;
		
		RETURN FOUND;
	END;
	$DEF$ LANGUAGE plpgsql;
	$EXEC$;
	
	EXECUTE $EXEC$
	CREATE OR REPLACE FUNCTION $EXEC$ || quote_ident(t_name || '_deactivate') || $EXEC$()
		RETURNS boolean
	AS $DEF$
	BEGIN
		UPDATE	$EXEC$ || quote_ident(t_name) || $EXEC$
		SET		status = 'inactive'
		WHERE	status = 'active'
		AND		expire_date <= NOW()::datetime;
		
		RETURN FOUND;
	END;
	$DEF$ LANGUAGE plpgsql;
	$EXEC$;
	
	EXECUTE $EXEC$
	CREATE OR REPLACE FUNCTION $EXEC$ || quote_ident(t_name || '__sanitize_activatable') || $EXEC$()
		RETURNS TRIGGER
	AS $DEF$
	BEGIN
		-- Process schedules
		IF	NEW.status = 'future'
		THEN
			IF	NEW.$EXEC$ || quote_ident(t_field) || $EXEC$ IS NULL
			THEN
				NEW.status := 'inactive';
			ELSEIF NEW.$EXEC$ || quote_ident(t_field) || $EXEC$ <= NOW()::datetime
			THEN
				NEW.status := 'active';
			END IF;
		END IF;

		-- Make sure that start_date and expire_date are consistent
		IF	NEW.$EXEC$ || quote_ident(t_field) || $EXEC$ IS NOT NULL AND
			NEW.expire_date IS NOT NULL AND
			NEW.$EXEC$ || quote_ident(t_field) || $EXEC$ > NEW.expire_date
		THEN
			NEW.expire_date := NEW.$EXEC$ || quote_ident(t_field) || $EXEC$;
		END IF;
		
		RETURN NEW;
	END;
	$DEF$ LANGUAGE plpgsql;
	$EXEC$;
	
	IF	NOT trigger_exists(t_name || '_01__sanitize_activatable')
	THEN
		EXECUTE $EXEC$
		CREATE TRIGGER $EXEC$ || quote_ident(t_name || '_01__sanitize_activatable') || $EXEC$
			BEFORE INSERT OR UPDATE ON $EXEC$ || quote_ident(t_name) || $EXEC$
		FOR EACH ROW EXECUTE PROCEDURE $EXEC$ || quote_ident(t_name || '__sanitize_activatable') || $EXEC$();
		$EXEC$;
	END IF;
	
	RETURN t_name;
END;
$$ LANGUAGE plpgsql;
