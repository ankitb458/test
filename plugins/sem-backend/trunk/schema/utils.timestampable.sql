/**
 * Timestampable behavior
 *
 * Adds fields:
 * - {table}.created_date
 * - {table}.modified_date
 *
 * Adds triggers:
 * - {table}_10__modified()
 */
CREATE OR REPLACE FUNCTION timestampable(varchar)
	RETURNS varchar
AS $$
DECLARE
	t_name		alias for $1;
BEGIN
	EXECUTE $EXEC$
	CREATE OR REPLACE FUNCTION $EXEC$ || quote_ident(t_name || '__modified') || $EXEC$()
		RETURNS TRIGGER
	AS $DEF$
	BEGIN
		IF	NEW IS DISTINCT FROM OLD
		THEN
			NEW.modified_date := NOW();
		END IF;
		
		RETURN NEW;
	END;
	$DEF$ LANGUAGE plpgsql;
	$EXEC$;
	
	IF	NOT trigger_exists(t_name || '_99__modified')
	THEN
		EXECUTE $EXEC$
		CREATE TRIGGER $EXEC$ || quote_ident(t_name || '_99__modified') || $EXEC$
			BEFORE UPDATE ON $EXEC$ || quote_ident(t_name) || $EXEC$
		FOR EACH ROW EXECUTE PROCEDURE $EXEC$ || quote_ident(t_name || '__modified') || $EXEC$();
		$EXEC$;
	END IF;
	
	RETURN t_name;
END;
$$ LANGUAGE plpgsql;
