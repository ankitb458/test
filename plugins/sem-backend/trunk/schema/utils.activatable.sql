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
 * - min_date
 * - max_date
 *
 * Adds constraint:
 * - valid_min_max_date
 *
 * Adds indexes:
 * - {table}_activate
 * - {table}_deactivate
 *
 * Adds triggers:
 * - {table}_01__check_min_max_date
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
	IF NOT column_exists(t_name, 'status')
	THEN
		EXECUTE $EXEC$
		ALTER TABLE $EXEC$ || quote_ident(t_name) || $EXEC$
			ADD COLUMN status status_activatable NOT NULL DEFAULT 'draft';
		$EXEC$;
	END IF;
	
	IF NOT column_exists(t_name, 'min_date')
	THEN
		EXECUTE $EXEC$
		ALTER TABLE $EXEC$ || quote_ident(t_name) || $EXEC$
			ADD COLUMN min_date datetime;
		$EXEC$;
	END IF;
	
	IF NOT column_exists(t_name, 'max_date')
	THEN
		EXECUTE $EXEC$
		ALTER TABLE $EXEC$ || quote_ident(t_name) || $EXEC$
			ADD COLUMN max_date datetime;
		$EXEC$;
	END IF;
	
	IF NOT constraint_exists(t_name, 'valid_min_max_date')
	THEN
		EXECUTE $EXEC$
		ALTER TABLE $EXEC$ || quote_ident(t_name) || $EXEC$
			ADD CONSTRAINT valid_min_max_date
				CHECK ( min_date IS NULL OR max_date IS NULL OR min_date <= max_date );
		$EXEC$;
	END IF;
	
	IF NOT index_exists(t_name, t_name || '_activate')
	THEN
		EXECUTE $EXEC$
		CREATE INDEX $EXEC$ || quote_ident(t_name || '_activate') || $EXEC$
			ON $EXEC$ || quote_ident(t_name) || $EXEC$(min_date)
		WHERE	status = 'future';
		$EXEC$;
	END IF;
	
	IF NOT index_exists(t_name, t_name || '_deactivate')
	THEN
		EXECUTE $EXEC$
		CREATE INDEX $EXEC$ || quote_ident(t_name || '_deactivate') || $EXEC$
			ON $EXEC$ || quote_ident(t_name) || $EXEC$(max_date)
		WHERE	status = 'active' AND max_date IS NOT NULL;
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
		AND		min_date <= NOW()::datetime;
		
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
		AND		max_date <= NOW()::datetime;
		
		RETURN FOUND;
	END;
	$DEF$ LANGUAGE plpgsql;
	$EXEC$;
	
	EXECUTE $EXEC$
	CREATE OR REPLACE FUNCTION $EXEC$ || quote_ident(t_name || '__check_min_max_date') || $EXEC$()
		RETURNS TRIGGER
	AS $DEF$
	BEGIN
		-- Process schedules
		IF	NEW.status = 'future'
		THEN
			IF	NEW.min_date IS NULL
			THEN
				NEW.status := 'inactive';
			ELSEIF NEW.min_date <= NOW()::datetime
			THEN
				NEW.status := 'active';
			END IF;
		END IF;

		-- Make sure that min_date and max_date are consistent
		IF	NEW.min_date IS NOT NULL AND NEW.max_date IS NOT NULL AND NEW.min_date > NEW.max_date
		THEN
			NEW.max_date := NULL;
		END IF;
		
		RETURN NEW;
	END;
	$DEF$ LANGUAGE plpgsql;
	$EXEC$;
	
	IF NOT trigger_exists(t_name || '_01__check_min_max_date')
	THEN
		EXECUTE $EXEC$
		CREATE TRIGGER $EXEC$ || quote_ident(t_name || '_01__check_min_max_date') || $EXEC$
			BEFORE INSERT OR UPDATE ON $EXEC$ || quote_ident(t_name) || $EXEC$
		FOR EACH ROW EXECUTE PROCEDURE $EXEC$ || quote_ident(t_name || '__check_min_max_date') || $EXEC$();
		$EXEC$;
	END IF;
	
	RETURN t_name;
END;
$$ LANGUAGE plpgsql;
