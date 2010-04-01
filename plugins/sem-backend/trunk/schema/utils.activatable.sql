/**
 * Activatable status
 */
CREATE TYPE status_activatable AS enum (
	'trash',
	'inherit',
	'draft',
	'pending',
	'inactive',
	'future',
	'active'
	);

/**
 * Activatable behavior
 *
 * Adds fields:
 * - status
 * - starts
 * - stops
 *
 * Adds constraint:
 * - valid_activatable
 *
 * Adds indexes:
 * - {table}_activate
 * - {table}_deactivate
 *
 * Adds triggers:
 * - {table}_01__check_activatable
 *
 * Adds functions
 * - {table}_activate
 * - {table}_deactivate
 */
CREATE OR REPLACE FUNCTION activatable(varchar)
	RETURNS varchar
AS $$
DECLARE
	t_name		alias for $1;
BEGIN
	IF	NOT constraint_exists(t_name, 'valid_activatable')
	THEN
		RAISE EXCEPTION 'Constraint valid_% does not exist on %', 'activatable. Default:', t_name;
		EXECUTE $EXEC$
			CONSTRAINT valid_activatable
				CHECK ( stops >= starts );
		$EXEC$;
	END IF;
	
	IF	NOT index_exists(t_name, t_name || '_activate')
	THEN
		EXECUTE $EXEC$
		CREATE INDEX $EXEC$ || quote_ident(t_name || '_activate') || $EXEC$
			ON $EXEC$ || quote_ident(t_name) || $EXEC$(starts)
		WHERE	status = 'future';
		$EXEC$;
	END IF;
	
	IF	NOT index_exists(t_name, t_name || '_deactivate')
	THEN
		EXECUTE $EXEC$
		CREATE INDEX $EXEC$ || quote_ident(t_name || '_deactivate') || $EXEC$
			ON $EXEC$ || quote_ident(t_name) || $EXEC$(stops)
		WHERE	status = 'active' AND stops IS NOT NULL;
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
		AND		starts <= NOW()::datetime;
		
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
		AND		stops <= NOW()::datetime;
		
		RETURN FOUND;
	END;
	$DEF$ LANGUAGE plpgsql;
	$EXEC$;
	
	EXECUTE $EXEC$
	CREATE OR REPLACE FUNCTION $EXEC$ || quote_ident(t_name || '__check_activatable') || $EXEC$()
		RETURNS TRIGGER
	AS $DEF$
	BEGIN
		-- Process schedules
		IF	NEW.status = 'future'
		THEN
			IF	NEW.starts IS NULL
			THEN
				NEW.status := 'inactive';
			ELSEIF NEW.starts <= NOW()::datetime
			THEN
				NEW.status := 'active';
			END IF;
		END IF;

		-- Make sure that starts and stops are consistent
		IF	NEW.starts IS NOT NULL AND NEW.stops IS NOT NULL AND NEW.starts > NEW.stops
		THEN
			NEW.stops := NULL;
		END IF;
		
		RETURN NEW;
	END;
	$DEF$ LANGUAGE plpgsql;
	$EXEC$;
	
	IF	NOT trigger_exists(t_name || '_01__check_activatable')
	THEN
		EXECUTE $EXEC$
		CREATE TRIGGER $EXEC$ || quote_ident(t_name || '_01__check_activatable') || $EXEC$
			BEFORE INSERT OR UPDATE ON $EXEC$ || quote_ident(t_name) || $EXEC$
		FOR EACH ROW EXECUTE PROCEDURE $EXEC$ || quote_ident(t_name || '__check_activatable') || $EXEC$();
		$EXEC$;
	END IF;
	
	RETURN t_name;
END;
$$ LANGUAGE plpgsql;
