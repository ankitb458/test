/**
 * Repeatable behavior
 *
 * Adds fields:
 * - rec_interval
 * - rec_count
 *
 * Adds constraint:
 * - valid_interval
 *
 * Adds triggers:
 * - {table}_01__check_interval
 */
CREATE OR REPLACE FUNCTION repeatable(varchar)
	RETURNS varchar
AS $$
DECLARE
	t_name		alias for $1;
BEGIN
	IF	NOT constraint_exists(t_name, 'valid_interval')
	THEN
		RAISE EXCEPTION 'Constraint valid_% does not exist on %. Default:', 'interval', t_name;
		EXECUTE $EXEC$
			CONSTRAINT valid_interval
				CHECK ( rec_interval IS NULL AND rec_count IS NULL OR
					rec_interval >= '0' AND ( rec_count IS NULL OR rec_count >= 0 ) );
		$EXEC$;
	END IF;
	
	EXECUTE $EXEC$
	CREATE OR REPLACE FUNCTION $EXEC$ || quote_ident(t_name || '__check_interval') || $EXEC$()
		RETURNS TRIGGER
	AS $DEF$
	BEGIN
		-- Make sure that rec_interval and rec_count are consistent
		IF	NEW.rec_interval IS NULL AND NEW.rec_count IS NOT NULL
		THEN
			NEW.rec_count := NULL;
		END IF;
		
		RETURN NEW;
	END;
	$DEF$ LANGUAGE plpgsql;
	$EXEC$;
	
	IF	NOT trigger_exists(t_name || '_01__check_interval')
	THEN
		EXECUTE $EXEC$
		CREATE TRIGGER $EXEC$ || quote_ident(t_name || '_01__check_interval') || $EXEC$
			BEFORE INSERT OR UPDATE ON $EXEC$ || quote_ident(t_name) || $EXEC$
		FOR EACH ROW EXECUTE PROCEDURE $EXEC$ || quote_ident(t_name || '__check_interval') || $EXEC$();
		$EXEC$;
	END IF;
	RETURN t_name;
END;
$$ LANGUAGE plpgsql;
